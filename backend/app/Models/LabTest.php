<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number','patient_id','requested_by','requested_for','test_type','status','priority','requested_at','collected_at','completed_at','result','reported_by','report_notes'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'collected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
