<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NursingAssessment extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id','encounter_id','nurse_id','assessment','care_plan','observations'];
}
