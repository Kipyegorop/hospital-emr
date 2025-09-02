<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function index()
    {
        $query = Appointment::query();

        // optional filters: patient_id, doctor_id, department_id, date, status
        if (request()->has('patient_id')) $query->where('patient_id', request('patient_id'));
        if (request()->has('doctor_id')) $query->where('doctor_id', request('doctor_id'));
        if (request()->has('department_id')) $query->where('department_id', request('department_id'));
        if (request()->has('date')) $query->whereDate('appointment_date', request('date'));
        if (request()->has('status')) $query->where('status', request('status'));

        $appointments = $query->orderBy('appointment_date', 'asc')->orderBy('appointment_time', 'asc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $appointments
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
            'appointment_type' => 'required|in:consultation,follow_up,emergency,routine_checkup,specialist',
            'reason_for_visit' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        // check for conflicting appointment for same doctor and time
        $conflict = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->exists();

        if ($conflict) {
            return response()->json(['status' => 'error', 'message' => 'Doctor has another appointment at that time'], 409);
        }

        $appointment = null;
        DB::transaction(function () use ($request, &$appointment) {
            $appointment = Appointment::create(array_merge($request->only([
                'patient_id','doctor_id','department_id','appointment_date','appointment_time','estimated_duration','appointment_type','reason_for_visit'
            ]), [
                'status' => 'scheduled'
            ]));

            // Optionally assign a queue number (simple incremental per date)
            $date = $appointment->appointment_date->toDateString();
            $lastQueue = Appointment::whereDate('appointment_date', $date)->max('id');
            $appointment->queue_number = 'Q' . $appointment->id;
            $appointment->save();
        });

        return response()->json(['status' => 'success', 'data' => $appointment], 201);
    }

    public function show($id)
    {
    $appointment = Appointment::with(['patient','doctor','department'])->findOrFail($id);
    return response()->json(['status' => 'success', 'data' => $appointment]);
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'doctor_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'appointment_date' => 'nullable|date',
            'appointment_time' => 'nullable',
            'status' => 'nullable|in:scheduled,confirmed,in_progress,completed,cancelled,no_show',
            'reason_for_visit' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $appointment->update($validator->validated());

        return response()->json(['status' => 'success', 'data' => $appointment]);
    }

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }

    // Additional helpers
    public function doctorAppointments($doctor)
    {
        $appointments = Appointment::where('doctor_id', $doctor)->orderBy('appointment_date')->get();
        return response()->json(['status' => 'success', 'data' => $appointments]);
    }

    public function departmentAppointments($department)
    {
        $appointments = Appointment::where('department_id', $department)->orderBy('appointment_date')->get();
        return response()->json(['status' => 'success', 'data' => $appointments]);
    }

    public function queue($date)
    {
        $appointments = Appointment::whereDate('appointment_date', $date)->whereIn('status', ['scheduled','confirmed','in_progress'])->orderBy('queue_number')->get();
        return response()->json(['status' => 'success', 'data' => $appointments]);
    }

    public function checkIn($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->check_in_time = now();
        $appointment->status = 'in_progress';
        $appointment->save();
        return response()->json(['status' => 'success', 'data' => $appointment]);
    }

    public function checkOut($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->check_out_time = now();
        $appointment->status = 'completed';
        $appointment->save();
        return response()->json(['status' => 'success', 'data' => $appointment]);
    }

    public function cancel($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->status = 'cancelled';
        $appointment->save();
        return response()->json(['status' => 'success', 'data' => $appointment]);
    }
}

