<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicationAdministration extends Model
{
    use HasFactory;

    protected $fillable = ['prescription_id','patient_id','encounter_id','administered_by','medication_name','dose','route','frequency','administration_time','notes','given'];
}
