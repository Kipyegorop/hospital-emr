<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'department_id',
        'appointment_date',
        'appointment_time',
        'estimated_duration',
        'appointment_type',
        'status',
        'reason_for_visit',
        'notes',
        'queue_number',
        'check_in_time',
        'check_out_time',
        'consultation_fee',
        'payment_status',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'confirmed', 'in_progress']);
    }
}
