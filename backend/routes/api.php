<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\TriageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProcedureController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\WardController;
use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\LabTestController;
use App\Http\Controllers\Api\MedicationController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\NhifClaimController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PharmacySaleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Public queue display for waiting area TV/monitors
Route::get('/public/queue-display', [TriageController::class, 'queueDisplay']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/update-profile', [AuthController::class, 'updateProfile']);
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Patients
    Route::apiResource('patients', PatientController::class);
    Route::post('/patients/check-duplicates', [PatientController::class, 'checkDuplicates']);
    Route::post('/patients/merge', [PatientController::class, 'merge']);
    Route::get('/patients/search/{query}', [PatientController::class, 'search']);
    Route::get('/patients/{patient}/history', [PatientController::class, 'history']);
    Route::get('/patients/{patient}/statistics', [PatientController::class, 'statistics']);

    // Triage & Queue Management
    Route::apiResource('triages', TriageController::class);
    Route::get('/triages/queue/display', [TriageController::class, 'queueDisplay']);
    Route::post('/triages/queue/call-next', [TriageController::class, 'callNext']);
    Route::get('/triages/queue/stats', [TriageController::class, 'queueStats']);

    // CPOE & Orders Management
    Route::apiResource('orders', OrderController::class);
    Route::get('/orders/worklist/{department_id}', [OrderController::class, 'worklist']);
    Route::patch('/orders/{order}/results', [OrderController::class, 'updateResults']);
    Route::get('/loinc/search', [OrderController::class, 'searchLoinc']);
    Route::get('/loinc/common', [OrderController::class, 'commonLoinc']);

    // Procedures & Theatre Management
    Route::apiResource('procedures', ProcedureController::class);
    Route::post('/procedures/schedule-theatre', [ProcedureController::class, 'scheduleTheatre']);
    Route::get('/theatre/schedule', [ProcedureController::class, 'theatreSchedule']);
    Route::patch('/theatre-schedules/{theatreSchedule}', [ProcedureController::class, 'updateTheatreSchedule']);
    Route::get('/procedures/statistics', [ProcedureController::class, 'statistics']);

    // Appointments
    Route::apiResource('appointments', AppointmentController::class);
    Route::get('/appointments/doctor/{doctor}', [AppointmentController::class, 'doctorAppointments']);
    Route::get('/appointments/department/{department}', [AppointmentController::class, 'departmentAppointments']);
    Route::get('/appointments/queue/{date}', [AppointmentController::class, 'queue']);
    Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);
    Route::post('/appointments/{appointment}/check-out', [AppointmentController::class, 'checkOut']);
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    
    // Consultations
    Route::apiResource('consultations', ConsultationController::class);
    Route::get('/consultations/patient/{patient}', [ConsultationController::class, 'patientConsultations']);
    Route::get('/consultations/doctor/{doctor}', [ConsultationController::class, 'doctorConsultations']);
    Route::post('/consultations/{consultation}/complete', [ConsultationController::class, 'complete']);
    
    // Prescriptions & Pharmacy Workflow (Kenya-Ready)
    Route::apiResource('prescriptions', PrescriptionController::class);
    Route::post('/prescriptions/{prescription}/request-exception', [PrescriptionController::class, 'requestException']);
    Route::post('/prescription-exceptions/{exception}/respond', [PrescriptionController::class, 'respondToException']);
    Route::post('/prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense']);
    Route::get('/pharmacy/worklist', [PrescriptionController::class, 'pharmacyWorklist']);
    Route::get('/pharmacy/exceptions', [PrescriptionController::class, 'exceptions']);
    Route::get('/pharmacy/sales-report', [PrescriptionController::class, 'salesReport']);
    
    // Wards
    Route::apiResource('wards', WardController::class);
    Route::get('/wards/department/{department}', [WardController::class, 'departmentWards']);
    Route::get('/wards/{ward}/beds', [WardController::class, 'beds']);
    Route::get('/wards/{ward}/statistics', [WardController::class, 'statistics']);
    
    // Beds
    Route::apiResource('beds', BedController::class);
    Route::get('/beds/available', [BedController::class, 'available']);
    Route::get('/beds/ward/{ward}', [BedController::class, 'wardBeds']);
    Route::post('/beds/{bed}/assign', [BedController::class, 'assign']);
    Route::post('/beds/{bed}/vacate', [BedController::class, 'vacate']);

    // Vitals / Nursing
    Route::post('/vitals', [\App\Http\Controllers\Api\VitalController::class, 'store']);
    Route::post('/nursing-assessments', [\App\Http\Controllers\Api\NursingAssessmentController::class, 'store']);
    Route::post('/medication-administrations', [\App\Http\Controllers\Api\MedicationAdministrationController::class, 'store']);
    
    // Lab Tests
    Route::apiResource('lab-tests', LabTestController::class);
    Route::get('/lab-tests/patient/{patient}', [LabTestController::class, 'patientTests']);
    Route::get('/lab-tests/requested-by/{user}', [LabTestController::class, 'requestedBy']);
    Route::post('/lab-tests/{labTest}/collect', [LabTestController::class, 'collect']);
    Route::post('/lab-tests/{labTest}/complete', [LabTestController::class, 'complete']);
    Route::post('/lab-tests/{labTest}/report', [LabTestController::class, 'report']);
    
    // Medications
    Route::apiResource('medications', MedicationController::class);
    Route::get('/medications/search/{query}', [MedicationController::class, 'search']);
    Route::get('/medications/low-stock', [MedicationController::class, 'lowStock']);
    Route::get('/medications/expiring', [MedicationController::class, 'expiring']);
    Route::post('/medications/{medication}/adjust-stock', [MedicationController::class, 'adjustStock']);
    
    // Pharmacy sales returns
    Route::post('/pharmacy-sales/{pharmacySale}/return', [PharmacySaleController::class, 'returnSale']);
    
    // OPD & IPD module entry points
    Route::get('/opd', [\App\Http\Controllers\Api\OpdController::class, 'index']);
    Route::get('/opd/worklist', [\App\Http\Controllers\Api\OpdController::class, 'worklist']);

    // IPD dashboard is readable by clinical staff
    Route::get('/ipd', [\App\Http\Controllers\Api\IpdController::class, 'index']);
    Route::get('/ipd/dashboard', [\App\Http\Controllers\Api\IpdController::class, 'dashboard']);

    // IPD clinical actions - restrict to nurse, doctor, admin, super_admin
    Route::middleware('role:nurse,doctor,admin,super_admin')->group(function () {
        Route::post('/ipd/admit', [\App\Http\Controllers\Api\IpdController::class, 'admit']);
        Route::post('/ipd/transfer', [\App\Http\Controllers\Api\IpdController::class, 'transfer']);
        Route::post('/ipd/discharge', [\App\Http\Controllers\Api\IpdController::class, 'discharge']);
    });
    
    // Bills
    Route::apiResource('bills', BillController::class);
    Route::get('/bills/patient/{patient}', [BillController::class, 'patientBills']);
    Route::get('/bills/unpaid', [BillController::class, 'unpaid']);
    Route::post('/bills/{bill}/pay', [BillController::class, 'pay']);
    Route::post('/bills/{bill}/waive', [BillController::class, 'waive']);
    Route::get('/bills/{bill}/receipt', [BillController::class, 'receipt']);
    
    // NHIF Claims
    Route::apiResource('nhif-claims', NhifClaimController::class);
    Route::get('/nhif-claims/patient/{patient}', [NhifClaimController::class, 'patientClaims']);
    Route::get('/nhif-claims/status/{status}', [NhifClaimController::class, 'byStatus']);
    Route::post('/nhif-claims/{nhifClaim}/submit', [NhifClaimController::class, 'submit']);
    Route::post('/nhif-claims/{nhifClaim}/approve', [NhifClaimController::class, 'approve']);
    Route::post('/nhif-claims/{nhifClaim}/reject', [NhifClaimController::class, 'reject']);
    
    // Users (Admin only)
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/users/{user}/activate', [UserController::class, 'activate']);
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    });
    
    // Roles (Admin only)
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);
    });
    
    // Departments (Admin only)
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::apiResource('departments', DepartmentController::class);
        Route::get('/departments/{department}/users', [DepartmentController::class, 'users']);
        Route::get('/departments/{department}/statistics', [DepartmentController::class, 'statistics']);
    });
    
    // Reports (Admin and Super Admin)
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/reports/patients', [DashboardController::class, 'patientReport']);
        Route::get('/reports/appointments', [DashboardController::class, 'appointmentReport']);
        Route::get('/reports/revenue', [DashboardController::class, 'revenueReport']);
        Route::get('/reports/nhif', [DashboardController::class, 'nhifReport']);
        Route::get('/reports/medications', [DashboardController::class, 'medicationReport']);
    });

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Api\SettingController::class, 'index']);
    Route::post('/settings', [\App\Http\Controllers\Api\SettingController::class, 'update']);
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found.',
        'status' => 404
    ], 404);
});
