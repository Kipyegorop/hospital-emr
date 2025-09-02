<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Triage;
use App\Models\Patient;
use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TriageController extends Controller
{
    /**
     * Display a listing of triage records
     */
    public function index(Request $request)
    {
        $query = Triage::with(['patient', 'nurse', 'department', 'encounter']);

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            $query->today();
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by triage level
        if ($request->has('triage_level')) {
            $query->where('triage_level', $request->triage_level);
        }

        // Filter by queue status
        if ($request->has('queue_status')) {
            $query->where('queue_status', $request->queue_status);
        }

        // Order by priority and queue time
        $triages = $query->byPriority()->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => [
                'triages' => $triages->items(),
                'pagination' => [
                    'current_page' => $triages->currentPage(),
                    'last_page' => $triages->lastPage(),
                    'per_page' => $triages->perPage(),
                    'total' => $triages->total(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created triage record
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'department_id' => 'nullable|exists:departments,id',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'systolic_bp' => 'nullable|integer|min:50|max:300',
            'diastolic_bp' => 'nullable|integer|min:30|max:200',
            'heart_rate' => 'nullable|integer|min:30|max:250',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|numeric|min:50|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:30|max:250',
            'triage_level' => 'required|in:emergency,urgent,semi_urgent,non_urgent,fast_track',
            'chief_complaint' => 'required|string|max:1000',
            'presenting_symptoms' => 'nullable|string|max:2000',
            'pain_scale' => 'nullable|string|max:100',
            'allergies' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'medical_history' => 'nullable|string|max:2000',
            'assessment_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::transaction(function () use ($request, &$triage) {
            // Get triage level configuration
            $triageLevels = Triage::getTriageLevels();
            $triageConfig = $triageLevels[$request->triage_level];

            // Generate queue number
            $queueNumber = Triage::generateQueueNumber($request->department_id);

            // Create triage record
            $triage = Triage::create(array_merge($request->all(), [
                'nurse_id' => $request->user()->id,
                'triage_color' => $triageConfig['color'],
                'queue_number' => $queueNumber,
                'queue_status' => 'waiting',
                'queue_time' => now(),
            ]));

            // Calculate BMI if height and weight provided
            if ($request->height && $request->weight) {
                $triage->calculateBmi();
                $triage->save();
            }

            // Create encounter if not provided
            if (!$request->encounter_id) {
                $encounter = Encounter::create([
                    'encounter_number' => Encounter::generateEncounterNumber(),
                    'patient_id' => $request->patient_id,
                    'department_id' => $request->department_id,
                    'encounter_type' => 'outpatient',
                    'status' => 'active',
                    'start_time' => now(),
                    'chief_complaint' => $request->chief_complaint,
                ]);

                $triage->update(['encounter_id' => $encounter->id]);
            }
        });

        $triage->load(['patient', 'nurse', 'department', 'encounter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Triage record created successfully',
            'data' => [
                'triage' => $triage,
                'queue_position' => $this->getQueuePosition($triage),
            ]
        ], 201);
    }

    /**
     * Display the specified triage record
     */
    public function show(Triage $triage)
    {
        $triage->load(['patient', 'nurse', 'department', 'encounter']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'triage' => $triage,
                'queue_position' => $this->getQueuePosition($triage),
                'waiting_time' => $this->getWaitingTime($triage),
            ]
        ]);
    }

    /**
     * Update the specified triage record
     */
    public function update(Request $request, Triage $triage)
    {
        $validator = Validator::make($request->all(), [
            'temperature' => 'nullable|numeric|min:30|max:45',
            'systolic_bp' => 'nullable|integer|min:50|max:300',
            'diastolic_bp' => 'nullable|integer|min:30|max:200',
            'heart_rate' => 'nullable|integer|min:30|max:250',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|numeric|min:50|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:30|max:250',
            'triage_level' => 'sometimes|in:emergency,urgent,semi_urgent,non_urgent,fast_track',
            'chief_complaint' => 'sometimes|string|max:1000',
            'presenting_symptoms' => 'nullable|string|max:2000',
            'pain_scale' => 'nullable|string|max:100',
            'allergies' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'medical_history' => 'nullable|string|max:2000',
            'assessment_notes' => 'nullable|string|max:2000',
            'queue_status' => 'sometimes|in:waiting,called,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update triage color if level changed
        if ($request->has('triage_level')) {
            $triageLevels = Triage::getTriageLevels();
            $triageConfig = $triageLevels[$request->triage_level];
            $request->merge(['triage_color' => $triageConfig['color']]);
        }

        // Update timestamps based on status changes
        if ($request->has('queue_status')) {
            switch ($request->queue_status) {
                case 'called':
                    $request->merge(['called_time' => now()]);
                    break;
                case 'in_progress':
                    $request->merge(['started_time' => now()]);
                    break;
                case 'completed':
                case 'cancelled':
                    $request->merge(['completed_time' => now()]);
                    break;
            }
        }

        $triage->update($request->all());

        // Recalculate BMI if height or weight changed
        if ($request->has('height') || $request->has('weight')) {
            $triage->calculateBmi();
            $triage->save();
        }

        $triage->load(['patient', 'nurse', 'department', 'encounter']);

        return response()->json([
            'status' => 'success',
            'message' => 'Triage record updated successfully',
            'data' => [
                'triage' => $triage,
                'queue_position' => $this->getQueuePosition($triage),
            ]
        ]);
    }

    /**
     * Get queue display for waiting area
     */
    public function queueDisplay(Request $request)
    {
        $query = Triage::with(['patient', 'department'])
            ->today()
            ->whereIn('queue_status', ['waiting', 'called', 'in_progress']);

        // Filter by department if specified
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $queue = $query->byPriority()->get();

        // Group by status for display
        $queueDisplay = [
            'waiting' => $queue->where('queue_status', 'waiting')->values(),
            'called' => $queue->where('queue_status', 'called')->values(),
            'in_progress' => $queue->where('queue_status', 'in_progress')->values(),
            'statistics' => [
                'total_waiting' => $queue->where('queue_status', 'waiting')->count(),
                'total_called' => $queue->where('queue_status', 'called')->count(),
                'total_in_progress' => $queue->where('queue_status', 'in_progress')->count(),
                'average_wait_time' => $this->getAverageWaitTime(),
                'last_updated' => now(),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $queueDisplay
        ]);
    }

    /**
     * Call next patient in queue
     */
    public function callNext(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => 'nullable|exists:departments,id',
            'triage_level' => 'nullable|in:emergency,urgent,semi_urgent,non_urgent,fast_track',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Triage::with(['patient', 'department'])
            ->today()
            ->where('queue_status', 'waiting');

        // Filter by department if specified
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by triage level if specified
        if ($request->has('triage_level')) {
            $query->where('triage_level', $request->triage_level);
        }

        $nextPatient = $query->byPriority()->first();

        if (!$nextPatient) {
            return response()->json([
                'status' => 'error',
                'message' => 'No patients waiting in queue'
            ], 404);
        }

        $nextPatient->update([
            'queue_status' => 'called',
            'called_time' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Patient called successfully',
            'data' => [
                'triage' => $nextPatient,
                'patient' => $nextPatient->patient,
            ]
        ]);
    }

    /**
     * Get queue statistics
     */
    public function queueStats(Request $request)
    {
        $query = Triage::today();

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $stats = [
            'total_patients' => $query->count(),
            'waiting' => $query->where('queue_status', 'waiting')->count(),
            'called' => $query->where('queue_status', 'called')->count(),
            'in_progress' => $query->where('queue_status', 'in_progress')->count(),
            'completed' => $query->where('queue_status', 'completed')->count(),
            'cancelled' => $query->where('queue_status', 'cancelled')->count(),
            'by_triage_level' => [
                'emergency' => $query->where('triage_level', 'emergency')->count(),
                'urgent' => $query->where('triage_level', 'urgent')->count(),
                'semi_urgent' => $query->where('triage_level', 'semi_urgent')->count(),
                'non_urgent' => $query->where('triage_level', 'non_urgent')->count(),
                'fast_track' => $query->where('triage_level', 'fast_track')->count(),
            ],
            'average_wait_time' => $this->getAverageWaitTime(),
            'longest_wait' => $this->getLongestWait(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Helper method to get queue position
     */
    private function getQueuePosition(Triage $triage)
    {
        if ($triage->queue_status !== 'waiting') {
            return null;
        }

        return Triage::today()
            ->where('queue_status', 'waiting')
            ->where('department_id', $triage->department_id)
            ->byPriority()
            ->get()
            ->search(function ($item) use ($triage) {
                return $item->id === $triage->id;
            }) + 1;
    }

    /**
     * Helper method to get waiting time
     */
    private function getWaitingTime(Triage $triage)
    {
        if (!$triage->queue_time) {
            return null;
        }

        return now()->diffInMinutes($triage->queue_time);
    }

    /**
     * Helper method to get average wait time
     */
    private function getAverageWaitTime()
    {
        $completedToday = Triage::today()
            ->where('queue_status', 'completed')
            ->whereNotNull('queue_time')
            ->whereNotNull('started_time')
            ->get();

        if ($completedToday->isEmpty()) {
            return 0;
        }

        $totalWaitTime = $completedToday->sum(function ($triage) {
            return $triage->queue_time->diffInMinutes($triage->started_time);
        });

        return round($totalWaitTime / $completedToday->count());
    }

    /**
     * Helper method to get longest wait
     */
    private function getLongestWait()
    {
        $longestWaiting = Triage::today()
            ->where('queue_status', 'waiting')
            ->whereNotNull('queue_time')
            ->orderBy('queue_time')
            ->first();

        if (!$longestWaiting) {
            return 0;
        }

        return now()->diffInMinutes($longestWaiting->queue_time);
    }
}
