<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Triage;
use App\Models\Consultation;
use Carbon\Carbon;

class OpdController extends Controller
{
    /**
     * Return summary for OPD module (worklist, quick stats)
     */
    public function index(Request $request)
    {
        // Return aggregated OPD dashboard data
        $today = Carbon::today()->toDateString();

        $appointmentsToday = Appointment::forDate($today)->count();
        $appointmentsUpcoming = Appointment::forDate($today)->active()->orderBy('appointment_time')->limit(10)->get();

        $waitingTriage = Triage::today()->waiting()->count();
        $triageList = Triage::today()->byPriority()->limit(10)->get();

        $pendingConsultations = Consultation::whereDate('created_at', $today)
            ->where('status', '!=', 'completed')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        $data = [
            'module' => 'opd',
            'description' => 'Outpatient department dashboard',
            'today' => [
                'appointments_count' => $appointmentsToday,
                'waiting_triage_count' => $waitingTriage,
                'pending_consultations_count' => Consultation::whereDate('created_at', $today)->where('status', '!=', 'completed')->count(),
            ],
            'lists' => [
                'upcoming_appointments' => $appointmentsUpcoming,
                'triage_list' => $triageList,
                'pending_consultations' => $pendingConsultations,
            ],
            'endpoints' => [
                'patients' => '/api/patients',
                'appointments' => '/api/appointments',
                'consultations' => '/api/consultations',
                'prescriptions' => '/api/prescriptions',
                'bills' => '/api/bills',
            ]
        ];

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Return worklist (simple stub)
     */
    public function worklist(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $appointments = Appointment::forDate($today)->active()->orderBy('appointment_time')->get();
        $triages = Triage::today()->waiting()->byPriority()->get();
        $consultations = Consultation::whereDate('created_at', $today)->where('status', '!=', 'completed')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'appointments' => $appointments,
                'triages' => $triages,
                'consultations' => $consultations,
            ]
        ]);
    }
}
