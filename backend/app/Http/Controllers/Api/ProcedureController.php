<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Procedure;
use App\Models\TheatreSchedule;
use App\Models\ProcedureConsumable;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProcedureController extends Controller
{
    /**
     * Display a listing of procedures
     */
    public function index(Request $request)
    {
        $query = Procedure::query();

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by specialty
        if ($request->has('specialty')) {
            $query->bySpecialty($request->specialty);
        }

        // Filter by complexity
        if ($request->has('complexity_level')) {
            $query->byComplexity($request->complexity_level);
        }

        // Search by term
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Show only active procedures by default
        if (!$request->has('include_inactive')) {
            $query->active();
        }

        // Show only emergency procedures
        if ($request->boolean('emergency_only')) {
            $query->emergency();
        }

        // Show only day case procedures
        if ($request->boolean('day_case_only')) {
            $query->dayCase();
        }

        $procedures = $query->orderBy('procedure_name')->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => [
                'procedures' => $procedures->items(),
                'pagination' => [
                    'current_page' => $procedures->currentPage(),
                    'last_page' => $procedures->lastPage(),
                    'per_page' => $procedures->perPage(),
                    'total' => $procedures->total(),
                ],
                'categories' => Procedure::getCategories(),
                'specialties' => Procedure::getSpecialties(),
                'complexity_levels' => Procedure::getComplexityLevels(),
            ]
        ]);
    }

    /**
     * Store a newly created procedure
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'procedure_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'required|string|max:100',
            'specialty' => 'nullable|string|max:100',
            'estimated_duration_minutes' => 'required|integer|min:5|max:1440',
            'complexity_level' => 'required|in:simple,moderate,complex,high_risk',
            'anesthesia_type' => 'nullable|in:local,regional,general,sedation,none',
            'pre_procedure_requirements' => 'nullable|string|max:2000',
            'post_procedure_care' => 'nullable|string|max:2000',
            'contraindications' => 'nullable|string|max:2000',
            'complications' => 'nullable|string|max:2000',
            'base_price' => 'required|numeric|min:0|max:999999.99',
            'surgeon_fee' => 'nullable|numeric|min:0|max:999999.99',
            'anesthetist_fee' => 'nullable|numeric|min:0|max:999999.99',
            'theatre_fee' => 'nullable|numeric|min:0|max:999999.99',
            'consumables_cost' => 'nullable|numeric|min:0|max:999999.99',
            'nhif_rate' => 'nullable|numeric|min:0|max:999999.99',
            'billing_code' => 'nullable|string|max:50',
            'required_equipment' => 'nullable|array',
            'required_staff' => 'nullable|array',
            'consumables_list' => 'nullable|array',
            'requires_implants' => 'boolean',
            'requires_blood_products' => 'boolean',
            'requires_consent' => 'boolean',
            'is_emergency_procedure' => 'boolean',
            'is_day_case' => 'boolean',
            'typical_los_days' => 'nullable|integer|min:0|max:365',
            'quality_indicators' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate procedure code
        $procedureCode = Procedure::generateProcedureCode($request->category);

        // Create procedure
        $procedure = Procedure::create(array_merge($request->all(), [
            'procedure_code' => $procedureCode,
            'status' => 'active',
            'is_available' => true,
        ]));

        // Calculate total cost
        $procedure->calculateTotalCost();
        $procedure->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Procedure created successfully',
            'data' => [
                'procedure' => $procedure,
            ]
        ], 201);
    }

    /**
     * Display the specified procedure
     */
    public function show(Procedure $procedure)
    {
        $procedure->load(['theatreSchedules.patient', 'procedureConsumables']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'procedure' => $procedure,
                'complexity_config' => $procedure->complexity_config,
                'is_high_risk' => $procedure->is_high_risk,
                'estimated_duration_hours' => $procedure->estimated_duration_hours,
                'recent_schedules' => $procedure->theatreSchedules()
                    ->with(['patient', 'primarySurgeon'])
                    ->latest('scheduled_date')
                    ->limit(10)
                    ->get(),
            ]
        ]);
    }

    /**
     * Update the specified procedure
     */
    public function update(Request $request, Procedure $procedure)
    {
        $validator = Validator::make($request->all(), [
            'procedure_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'category' => 'sometimes|string|max:100',
            'specialty' => 'nullable|string|max:100',
            'estimated_duration_minutes' => 'sometimes|integer|min:5|max:1440',
            'complexity_level' => 'sometimes|in:simple,moderate,complex,high_risk',
            'anesthesia_type' => 'nullable|in:local,regional,general,sedation,none',
            'base_price' => 'sometimes|numeric|min:0|max:999999.99',
            'surgeon_fee' => 'nullable|numeric|min:0|max:999999.99',
            'anesthetist_fee' => 'nullable|numeric|min:0|max:999999.99',
            'theatre_fee' => 'nullable|numeric|min:0|max:999999.99',
            'consumables_cost' => 'nullable|numeric|min:0|max:999999.99',
            'nhif_rate' => 'nullable|numeric|min:0|max:999999.99',
            'status' => 'sometimes|in:active,inactive,deprecated',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $procedure->update($request->all());

        // Recalculate total cost if pricing changed
        if ($request->hasAny(['base_price', 'surgeon_fee', 'anesthetist_fee', 'theatre_fee', 'consumables_cost'])) {
            $procedure->calculateTotalCost();
            $procedure->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Procedure updated successfully',
            'data' => ['procedure' => $procedure]
        ]);
    }

    /**
     * Schedule a procedure in theatre
     */
    public function scheduleTheatre(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'procedure_id' => 'required|exists:procedures,id',
            'primary_surgeon_id' => 'required|exists:users,id',
            'anesthetist_id' => 'nullable|exists:users,id',
            'theatre_room_id' => 'required|exists:departments,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_start_time' => 'required|date_format:H:i',
            'priority' => 'required|in:elective,urgent,emergency',
            'session' => 'required|in:morning,afternoon,evening,night',
            'procedure_notes' => 'nullable|string|max:2000',
            'anesthesia_type' => 'nullable|in:local,regional,general,sedation,none',
            'special_requirements' => 'nullable|string|max:2000',
            'surgeon_notes' => 'nullable|string|max:2000',
            'assigned_staff' => 'nullable|array',
            'required_equipment' => 'nullable|array',
            'consumables_list' => 'nullable|array',
            'implants_list' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get procedure details
        $procedure = Procedure::findOrFail($request->procedure_id);
        $patient = Patient::findOrFail($request->patient_id);

        // Check for scheduling conflicts
        $conflictingSchedule = TheatreSchedule::where('theatre_room_id', $request->theatre_room_id)
            ->where('scheduled_date', $request->scheduled_date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($request) {
                $startTime = $request->scheduled_date . ' ' . $request->scheduled_start_time;
                $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' + ' . $request->estimated_duration_minutes . ' minutes'));

                $query->whereBetween('scheduled_start_time', [$startTime, $endTime])
                      ->orWhereBetween('scheduled_end_time', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('scheduled_start_time', '<=', $startTime)
                            ->where('scheduled_end_time', '>=', $endTime);
                      });
            })
            ->first();

        if ($conflictingSchedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Theatre room is already booked for the selected time slot',
                'data' => [
                    'conflicting_schedule' => $conflictingSchedule->load(['patient', 'procedure', 'primarySurgeon'])
                ]
            ], 409);
        }

        // Initialize variables
        $theatreSchedule = null;
        $totalCost = 0;

        DB::transaction(function () use ($request, $procedure, $patient, &$theatreSchedule, &$totalCost) {
            // Calculate scheduled end time
            $startTime = $request->scheduled_date . ' ' . $request->scheduled_start_time;
            $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' + ' . $procedure->estimated_duration_minutes . ' minutes'));

            // Generate schedule number
            $scheduleNumber = TheatreSchedule::generateScheduleNumber();

            // Create theatre schedule
            $theatreSchedule = TheatreSchedule::create([
                'schedule_number' => $scheduleNumber,
                'patient_id' => $request->patient_id,
                'procedure_id' => $request->procedure_id,
                'primary_surgeon_id' => $request->primary_surgeon_id,
                'anesthetist_id' => $request->anesthetist_id,
                'theatre_room_id' => $request->theatre_room_id,
                'encounter_id' => $request->encounter_id,
                'scheduled_date' => $request->scheduled_date,
                'scheduled_start_time' => $startTime,
                'scheduled_end_time' => $endTime,
                'estimated_duration_minutes' => $procedure->estimated_duration_minutes,
                'priority' => $request->priority,
                'session' => $request->session,
                'procedure_name' => $procedure->procedure_name,
                'procedure_notes' => $request->procedure_notes,
                'anesthesia_type' => $request->anesthesia_type ?? $procedure->anesthesia_type,
                'special_requirements' => $request->special_requirements,
                'surgeon_notes' => $request->surgeon_notes,
                'assigned_staff' => $request->assigned_staff,
                'required_equipment' => $request->required_equipment ?? $procedure->required_equipment,
                'consumables_list' => $request->consumables_list ?? $procedure->consumables_list,
                'implants_list' => $request->implants_list,
                'status' => 'scheduled',
                'requires_consent' => $procedure->requires_consent,
            ]);

            // Create consumables records if provided
            if ($request->has('consumables_list') && is_array($request->consumables_list)) {
                foreach ($request->consumables_list as $consumable) {
                    ProcedureConsumable::create([
                        'theatre_schedule_id' => $theatreSchedule->id,
                        'procedure_id' => $procedure->id,
                        'item_code' => $consumable['item_code'] ?? null,
                        'item_name' => $consumable['item_name'],
                        'item_type' => $consumable['item_type'] ?? 'consumable',
                        'planned_quantity' => $consumable['quantity'] ?? 1,
                        'unit_of_measure' => $consumable['unit'] ?? 'pieces',
                        'unit_cost' => $consumable['unit_cost'] ?? 0,
                        'unit_price' => $consumable['unit_price'] ?? 0,
                        'billable' => $consumable['billable'] ?? true,
                        'usage_status' => 'planned',
                    ]);
                }
            }

            // Calculate total procedure cost
            $theatreSchedule->calculateTotalCost();
            $theatreSchedule->save();
            $totalCost = $theatreSchedule->total_procedure_cost;
        });

        $theatreSchedule->load(['patient', 'procedure', 'primarySurgeon', 'anesthetist', 'theatreRoom', 'procedureConsumables']);

        return response()->json([
            'status' => 'success',
            'message' => 'Procedure scheduled successfully',
            'data' => [
                'theatre_schedule' => $theatreSchedule,
                'total_cost' => $totalCost,
                'priority_config' => $theatreSchedule->priority_config,
                'is_ready' => $theatreSchedule->is_ready,
            ]
        ], 201);
    }

    /**
     * Get theatre schedule/worklist
     */
    public function theatreSchedule(Request $request)
    {
        $query = TheatreSchedule::with(['patient', 'procedure', 'primarySurgeon', 'anesthetist', 'theatreRoom']);

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('scheduled_date', $request->date);
        } else {
            $query->today(); // Default to today
        }

        // Filter by theatre room
        if ($request->has('theatre_room_id')) {
            $query->byTheatre($request->theatre_room_id);
        }

        // Filter by surgeon
        if ($request->has('surgeon_id')) {
            $query->bySurgeon($request->surgeon_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Show only upcoming schedules
        if ($request->boolean('upcoming_only')) {
            $query->upcoming();
        }

        // Show only ready schedules
        if ($request->boolean('ready_only')) {
            $query->ready();
        }

        // Show overdue schedules
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        $schedules = $query->byPriority()->get();

        // Group by status for better workflow management
        $theatreSchedule = [
            'scheduled' => $schedules->where('status', 'scheduled')->values(),
            'confirmed' => $schedules->where('status', 'confirmed')->values(),
            'in_progress' => $schedules->where('status', 'in_progress')->values(),
            'completed' => $schedules->where('status', 'completed')->values(),
            'ready_to_start' => $schedules->filter(fn($s) => $s->is_ready)->values(),
            'overdue' => $schedules->filter(fn($s) => $s->is_overdue)->values(),
            'statistics' => [
                'total_scheduled' => $schedules->count(),
                'emergency_count' => $schedules->where('priority', 'emergency')->count(),
                'urgent_count' => $schedules->where('priority', 'urgent')->count(),
                'elective_count' => $schedules->where('priority', 'elective')->count(),
                'ready_count' => $schedules->filter(fn($s) => $s->is_ready)->count(),
                'overdue_count' => $schedules->filter(fn($s) => $s->is_overdue)->count(),
                'in_progress_count' => $schedules->where('status', 'in_progress')->count(),
                'completed_count' => $schedules->where('status', 'completed')->count(),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $theatreSchedule
        ]);
    }

    /**
     * Update theatre schedule status and workflow
     */
    public function updateTheatreSchedule(Request $request, TheatreSchedule $theatreSchedule)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:scheduled,confirmed,in_progress,completed,cancelled,postponed',
            'pre_op_completed' => 'boolean',
            'consent_obtained' => 'boolean',
            'equipment_ready' => 'boolean',
            'consumables_ready' => 'boolean',
            'actual_start_time' => 'nullable|date',
            'actual_end_time' => 'nullable|date|after:actual_start_time',
            'complications' => 'nullable|string|max:2000',
            'outcome' => 'nullable|in:successful,complicated,failed',
            'outcome_notes' => 'nullable|string|max:2000',
            'post_op_instructions' => 'nullable|string|max:2000',
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'status', 'pre_op_completed', 'consent_obtained', 'equipment_ready',
            'consumables_ready', 'actual_start_time', 'actual_end_time',
            'complications', 'outcome', 'outcome_notes', 'post_op_instructions'
        ]);

        // Handle status-specific updates
        if ($request->has('status')) {
            switch ($request->status) {
                case 'in_progress':
                    $updateData['actual_start_time'] = $updateData['actual_start_time'] ?? now();
                    break;
                case 'completed':
                    $updateData['actual_end_time'] = $updateData['actual_end_time'] ?? now();
                    if ($updateData['actual_start_time'] && $updateData['actual_end_time']) {
                        $startTime = new \DateTime($updateData['actual_start_time']);
                        $endTime = new \DateTime($updateData['actual_end_time']);
                        $updateData['actual_duration_minutes'] = $endTime->diff($startTime)->i + ($endTime->diff($startTime)->h * 60);
                    }
                    break;
                case 'cancelled':
                    $updateData['cancelled_by'] = $request->user()->id;
                    $updateData['cancelled_at'] = now();
                    $updateData['cancellation_reason'] = $request->cancellation_reason;
                    break;
            }
        }

        // Handle workflow completions
        if ($request->has('pre_op_completed') && $request->pre_op_completed) {
            $updateData['pre_op_completed_at'] = now();
            $updateData['pre_op_completed_by'] = $request->user()->id;
        }

        if ($request->has('consent_obtained') && $request->consent_obtained) {
            $updateData['consent_obtained_at'] = now();
            $updateData['consent_obtained_by'] = $request->user()->id;
        }

        if ($request->has('equipment_ready') && $request->equipment_ready) {
            $updateData['equipment_checked_at'] = now();
            $updateData['equipment_checked_by'] = $request->user()->id;
        }

        $theatreSchedule->update($updateData);

        // Auto-post charges when procedure is completed
        if ($request->status === 'completed' && !$theatreSchedule->charges_posted) {
            $theatreSchedule->update([
                'charges_posted' => true,
                'charges_posted_at' => now(),
                'charges_posted_by' => $request->user()->id,
            ]);
        }

        $theatreSchedule->load(['patient', 'procedure', 'primarySurgeon', 'anesthetist', 'theatreRoom']);

        return response()->json([
            'status' => 'success',
            'message' => 'Theatre schedule updated successfully',
            'data' => [
                'theatre_schedule' => $theatreSchedule,
                'is_ready' => $theatreSchedule->is_ready,
                'is_overdue' => $theatreSchedule->is_overdue,
            ]
        ]);
    }

    /**
     * Get procedure statistics and analytics
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $query = TheatreSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo]);

        $statistics = [
            'total_procedures' => $query->count(),
            'completed_procedures' => $query->where('status', 'completed')->count(),
            'cancelled_procedures' => $query->where('status', 'cancelled')->count(),
            'by_priority' => [
                'emergency' => $query->where('priority', 'emergency')->count(),
                'urgent' => $query->where('priority', 'urgent')->count(),
                'elective' => $query->where('priority', 'elective')->count(),
            ],
            'by_outcome' => [
                'successful' => $query->where('outcome', 'successful')->count(),
                'complicated' => $query->where('outcome', 'complicated')->count(),
                'failed' => $query->where('outcome', 'failed')->count(),
            ],
            'average_duration' => $query->whereNotNull('actual_duration_minutes')->avg('actual_duration_minutes'),
            'total_revenue' => $query->where('charges_posted', true)->sum('total_procedure_cost'),
            'utilization_rate' => $this->calculateTheatreUtilization($dateFrom, $dateTo),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'statistics' => $statistics,
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ]
            ]
        ]);
    }

    /**
     * Calculate theatre utilization rate
     */
    private function calculateTheatreUtilization($dateFrom, $dateTo)
    {
        $totalScheduledMinutes = TheatreSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
            ->sum('estimated_duration_minutes');

        $totalActualMinutes = TheatreSchedule::whereBetween('scheduled_date', [$dateFrom, $dateTo])
            ->whereNotNull('actual_duration_minutes')
            ->sum('actual_duration_minutes');

        return $totalScheduledMinutes > 0 ? round(($totalActualMinutes / $totalScheduledMinutes) * 100, 2) : 0;
    }
}
