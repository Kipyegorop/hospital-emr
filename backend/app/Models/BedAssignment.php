<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BedAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bed_id',
        'patient_id',
        'encounter_id',
        'assigned_by',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }
}
