<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Encounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_number',
        'patient_id',
        'department_id',
        'attending_doctor_id',
        'encounter_type',
        'status',
        'start_time',
        'end_time',
        'chief_complaint',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Generate unique encounter number.
     */
    public static function generateEncounterNumber()
    {
        $prefix = 'ENC';
        $year = date('Y');
        $month = date('m');
        $lastEncounter = static::where('encounter_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('encounter_number', 'desc')
            ->first();

        if ($lastEncounter) {
            $lastNumber = (int) substr($lastEncounter->encounter_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function attendingDoctor()
    {
        return $this->belongsTo(User::class, 'attending_doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}
