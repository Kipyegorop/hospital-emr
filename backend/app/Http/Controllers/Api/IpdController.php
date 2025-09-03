<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Encounter;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\Patient;
use App\Models\BedAssignment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class IpdController extends Controller
{
    /**
     * Return summary for IPD module (inpatient ward management)
     */
    public function index(Request $request)
    {
        // Provide IPD summary using real data
        $occupiedBeds = Bed::where('is_occupied', true)->count();
        $availableBeds = Bed::where('is_occupied', false)->count();
        $currentAdmissions = Encounter::where('encounter_type', 'inpatient')->where('status', 'active')->count();

        $wards = Ward::withCount('beds')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'module' => 'ipd',
                'description' => 'Inpatient department dashboard',
                'occupied_beds' => $occupiedBeds,
                'available_beds' => $availableBeds,
                'current_admissions' => $currentAdmissions,
                'wards' => $wards,
            ]
        ]);
    }

    /**
     * Return IPD dashboard stub
     */
    public function dashboard(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Admit a patient into a bed (create Encounter and assign Bed)
     */
    public function admit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'ward_id' => 'required|exists:wards,id',
            'bed_id' => 'required|exists:beds,id',
            'attending_doctor_id' => 'nullable|exists:users,id',
            'admission_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

    DB::transaction(function () use ($data, $request, &$encounter) {
            // Lock the chosen bed row to prevent concurrent assignment
            $bed = Bed::where('id', $data['bed_id'])->lockForUpdate()->first();

            if (!$bed) {
                throw new \Exception('Bed not found');
            }

            if ($bed->is_occupied) {
                throw new \Exception('Bed is already occupied');
            }

            // Create encounter
            $encounter = Encounter::create([
                'encounter_number' => Encounter::generateEncounterNumber(),
                'patient_id' => $data['patient_id'],
                'department_id' => null,
                'attending_doctor_id' => $data['attending_doctor_id'] ?? null,
                'encounter_type' => 'inpatient',
                'status' => 'active',
                'start_time' => now(),
                'chief_complaint' => $data['admission_reason'] ?? 'Admission',
            ]);

            // Mark bed occupied and create assignment record
            $bed->is_occupied = true;
            $bed->current_patient_id = $data['patient_id'];
            $bed->save();

            BedAssignment::create([
                'bed_id' => $bed->id,
                'patient_id' => $data['patient_id'],
                'encounter_id' => $encounter->id,
                'assigned_by' => $request->user()?->id,
                'started_at' => now(),
                'notes' => 'Admitted via IPD admit endpoint',
            ]);
        });

        $encounter->load('patient');

        return response()->json(['status' => 'success', 'data' => $encounter], 201);
    }

    /**
     * Transfer patient to another bed (update Bed occupancy)
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_bed_id' => 'required|exists:beds,id',
            'to_bed_id' => 'required|exists:beds,id',
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        DB::transaction(function () use ($request, &$fromBed, &$toBed) {
            // Lock both beds in a consistent order to avoid deadlocks
            $ids = [$request->from_bed_id, $request->to_bed_id];
            sort($ids);
            $beds = Bed::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $fromBed = $beds[$request->from_bed_id] ?? null;
            $toBed = $beds[$request->to_bed_id] ?? null;

            if (!$fromBed) throw new \Exception('Source bed not found');
            if (!$toBed) throw new \Exception('Target bed not found');

            if (!$fromBed->is_occupied) {
                throw new \Exception('Source bed is not occupied');
            }

            if ($toBed->is_occupied) {
                throw new \Exception('Target bed is already occupied');
            }

            // Perform transfer
            $toBed->is_occupied = true;
            $toBed->current_patient_id = $fromBed->current_patient_id;
            $toBed->save();

            // Close previous assignment(s) for fromBed
            BedAssignment::where('bed_id', $fromBed->id)->whereNull('ended_at')->update(['ended_at' => now(), 'notes' => 'Transferred out']);

            // Create new assignment for toBed
            BedAssignment::create([
                'bed_id' => $toBed->id,
                'patient_id' => $toBed->current_patient_id,
                'encounter_id' => null,
                'assigned_by' => $request->user()?->id,
                'started_at' => now(),
                'notes' => 'Transferred via IPD transfer endpoint',
            ]);

            $fromBed->is_occupied = false;
            $fromBed->current_patient_id = null;
            $fromBed->save();
        });

        return response()->json(['status' => 'success', 'data' => ['from' => $fromBed, 'to' => $toBed]]);
    }

    /**
     * Discharge a patient (end Encounter and free Bed)
     */
    public function discharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'encounter_id' => 'required|exists:encounters,id',
            'discharge_summary' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        $encounter = Encounter::findOrFail($request->encounter_id);

        DB::transaction(function () use ($encounter, $request, &$bed) {
            $encounter->status = 'completed';
            $encounter->end_time = now();
            $encounter->notes = ($encounter->notes ?? '') . "\nDischarge Summary:\n" . ($request->discharge_summary ?? '');
            $encounter->save();

            // Lock bed row if present and free it
            $bed = Bed::where('current_patient_id', $encounter->patient_id)->lockForUpdate()->first();
            if ($bed) {
                $bed->is_occupied = false;
                $bed->current_patient_id = null;
                $bed->save();

                // Close assignment record
                BedAssignment::where('bed_id', $bed->id)->where('patient_id', $encounter->patient_id)->whereNull('ended_at')
                    ->update(['ended_at' => now(), 'notes' => 'Discharged via IPD discharge endpoint']);
            }
        });

        return response()->json(['status' => 'success', 'data' => $encounter]);
    }
}
