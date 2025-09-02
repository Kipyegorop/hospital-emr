<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Models\LabTest;
use App\Models\Bill;
use App\Models\NhifClaim;
use App\Models\Medication;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview
     */
    public function index()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $stats = [
            'today' => [
                'appointments' => Appointment::whereDate('appointment_date', $today)->count(),
                'consultations' => Consultation::whereDate('created_at', $today)->count(),
                'new_patients' => Patient::whereDate('created_at', $today)->count(),
                'prescriptions' => Prescription::whereDate('created_at', $today)->count(),
                'lab_tests' => LabTest::whereDate('created_at', $today)->count(),
                'bills' => Bill::whereDate('created_at', $today)->count(),
            ],
            'this_month' => [
                'appointments' => Appointment::whereMonth('appointment_date', $thisMonth->month)->count(),
                'consultations' => Consultation::whereMonth('created_at', $thisMonth->month)->count(),
                'new_patients' => Patient::whereMonth('created_at', $thisMonth->month)->count(),
                'prescriptions' => Prescription::whereMonth('created_at', $thisMonth->month)->count(),
                'lab_tests' => LabTest::whereMonth('created_at', $thisMonth->month)->count(),
                'bills' => Bill::whereMonth('created_at', $thisMonth->month)->count(),
                'revenue' => Bill::whereMonth('created_at', $thisMonth->month)->sum('total_amount'),
            ],
            'last_month' => [
                'appointments' => Appointment::whereMonth('appointment_date', $lastMonth->month)->count(),
                'consultations' => Consultation::whereMonth('created_at', $lastMonth->month)->count(),
                'new_patients' => Patient::whereMonth('created_at', $lastMonth->month)->count(),
                'prescriptions' => Prescription::whereMonth('created_at', $lastMonth->month)->count(),
                'lab_tests' => LabTest::whereMonth('created_at', $lastMonth->month)->count(),
                'bills' => Bill::whereMonth('created_at', $lastMonth->month)->count(),
                'revenue' => Bill::whereMonth('created_at', $lastMonth->month)->sum('total_amount'),
            ],
            'total' => [
                'patients' => Patient::count(),
                'users' => User::count(),
                'departments' => Department::count(),
                'wards' => Ward::count(),
                'beds' => Bed::count(),
                'medications' => Medication::count(),
            ],
            'pending' => [
                'appointments' => Appointment::where('status', 'scheduled')->count(),
                'consultations' => Consultation::where('status', 'in_progress')->count(),
                'prescriptions' => Prescription::where('dispensing_status', 'pending')->count(),
                'lab_tests' => LabTest::where('status', 'requested')->count(),
                'bills' => Bill::where('payment_status', 'pending')->count(),
                'nhif_claims' => NhifClaim::where('status', 'submitted')->count(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Get detailed statistics
     */
    public function stats()
    {
        $stats = [
            'patient_demographics' => [
                'gender_distribution' => Patient::selectRaw('gender, COUNT(*) as count')
                    ->groupBy('gender')
                    ->get(),
                'age_distribution' => [
                    '0-18' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= 18')->count(),
                    '19-30' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 19 AND 30')->count(),
                    '31-50' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50')->count(),
                    '51-65' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 65')->count(),
                    '65+' => Patient::whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 65')->count(),
                ],
                'payment_methods' => Patient::selectRaw('payment_method, COUNT(*) as count')
                    ->groupBy('payment_method')
                    ->get(),
            ],
            'appointment_trends' => [
                'daily' => Appointment::selectRaw('DATE(appointment_date) as date, COUNT(*) as count')
                    ->whereBetween('appointment_date', [Carbon::now()->subDays(30), Carbon::now()])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'by_department' => Appointment::with('department')
                    ->selectRaw('department_id, COUNT(*) as count')
                    ->groupBy('department_id')
                    ->get(),
                'by_status' => Appointment::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get(),
            ],
            'revenue_analytics' => [
                'monthly' => Bill::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
                    ->whereYear('created_at', Carbon::now()->year)
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get(),
                'by_payment_method' => Bill::selectRaw('payment_method, SUM(total_amount) as total')
                    ->groupBy('payment_method')
                    ->get(),
                'outstanding_amounts' => Bill::where('payment_status', '!=', 'paid')->sum('balance_due'),
            ],
            'ward_occupancy' => [
                'total_beds' => Bed::count(),
                'occupied_beds' => Bed::where('status', 'occupied')->count(),
                'available_beds' => Bed::where('status', 'available')->count(),
                'by_ward' => Ward::withCount(['beds as total_beds', 'beds as occupied_beds' => function($query) {
                    $query->where('status', 'occupied');
                }])->get(),
            ],
            'medication_inventory' => [
                'total_medications' => Medication::count(),
                'low_stock' => Medication::where('current_stock', '<=', DB::raw('minimum_stock'))->count(),
                'expiring_soon' => Medication::where('expiry_date', '<=', Carbon::now()->addMonths(3))->count(),
                'out_of_stock' => Medication::where('current_stock', 0)->count(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Get patient report
     */
    public function patientReport(Request $request)
    {
        $query = Patient::query();

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Gender filter
        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        $patients = $query->with(['appointments', 'consultations', 'bills'])
            ->get()
            ->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => $patient->full_name,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'phone' => $patient->phone,
                    'nhif_number' => $patient->nhif_number,
                    'status' => $patient->status,
                    'created_at' => $patient->created_at,
                    'total_appointments' => $patient->appointments->count(),
                    'total_consultations' => $patient->consultations->count(),
                    'total_bills' => $patient->bills->count(),
                    'outstanding_amount' => $patient->bills->where('payment_status', '!=', 'paid')->sum('balance_due'),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $patients
        ]);
    }

    /**
     * Get appointment report
     */
    public function appointmentReport(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor', 'department']);

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('appointment_date', [$request->start_date, $request->end_date]);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Department filter
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $appointments = $query->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient->full_name,
                    'patient_number' => $appointment->patient->patient_number,
                    'doctor_name' => $appointment->doctor->name,
                    'department' => $appointment->department->name,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'type' => $appointment->appointment_type,
                    'status' => $appointment->status,
                    'payment_status' => $appointment->payment_status,
                    'consultation_fee' => $appointment->consultation_fee,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $appointments
        ]);
    }

    /**
     * Get revenue report
     */
    public function revenueReport(Request $request)
    {
        $query = Bill::query();

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Payment status filter
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $bills = $query->with(['patient'])
            ->get()
            ->map(function ($bill) {
                return [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'patient_name' => $bill->patient->full_name,
                    'patient_number' => $bill->patient->patient_number,
                    'bill_type' => $bill->bill_type,
                    'subtotal' => $bill->subtotal,
                    'tax_amount' => $bill->tax_amount,
                    'discount_amount' => $bill->discount_amount,
                    'total_amount' => $bill->total_amount,
                    'amount_paid' => $bill->amount_paid,
                    'balance_due' => $bill->balance_due,
                    'payment_method' => $bill->payment_method,
                    'payment_status' => $bill->payment_status,
                    'created_at' => $bill->created_at,
                ];
            });

        $summary = [
            'total_bills' => $bills->count(),
            'total_revenue' => $bills->sum('total_amount'),
            'total_collected' => $bills->sum('amount_paid'),
            'total_outstanding' => $bills->sum('balance_due'),
            'by_payment_method' => $bills->groupBy('payment_method')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total' => $group->sum('total_amount'),
                    ];
                }),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'bills' => $bills,
                'summary' => $summary,
            ]
        ]);
    }

    /**
     * Get NHIF report
     */
    public function nhifReport(Request $request)
    {
        $query = NhifClaim::with(['patient', 'bill']);

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $claims = $query->get()
            ->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'claim_number' => $claim->claim_number,
                    'patient_name' => $claim->patient->full_name,
                    'patient_number' => $claim->patient->patient_number,
                    'nhif_number' => $claim->nhif_number,
                    'claim_type' => $claim->claim_type,
                    'total_bill_amount' => $claim->total_bill_amount,
                    'nhif_coverable_amount' => $claim->nhif_coverable_amount,
                    'patient_contribution' => $claim->patient_contribution,
                    'nhif_payable_amount' => $claim->nhif_payable_amount,
                    'amount_paid_by_nhif' => $claim->amount_paid_by_nhif,
                    'outstanding_amount' => $claim->outstanding_amount,
                    'status' => $claim->status,
                    'submitted_at' => $claim->submitted_at,
                    'expected_payment_date' => $claim->expected_payment_date,
                ];
            });

        $summary = [
            'total_claims' => $claims->count(),
            'total_amount_claimed' => $claims->sum('nhif_payable_amount'),
            'total_amount_paid' => $claims->sum('amount_paid_by_nhif'),
            'total_outstanding' => $claims->sum('outstanding_amount'),
            'by_status' => $claims->groupBy('status')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_amount' => $group->sum('nhif_payable_amount'),
                    ];
                }),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'claims' => $claims,
                'summary' => $summary,
            ]
        ]);
    }

    /**
     * Get medication report
     */
    public function medicationReport(Request $request)
    {
        $query = Medication::query();

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Therapeutic class filter
        if ($request->has('therapeutic_class')) {
            $query->where('therapeutic_class', $request->therapeutic_class);
        }

        $medications = $query->get()
            ->map(function ($medication) {
                return [
                    'id' => $medication->id,
                    'medication_code' => $medication->medication_code,
                    'name' => $medication->name,
                    'generic_name' => $medication->generic_name,
                    'dosage_form' => $medication->dosage_form,
                    'strength' => $medication->strength,
                    'therapeutic_class' => $medication->therapeutic_class,
                    'current_stock' => $medication->current_stock,
                    'minimum_stock' => $medication->minimum_stock,
                    'unit_cost' => $medication->unit_cost,
                    'selling_price' => $medication->selling_price,
                    'nhif_price' => $medication->nhif_price,
                    'expiry_date' => $medication->expiry_date,
                    'status' => $medication->status,
                    'is_available' => $medication->is_available,
                ];
            });

        $summary = [
            'total_medications' => $medications->count(),
            'low_stock_count' => $medications->where('current_stock', '<=', 'minimum_stock')->count(),
            'out_of_stock_count' => $medications->where('current_stock', 0)->count(),
            'expiring_soon_count' => $medications->where('expiry_date', '<=', Carbon::now()->addMonths(3))->count(),
            'total_inventory_value' => $medications->sum(function ($med) {
                return $med['current_stock'] * $med['unit_cost'];
            }),
            'by_therapeutic_class' => $medications->groupBy('therapeutic_class')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_stock' => $group->sum('current_stock'),
                    ];
                }),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'medications' => $medications,
                'summary' => $summary,
            ]
        ]);
    }
}
