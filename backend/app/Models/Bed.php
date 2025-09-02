<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id','bed_number','bed_type','is_occupied','current_patient_id','notes','status'
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
    ];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function currentPatient()
    {
        return $this->belongsTo(Patient::class, 'current_patient_id');
    }
}
