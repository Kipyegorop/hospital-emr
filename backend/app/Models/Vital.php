<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vital extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id','encounter_id','recorded_by','temperature','systolic_bp','diastolic_bp','heart_rate','respiratory_rate','oxygen_saturation','weight','height','notes'
    ];
}
