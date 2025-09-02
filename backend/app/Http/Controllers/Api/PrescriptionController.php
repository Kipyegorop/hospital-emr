<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionException;
use App\Models\PharmacySale;
use App\Models\Bill;
use Illuminate\Support\Str;
use App\Models\Medication;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of prescriptions
     */
    public function index(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'medication', 'dispensedBy']);

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by dispensing status
        if ($request->has('dispensing_status')) {
            $query->where('dispensing_status', $request->dispensing_status);
        }

        // Filter by prescription type (OPD/IPD)
        if ($request->has('prescription_type')) {
            $query->where('prescription_type', $request->prescription_type);
        }

        // Filter by patient category
        if ($request->has('patient_category')) {
            $query->where('patient_category', $request->patient_category);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('prescribed_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('prescribed_date', '<=', $request->date_to);
        }

        // Show only prescriptions with exceptions
        if ($request->boolean('with_exceptions')) {
            $query->withExceptions();
        }

        // Show only locked prescriptions
        if ($request->boolean('locked_only')) {
            $query->quantityLocked();
        }

        $prescriptions = $query->latest('prescribed_date')->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => [
                'prescriptions' => $prescriptions->items(),
                'pagination' => [
                    'current_page' => $prescriptions->currentPage(),
                    'last_page' => $prescriptions->lastPage(),
                    'per_page' => $prescriptions->perPage(),
                    'total' => $prescriptions->total(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created prescription
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'medication_id' => 'required|exists:medications,id',
            'medication_name' => 'required|string|max:255',
            'dosage_form' => 'required|string|max:100',
            'strength' => 'required|string|max:100',
            'dosage_instructions' => 'required|string|max:500',
            'quantity_prescribed' => 'required|integer|min:1|max:1000',
            'unit' => 'required|string|max:50',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'frequency' => 'required|string|max:100',
            'prescription_type' => 'required|in:opd,ipd,emergency',
            'patient_category' => 'required|in:cash,nhif,insurance,staff,waiver',
            'special_instructions' => 'nullable|string|max:1000',
            'side_effects_warning' => 'nullable|string|max:1000',
            'requires_refrigeration' => 'boolean',
            'quantity_locked' => 'boolean', // Allow override for special cases
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get medication details for pricing
        $medication = Medication::findOrFail($request->medication_id);

        DB::transaction(function () use ($request, $medication, &$prescription) {
            // Generate prescription number
            $prescriptionNumber = Prescription::generatePrescriptionNumber();

            // Create prescription with locked quantity by default (Kenya requirement)
            $prescription = Prescription::create([
                'prescription_number' => $prescriptionNumber,
                'patient_id' => $request->patient_id,
                'consultation_id' => $request->consultation_id,
                'doctor_id' => $request->user()->id,
                'medication_id' => $request->medication_id,
                'medication_name' => $request->medication_name,
                'generic_name' => $medication->generic_name,
                'dosage_form' => $request->dosage_form,
                'strength' => $request->strength,
                'dosage_instructions' => $request->dosage_instructions,
                'quantity_prescribed' => $request->quantity_prescribed,
                'quantity_locked' => $request->get('quantity_locked', true), // Locked by default
                'unit' => $request->unit,
                'duration_days' => $request->duration_days,
                'frequency' => $request->frequency,
                'status' => 'active',
                'prescribed_date' => now()->toDateString(),
                'expiry_date' => now()->addDays(30)->toDateString(), // 30 days validity
                'special_instructions' => $request->special_instructions,
                'side_effects_warning' => $request->side_effects_warning,
                'requires_refrigeration' => $request->boolean('requires_refrigeration'),
                'dispensing_status' => 'pending',
                'prescription_type' => $request->prescription_type,
                'patient_category' => $request->patient_category,
                'unit_price' => $medication->selling_price,
                'opd_price' => $medication->selling_price, // Can be different
                'ipd_price' => $medication->selling_price * 0.9, // 10% discount for IPD
                'nhif_price' => $medication->nhif_price,
            ]);

            // Calculate total cost
            $prescription->calculateTotalCost();
            $prescription->save();
        });

        $prescription->load(['patient', 'doctor', 'medication']);

        return response()->json([
            'status' => 'success',
            'message' => 'Prescription created successfully',
            'data' => [
                'prescription' => $prescription,
                'quantity_locked' => $prescription->quantity_locked,
                'applicable_price' => $prescription->applicable_price,
            ]
        ], 201);
    }

    /**
     * Display the specified prescription
     */
    public function show(Prescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'medication', 'dispensedBy', 'exceptions', 'pharmacySales']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'prescription' => $prescription,
                'can_modify_quantity' => $prescription->canModifyQuantity(),
                'is_expired' => $prescription->is_expired,
                'is_fully_dispensed' => $prescription->is_fully_dispensed,
                'remaining_quantity' => $prescription->remaining_quantity,
                'applicable_price' => $prescription->applicable_price,
            ]
        ]);
    }

    /**
     * Request exception for locked prescription (Kenya requirement)
     */
    public function requestException(Request $request, Prescription $prescription)
    {
        $validator = Validator::make($request->all(), [
            'exception_type' => 'required|in:quantity_change,substitution,dosage_change,other',
            'reason_for_exception' => 'required|string|max:2000',
            'requested_quantity' => 'required_if:exception_type,quantity_change|integer|min:1|max:1000',
            'requested_medication' => 'required_if:exception_type,substitution|string|max:255',
            'requested_dosage' => 'required_if:exception_type,dosage_change|string|max:500',
            'pharmacist_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if prescription is locked and requires exception
        if (!$prescription->quantity_locked && $request->exception_type === 'quantity_change') {
            return response()->json([
                'status' => 'error',
                'message' => 'Prescription quantity is not locked, no exception required'
            ], 400);
        }

        // Check if there's already a pending exception
        $existingException = $prescription->exceptions()
            ->where('status', 'pending')
            ->where('exception_type', $request->exception_type)
            ->first();

        if ($existingException) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is already a pending exception request for this prescription'
            ], 409);
        }

        DB::transaction(function () use ($request, $prescription, &$exception) {
            // Create exception request
            $exception = PrescriptionException::create([
                'prescription_id' => $prescription->id,
                'requested_by_pharmacist_id' => $request->user()->id,
                'exception_type' => $request->exception_type,
                'reason_for_exception' => $request->reason_for_exception,
                'pharmacist_notes' => $request->pharmacist_notes,
                'original_quantity' => $prescription->quantity_prescribed,
                'requested_quantity' => $request->requested_quantity ?? $prescription->quantity_prescribed,
                'original_medication' => $prescription->medication_name,
                'requested_medication' => $request->requested_medication ?? $prescription->medication_name,
                'original_dosage' => $prescription->dosage_instructions,
                'requested_dosage' => $request->requested_dosage ?? $prescription->dosage_instructions,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Update prescription exception status
            $prescription->update([
                'has_exception_request' => true,
                'exception_status' => 'pending',
            ]);

            // Add audit log
            $exception->addAuditLog('exception_requested', [
                'exception_type' => $request->exception_type,
                'reason' => $request->reason_for_exception,
                'requested_by' => $request->user()->name,
            ], $request->user()->id);
        });

        $exception->load(['prescription', 'requestedByPharmacist']);

        return response()->json([
            'status' => 'success',
            'message' => 'Exception request submitted successfully',
            'data' => [
                'exception' => $exception,
                'prescription' => $prescription,
            ]
        ], 201);
    }

    /**
     * Approve or reject exception request (Doctor only)
     */
    public function respondToException(Request $request, PrescriptionException $exception)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'doctor_response' => 'required|string|max:1000',
            'rejection_reason' => 'required_if:action,reject|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify the user is the prescribing doctor or has appropriate permissions
        if ($exception->prescription->doctor_id !== $request->user()->id &&
            !$request->user()->hasRole(['super_admin', 'doctor'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to respond to this exception'
            ], 403);
        }

        if ($exception->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception has already been responded to'
            ], 400);
        }

        DB::transaction(function () use ($request, $exception) {
            $status = $request->action === 'approve' ? 'approved' : 'rejected';

            // Update exception
            $exception->update([
                'status' => $status,
                'approved_by_doctor_id' => $request->user()->id,
                'responded_at' => now(),
                'doctor_response' => $request->doctor_response,
                'rejection_reason' => $request->action === 'reject' ? $request->rejection_reason : null,
            ]);

            // Update prescription
            $prescription = $exception->prescription;
            $prescription->update([
                'exception_status' => $status,
            ]);

            // If approved, apply the changes
            if ($status === 'approved') {
                switch ($exception->exception_type) {
                    case 'quantity_change':
                        $prescription->update([
                            'quantity_prescribed' => $exception->requested_quantity,
                            'quantity_remaining' => $exception->requested_quantity - $prescription->quantity_dispensed,
                        ]);
                        $prescription->calculateTotalCost();
                        $prescription->save();
                        break;
                    case 'substitution':
                        $prescription->update([
                            'medication_name' => $exception->requested_medication,
                        ]);
                        break;
                    case 'dosage_change':
                        $prescription->update([
                            'dosage_instructions' => $exception->requested_dosage,
                        ]);
                        break;
                }
            }

            // Add audit log
            $exception->addAuditLog('exception_' . $request->action . 'd', [
                'action' => $request->action,
                'response' => $request->doctor_response,
                'responded_by' => $request->user()->name,
            ], $request->user()->id);
        });

        $exception->load(['prescription', 'requestedByPharmacist', 'approvedByDoctor']);

        return response()->json([
            'status' => 'success',
            'message' => 'Exception ' . $request->action . 'd successfully',
            'data' => [
                'exception' => $exception,
                'prescription' => $exception->prescription,
            ]
        ]);
    }

    /**
     * Dispense prescription (Pharmacy workflow)
     */
    public function dispense(Request $request, Prescription $prescription)
    {
        $validator = Validator::make($request->all(), [
            'quantity_to_dispense' => 'required|integer|min:1',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
            'pharmacy_notes' => 'nullable|string|max:1000',
            'counseling_provided' => 'boolean',
            'counseling_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if prescription is active and not expired
        if ($prescription->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Prescription is not active'
            ], 400);
        }

        if ($prescription->is_expired) {
            return response()->json([
                'status' => 'error',
                'message' => 'Prescription has expired'
            ], 400);
        }

        // Check if quantity to dispense is valid
        $quantityToDispense = $request->quantity_to_dispense;
        $remainingQuantity = $prescription->remaining_quantity;

        if ($quantityToDispense > $remainingQuantity) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot dispense {$quantityToDispense} units. Only {$remainingQuantity} units remaining."
            ], 400);
        }

        // Check if prescription is locked and requires exception approval
        if ($prescription->quantity_locked && $prescription->exception_status !== 'approved' &&
            $quantityToDispense != $prescription->quantity_prescribed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Prescription quantity is locked. Exception approval required to modify quantity.'
            ], 400);
        }

        // Initialize variable
        $pharmacySale = null;

        DB::transaction(function () use ($request, $prescription, $quantityToDispense, &$pharmacySale) {
            // Update prescription dispensing information
            $newQuantityDispensed = $prescription->quantity_dispensed + $quantityToDispense;
            $newRemainingQuantity = $prescription->quantity_prescribed - $newQuantityDispensed;

            $dispensingStatus = $newRemainingQuantity > 0 ? 'partially_dispensed' : 'dispensed';

            $prescription->update([
                'quantity_dispensed' => $newQuantityDispensed,
                'quantity_remaining' => $newRemainingQuantity,
                'dispensing_status' => $dispensingStatus,
                'dispensed_at' => now(),
                'dispensed_by' => $request->user()->id,
                'pharmacy_notes' => $request->pharmacy_notes,
            ]);

            // Create pharmacy sale record
            $pharmacySale = PharmacySale::create([
                'sale_number' => PharmacySale::generateSaleNumber($prescription->prescription_type . '_prescription'),
                'patient_id' => $prescription->patient_id,
                'prescription_id' => $prescription->id,
                'medication_id' => $prescription->medication_id,
                'pharmacist_id' => $request->user()->id,
                'sale_type' => $prescription->prescription_type . '_prescription',
                'patient_category' => $prescription->patient_category,
                'medication_name' => $prescription->medication_name,
                'batch_number' => $request->batch_number,
                'expiry_date' => $request->expiry_date,
                'quantity_sold' => $quantityToDispense,
                'unit' => $prescription->unit,
                'unit_cost' => $prescription->medication->unit_cost ?? 0,
                'unit_price' => $prescription->applicable_price,
                'opd_price' => $prescription->opd_price,
                'ipd_price' => $prescription->ipd_price,
                'nhif_price' => $prescription->nhif_price,
                'total_amount' => $prescription->applicable_price * $quantityToDispense,
                'payment_method' => 'pending',
                'payment_status' => 'pending',
                'indication' => $prescription->dosage_instructions,
                'dosage_instructions' => $prescription->dosage_instructions,
                'pharmacist_counseling_notes' => $request->counseling_notes,
                'counseling_provided' => $request->boolean('counseling_provided'),
                'requires_prescription' => true,
                'sale_date' => now(),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Calculate profit margin
            $pharmacySale->calculateProfitMargin();
            $pharmacySale->save();

            // Update medication stock if linked
            if ($prescription->medication) {
                $medication = $prescription->medication;
                $medication->decrement('current_stock', $quantityToDispense);
            }

            // Create a bill for this pharmacy sale
            try {
                $items = [[
                    'type' => 'medication',
                    'reference' => $pharmacySale->id,
                    'name' => $pharmacySale->medication_name,
                    'quantity' => $pharmacySale->quantity_sold,
                    'unit_price' => $pharmacySale->unit_price,
                    'total' => $pharmacySale->total_amount,
                    'billing_code' => null,
                ]];

                $billData = [
                    'bill_number' => 'BILL-'.Str::upper(Str::random(8)),
                    'patient_id' => $prescription->patient_id,
                    'created_by' => $request->user()->id,
                    'bill_type' => 'medication',
                    'description' => 'Medication sale for prescription '.$prescription->prescription_number.' (Sale: '.$pharmacySale->sale_number.')',
                    'bill_date' => now()->toDateString(),
                    'subtotal' => $pharmacySale->total_amount,
                    'tax_amount' => 0.00,
                    'discount_amount' => 0.00,
                    'total_amount' => $pharmacySale->total_amount,
                    'amount_paid' => 0.00,
                    'balance_due' => $pharmacySale->total_amount,
                    'payment_method' => 'pending',
                    'payment_status' => 'pending',
                    'billable_items' => $items,
                    'prescription_id' => $prescription->id,
                    'status' => 'active',
                ];

                Bill::create($billData);
            } catch (\Exception $e) {
                // swallow bill creation errors to avoid breaking dispensing; log if available
                logger()->error('Failed to create bill for pharmacy sale: '.$e->getMessage());
            }
        });

        $prescription->load(['patient', 'doctor', 'medication', 'dispensedBy']);
        if ($pharmacySale) {
            $pharmacySale->load(['patient', 'medication', 'pharmacist']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Prescription dispensed successfully',
            'data' => [
                'prescription' => $prescription,
                'pharmacy_sale' => $pharmacySale,
                'dispensing_status' => $prescription->dispensing_status,
                'remaining_quantity' => $prescription->remaining_quantity,
            ]
        ]);
    }

    /**
     * Get pharmacy worklist (pending prescriptions)
     */
    public function pharmacyWorklist(Request $request)
    {
        $query = Prescription::with(['patient', 'doctor', 'medication'])
            ->where('dispensing_status', 'pending')
            ->where('status', 'active')
            ->where('expiry_date', '>=', now());

        // Filter by prescription type
        if ($request->has('prescription_type')) {
            $query->byType($request->prescription_type);
        }

        // Filter by patient category
        if ($request->has('patient_category')) {
            $query->byCategory($request->patient_category);
        }

        // Show only prescriptions with exceptions
        if ($request->boolean('exceptions_only')) {
            $query->withExceptions();
        }

        // Show only locked prescriptions
        if ($request->boolean('locked_only')) {
            $query->quantityLocked();
        }

        $prescriptions = $query->orderBy('prescribed_date')->get();

        // Group by type for better workflow management
        $worklist = [
            'opd_prescriptions' => $prescriptions->where('prescription_type', 'opd')->values(),
            'ipd_prescriptions' => $prescriptions->where('prescription_type', 'ipd')->values(),
            'emergency_prescriptions' => $prescriptions->where('prescription_type', 'emergency')->values(),
            'with_exceptions' => $prescriptions->where('has_exception_request', true)->values(),
            'locked_prescriptions' => $prescriptions->where('quantity_locked', true)->values(),
            'statistics' => [
                'total_pending' => $prescriptions->count(),
                'opd_count' => $prescriptions->where('prescription_type', 'opd')->count(),
                'ipd_count' => $prescriptions->where('prescription_type', 'ipd')->count(),
                'emergency_count' => $prescriptions->where('prescription_type', 'emergency')->count(),
                'with_exceptions_count' => $prescriptions->where('has_exception_request', true)->count(),
                'locked_count' => $prescriptions->where('quantity_locked', true)->count(),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $worklist
        ]);
    }

    /**
     * Get prescription exceptions (for doctors to review)
     */
    public function exceptions(Request $request)
    {
        $query = PrescriptionException::with(['prescription.patient', 'prescription.medication', 'requestedByPharmacist', 'approvedByDoctor']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->pending(); // Default to pending
        }

        // Filter by exception type
        if ($request->has('exception_type')) {
            $query->byType($request->exception_type);
        }

        // Filter by doctor (only their prescriptions)
        if ($request->has('doctor_id')) {
            $query->whereHas('prescription', function ($q) use ($request) {
                $q->where('doctor_id', $request->doctor_id);
            });
        }

        // Show overdue exceptions
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        $exceptions = $query->latest('requested_at')->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => [
                'exceptions' => $exceptions->items(),
                'pagination' => [
                    'current_page' => $exceptions->currentPage(),
                    'last_page' => $exceptions->lastPage(),
                    'per_page' => $exceptions->perPage(),
                    'total' => $exceptions->total(),
                ]
            ]
        ]);
    }

    /**
     * Get pharmacy sales report
     */
    public function salesReport(Request $request)
    {
        $query = PharmacySale::with(['patient', 'medication', 'pharmacist']);

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        } else {
            $query->today(); // Default to today
        }

        if ($request->has('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        // Filter by sale type
        if ($request->has('sale_type')) {
            $query->byType($request->sale_type);
        }

        // Filter by patient category
        if ($request->has('patient_category')) {
            $query->byCategory($request->patient_category);
        }

        $sales = $query->latest('sale_date')->get();

        // Calculate statistics
        $statistics = [
            'total_sales' => $sales->count(),
            'total_revenue' => $sales->sum('total_amount'),
            'total_profit' => $sales->sum('profit_margin'),
            'by_type' => [
                'otc' => $sales->where('sale_type', 'otc')->count(),
                'opd_prescription' => $sales->where('sale_type', 'opd_prescription')->count(),
                'ipd_prescription' => $sales->where('sale_type', 'ipd_prescription')->count(),
                'walk_in' => $sales->where('sale_type', 'walk_in')->count(),
                'ward_issue' => $sales->where('sale_type', 'ward_issue')->count(),
            ],
            'by_category' => [
                'cash' => $sales->where('patient_category', 'cash')->sum('total_amount'),
                'nhif' => $sales->where('patient_category', 'nhif')->sum('total_amount'),
                'insurance' => $sales->where('patient_category', 'insurance')->sum('total_amount'),
                'staff' => $sales->where('patient_category', 'staff')->sum('total_amount'),
                'waiver' => $sales->where('patient_category', 'waiver')->sum('total_amount'),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'sales' => $sales,
                'statistics' => $statistics,
            ]
        ]);
    }
}
