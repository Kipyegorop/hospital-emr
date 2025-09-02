<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $query = Consultation::with(['patient','doctor'])->orderBy('created_at','desc');
    if (request()->has('patient_id')) $query->where('patient_id', request('patient_id'));
    if (request()->has('doctor_id')) $query->where('doctor_id', request('doctor_id'));
    if (request()->has('status')) $query->where('status', request('status'));
    $consultations = $query->paginate(20);
    return response()->json(['status' => 'success', 'data' => $consultations]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'chief_complaint' => 'required|string',
            'consultation_type' => 'required|in:initial,follow_up,emergency,routine',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $consultation = Consultation::create(array_merge($validator->validated(), [
            'status' => 'in_progress'
        ]));

        return response()->json(['status' => 'success', 'data' => $consultation], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    $consultation = Consultation::with(['patient','doctor','appointment'])->findOrFail($id);
    return response()->json(['status' => 'success', 'data' => $consultation]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $consultation = Consultation::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'temperature' => 'nullable|numeric',
            'blood_pressure_systolic' => 'nullable|integer',
            'blood_pressure_diastolic' => 'nullable|integer',
            'heart_rate' => 'nullable|integer',
            'respiratory_rate' => 'nullable|integer',
            'height' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'bmi' => 'nullable|numeric',
            'chief_complaint' => 'nullable|string',
            'history_of_present_illness' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'assessment' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:in_progress,completed,pending_review'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $consultation->update($validator->validated());

        return response()->json(['status' => 'success', 'data' => $consultation]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }

    /**
     * Mark consultation complete and optionally update patient history
     */
    public function complete(Request $request, Consultation $consultation)
    {
        DB::transaction(function () use ($request, $consultation) {
            $consultation->status = 'completed';
            if ($request->filled('assessment')) $consultation->assessment = $request->assessment;
            if ($request->filled('treatment_plan')) $consultation->treatment_plan = $request->treatment_plan;
            if ($request->filled('notes')) $consultation->notes = $request->notes;
            $consultation->save();

            // Optionally append to patient medical history
            if ($request->filled('patient_history_update')) {
                $patient = $consultation->patient;
                $patient->medical_history = trim(($patient->medical_history ?? '') . "\n" . $request->patient_history_update);
                $patient->save();
            }
        });

        return response()->json(['status' => 'success', 'data' => $consultation]);
    }
}
