<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * Display a listing of patients
     */
    public function index(Request $request)
    {
        $query = Patient::query();

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by gender
        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by age range
        if ($request->has('min_age') || $request->has('max_age')) {
            $query->whereNotNull('date_of_birth');
            if ($request->min_age) {
                $query->where('date_of_birth', '<=', now()->subYears($request->min_age));
            }
            if ($request->max_age) {
                $query->where('date_of_birth', '>=', now()->subYears($request->max_age + 1));
            }
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $patients = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $patients->items(),
            'pagination' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ]
        ]);
    }

    /**
     * Check for potential duplicate patients
     */
    public function checkDuplicates(Request $request)
    {
        $potentials = Patient::findPotentialDuplicates(
            $request->phone,
            $request->nhif_number,
            $request->id_number,
            $request->first_name,
            $request->last_name,
            $request->date_of_birth
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_duplicates' => $potentials->count() > 0,
                'potential_duplicates' => $potentials->map(function ($patient) {
                    return [
                        'id' => $patient->id,
                        'uhid' => $patient->uhid,
                        'patient_number' => $patient->patient_number,
                        'full_name' => $patient->full_name,
                        'phone' => $patient->phone,
                        'nhif_number' => $patient->nhif_number,
                        'id_number' => $patient->id_number,
                        'date_of_birth' => $patient->date_of_birth,
                        'gender' => $patient->gender,
                        'last_visit' => $patient->appointments()->latest()->first()?->appointment_date,
                    ];
                })
            ]
        ]);
    }

    /**
     * Store a newly created patient
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'phone' => 'nullable|string|max:20|unique:patients',
            'email' => 'nullable|email|max:255|unique:patients',
            'nhif_number' => 'nullable|string|max:50|unique:patients',
            'id_number' => 'nullable|string|max:50|unique:patients',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:500',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expiry_date' => 'nullable|date|after:today',
            'payment_method' => 'nullable|in:cash,nhif,insurance,corporate',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicates unless explicitly bypassed
        if (!$request->boolean('bypass_duplicate_check')) {
            $potentials = Patient::findPotentialDuplicates(
                $request->phone,
                $request->nhif_number,
                $request->id_number,
                $request->first_name,
                $request->last_name,
                $request->date_of_birth
            );

            if ($potentials->count() > 0) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Potential duplicate patients found',
                    'data' => [
                        'potential_duplicates' => $potentials->map(function ($patient) {
                            return [
                                'id' => $patient->id,
                                'uhid' => $patient->uhid,
                                'patient_number' => $patient->patient_number,
                                'full_name' => $patient->full_name,
                                'phone' => $patient->phone,
                                'nhif_number' => $patient->nhif_number,
                                'id_number' => $patient->id_number,
                                'date_of_birth' => $patient->date_of_birth,
                                'gender' => $patient->gender,
                            ];
                        })
                    ]
                ], 409); // Conflict status
            }
        }

        // Initialize variables
        $patient = null;
        $encounter = null;

        DB::transaction(function () use ($request, &$patient, &$encounter, $validator) {
            // Use only validated data to create patient
            $validated = $validator->validated();

            // Generate unique identifiers
            $patientNumber = Patient::generatePatientNumber();
            $uhid = Patient::generateUhid();

            // Create patient
            $patient = Patient::create(array_merge($validated, [
                'patient_number' => $patientNumber,
                'uhid' => $uhid,
            ]));

            // Create initial encounter (use any provided chief_complaint from request)
            $encounter = Encounter::create([
                'encounter_number' => Encounter::generateEncounterNumber(),
                'patient_id' => $patient->id,
                'encounter_type' => 'outpatient',
                'status' => 'active',
                'start_time' => now(),
                'chief_complaint' => $request->input('chief_complaint', 'Initial registration'),
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Patient created successfully',
            'data' => [
                'patient' => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'uhid' => $patient->uhid,
                    'full_name' => $patient->full_name,
                    'date_of_birth' => $patient->date_of_birth,
                    'gender' => $patient->gender,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'nhif_number' => $patient->nhif_number,
                    'status' => $patient->status,
                ],
                'encounter' => [
                    'id' => $encounter->id,
                    'encounter_number' => $encounter->encounter_number,
                    'encounter_type' => $encounter->encounter_type,
                    'status' => $encounter->status,
                    'start_time' => $encounter->start_time,
                ]
            ]
        ], 201);
    }

    /**
     * Display the specified patient
     */
    public function show(Patient $patient)
    {
        $patient->load(['appointments', 'consultations', 'prescriptions', 'labTests', 'bills', 'nhifClaims']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'patient' => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'middle_name' => $patient->middle_name,
                    'full_name' => $patient->full_name,
                    'date_of_birth' => $patient->date_of_birth,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'phone' => $patient->phone,
                    'email' => $patient->email,
                    'nhif_number' => $patient->nhif_number,
                    'id_number' => $patient->id_number,
                    'emergency_contact' => [
                        'name' => $patient->emergency_contact_name,
                        'phone' => $patient->emergency_contact_phone,
                        'relationship' => $patient->emergency_contact_relationship,
                    ],
                    'address' => [
                        'line_1' => $patient->address_line_1,
                        'line_2' => $patient->address_line_2,
                        'city' => $patient->city,
                        'county' => $patient->county,
                        'postal_code' => $patient->postal_code,
                        'country' => $patient->country,
                    ],
                    'medical_info' => [
                        'allergies' => $patient->allergies,
                        'medical_history' => $patient->medical_history,
                        'current_medications' => $patient->current_medications,
                        'blood_type' => $patient->blood_type,
                        'height' => $patient->height,
                        'weight' => $patient->weight,
                        'bmi' => $patient->bmi,
                        'bmi_category' => $patient->bmi_category,
                    ],
                    'insurance' => [
                        'provider' => $patient->insurance_provider,
                        'policy_number' => $patient->insurance_policy_number,
                        'expiry_date' => $patient->insurance_expiry_date,
                        'payment_method' => $patient->payment_method,
                    ],
                    'status' => $patient->status,
                    'notes' => $patient->notes,
                    'statistics' => $patient->getStatistics(),
                ]
            ]
        ]);
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, Patient $patient)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'date_of_birth' => 'sometimes|required|date|before:today',
            'gender' => 'sometimes|required|in:male,female,other',
            'phone' => 'nullable|string|max:20|unique:patients,phone,' . $patient->id,
            'email' => 'nullable|email|max:255|unique:patients,email,' . $patient->id,
            'nhif_number' => 'nullable|string|max:50|unique:patients,nhif_number,' . $patient->id,
            'id_number' => 'nullable|string|max:50|unique:patients,id_number,' . $patient->id,
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'height' => 'nullable|numeric|min:0|max:300',
            'weight' => 'nullable|numeric|min:0|max:500',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expiry_date' => 'nullable|date|after:today',
            'payment_method' => 'nullable|in:cash,nhif,insurance,corporate',
            'status' => 'sometimes|required|in:active,inactive,deceased',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

    // Update using validated fields only
    $patient->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Patient updated successfully',
            'data' => [
                'patient' => [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => $patient->full_name,
                    'status' => $patient->status,
                ]
            ]
        ]);
    }

    /**
     * Remove the specified patient
     */
    public function destroy(Patient $patient)
    {
        // Check if patient has any related records
        if ($patient->appointments()->exists() || 
            $patient->consultations()->exists() || 
            $patient->prescriptions()->exists() || 
            $patient->labTests()->exists() || 
            $patient->bills()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete patient with existing records. Consider deactivating instead.'
            ], 400);
        }

        $patient->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Patient deleted successfully'
        ]);
    }

    /**
     * Search patients
     */
    public function search($query)
    {
        $patients = Patient::search($query)
            ->active()
            ->limit(10)
            ->get(['id', 'patient_number', 'first_name', 'last_name', 'phone', 'nhif_number']);

        return response()->json([
            'status' => 'success',
            'data' => $patients->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'patient_number' => $patient->patient_number,
                    'full_name' => $patient->full_name,
                    'phone' => $patient->phone,
                    'nhif_number' => $patient->nhif_number,
                ];
            })
        ]);
    }

    /**
     * Merge patients
     */
    public function merge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_patient_id' => 'required|exists:patients,id',
            'target_patient_id' => 'required|exists:patients,id|different:source_patient_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sourcePatient = Patient::findOrFail($request->source_patient_id);
        $targetPatient = Patient::findOrFail($request->target_patient_id);

        if ($sourcePatient->status === 'merged') {
            return response()->json([
                'status' => 'error',
                'message' => 'Source patient is already merged'
            ], 400);
        }

        try {
            $sourcePatient->mergeInto($targetPatient, $request->user()->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Patients merged successfully',
                'data' => [
                    'source_patient' => [
                        'id' => $sourcePatient->id,
                        'uhid' => $sourcePatient->uhid,
                        'full_name' => $sourcePatient->full_name,
                        'status' => $sourcePatient->status,
                    ],
                    'target_patient' => [
                        'id' => $targetPatient->id,
                        'uhid' => $targetPatient->uhid,
                        'full_name' => $targetPatient->full_name,
                        'status' => $targetPatient->status,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to merge patients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient history
     */
    public function history(Patient $patient)
    {
        $history = [
            'appointments' => $patient->appointments()->with('doctor', 'department')->latest()->get(),
            'consultations' => $patient->consultations()->with('doctor', 'department')->latest()->get(),
            'prescriptions' => $patient->prescriptions()->with('doctor')->latest()->get(),
            'lab_tests' => $patient->labTests()->with('requestedBy')->latest()->get(),
            'bills' => $patient->bills()->latest()->get(),
            'nhif_claims' => $patient->nhifClaims()->latest()->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Get patient statistics
     */
    public function statistics(Patient $patient)
    {
        return response()->json([
            'status' => 'success',
            'data' => $patient->getStatistics()
        ]);
    }
}
