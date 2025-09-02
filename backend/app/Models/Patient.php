<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Patient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_number',
        'uhid',
        'nhif_number',
        'id_number',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'address_line_1',
        'address_line_2',
        'city',
        'county',
        'postal_code',
        'country',
        'allergies',
        'medical_history',
        'current_medications',
        'blood_type',
        'height',
        'weight',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expiry_date',
        'payment_method',
        'status',
        'notes',
        'merged_into_patient_id',
        'merged_at',
        'merged_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'insurance_expiry_date' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'merged_at' => 'datetime',
    ];

    /**
     * Get the patient's full name.
     */
    public function getFullNameAttribute()
    {
        $name = trim($this->first_name . ' ' . $this->last_name);
        if ($this->middle_name) {
            $name = trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
        }
        return $name;
    }

    /**
     * Get the patient's age.
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }

    /**
     * Get the patient's BMI.
     */
    public function getBmiAttribute()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
        return null;
    }

    /**
     * Get the patient's BMI category.
     */
    public function getBmiCategoryAttribute()
    {
        $bmi = $this->bmi;
        if (!$bmi) return null;
        
        if ($bmi < 18.5) return 'Underweight';
        if ($bmi < 25) return 'Normal weight';
        if ($bmi < 30) return 'Overweight';
        return 'Obese';
    }

    /**
     * Get appointments for this patient.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get consultations for this patient.
     */
    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * Get prescriptions for this patient.
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get lab tests for this patient.
     */
    public function labTests()
    {
        return $this->hasMany(LabTest::class);
    }

    /**
     * Get bills for this patient.
     */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get NHIF claims for this patient.
     */
    public function nhifClaims()
    {
        return $this->hasMany(NhifClaim::class);
    }

    /**
     * Get encounters for this patient.
     */
    public function encounters()
    {
        return $this->hasMany(Encounter::class);
    }

    /**
     * Get current bed assignment.
     */
    public function currentBed()
    {
        return $this->belongsTo(Bed::class, 'id', 'current_patient_id');
    }

    /**
     * Scope to get only active patients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to search patients by name or number.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('patient_number', 'like', "%{$search}%")
              ->orWhere('nhif_number', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Get patient by patient number.
     */
    public static function findByPatientNumber($patientNumber)
    {
        return static::where('patient_number', $patientNumber)->first();
    }

    /**
     * Get patient by NHIF number.
     */
    public static function findByNhifNumber($nhifNumber)
    {
        return static::where('nhif_number', $nhifNumber)->first();
    }

    /**
     * Get patient by ID number.
     */
    public static function findByIdNumber($idNumber)
    {
        return static::where('id_number', $idNumber)->first();
    }

    /**
     * Generate unique patient number.
     */
    public static function generatePatientNumber()
    {
        $prefix = 'P';
        $year = date('Y');
        $lastPatient = static::where('patient_number', 'like', "{$prefix}{$year}%")
            ->orderBy('patient_number', 'desc')
            ->first();

        if ($lastPatient) {
            $lastNumber = (int) substr($lastPatient->patient_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique UHID (Unique Hospital ID).
     */
    public static function generateUhid()
    {
        $prefix = 'UHID';
        $year = date('Y');
        $lastPatient = static::where('uhid', 'like', "{$prefix}{$year}%")
            ->orderBy('uhid', 'desc')
            ->first();

        if ($lastPatient) {
            $lastNumber = (int) substr($lastPatient->uhid, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Find potential duplicate patients using fuzzy matching.
     */
    public static function findPotentialDuplicates($phone = null, $nhifNumber = null, $idNumber = null, $firstName = null, $lastName = null, $dateOfBirth = null)
    {
        $query = static::where('status', '!=', 'merged');
        $potentials = collect();

        // Exact matches on unique fields
        if ($phone) {
            $phoneMatches = $query->where('phone', $phone)->get();
            $potentials = $potentials->merge($phoneMatches);
        }

        if ($nhifNumber) {
            $nhifMatches = $query->where('nhif_number', $nhifNumber)->get();
            $potentials = $potentials->merge($nhifMatches);
        }

        if ($idNumber) {
            $idMatches = $query->where('id_number', $idNumber)->get();
            $potentials = $potentials->merge($idMatches);
        }

        // Fuzzy matching on name and DOB
        if ($firstName && $lastName && $dateOfBirth) {
            $nameMatches = $query->where('date_of_birth', $dateOfBirth)
                ->where(function ($q) use ($firstName, $lastName) {
                    $q->where('first_name', 'like', "%{$firstName}%")
                      ->where('last_name', 'like', "%{$lastName}%");
                })->get();
            $potentials = $potentials->merge($nameMatches);
        }

        return $potentials->unique('id');
    }

    /**
     * Merge this patient into another patient.
     */
    public function mergeInto(Patient $targetPatient, $userId)
    {
        if ($this->id === $targetPatient->id) {
            throw new \InvalidArgumentException('Cannot merge patient into itself');
        }

        if ($this->status === 'merged') {
            throw new \InvalidArgumentException('Patient is already merged');
        }

        DB::transaction(function () use ($targetPatient, $userId) {
            // Update all related records to point to target patient
            $this->appointments()->update(['patient_id' => $targetPatient->id]);
            $this->consultations()->update(['patient_id' => $targetPatient->id]);
            $this->prescriptions()->update(['patient_id' => $targetPatient->id]);
            $this->labTests()->update(['patient_id' => $targetPatient->id]);
            $this->bills()->update(['patient_id' => $targetPatient->id]);
            $this->nhifClaims()->update(['patient_id' => $targetPatient->id]);

            // Mark this patient as merged
            $this->update([
                'status' => 'merged',
                'merged_into_patient_id' => $targetPatient->id,
                'merged_at' => now(),
                'merged_by_user_id' => $userId,
            ]);

            // Log the merge action
            Log::info('Patient merged', [
                'source_patient_id' => $this->id,
                'target_patient_id' => $targetPatient->id,
                'merged_by_user_id' => $userId,
                'merged_at' => now(),
            ]);
        });
    }

    /**
     * Get merge relationships.
     */
    public function mergedIntoPatient()
    {
        return $this->belongsTo(Patient::class, 'merged_into_patient_id');
    }

    public function mergedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'merged_by_user_id');
    }

    public function mergedPatients()
    {
        return $this->hasMany(Patient::class, 'merged_into_patient_id');
    }

    /**
     * Get patient statistics.
     */
    public function getStatistics()
    {
        return [
            'total_appointments' => $this->appointments()->count(),
            'total_consultations' => $this->consultations()->count(),
            'total_prescriptions' => $this->prescriptions()->count(),
            'total_lab_tests' => $this->labTests()->count(),
            'total_bills' => $this->bills()->count(),
            'total_nhif_claims' => $this->nhifClaims()->count(),
            'last_visit' => $this->appointments()->latest()->first()?->appointment_date,
            'outstanding_bills' => $this->bills()->where('payment_status', '!=', 'paid')->sum('balance_due'),
        ];
    }
}
