<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionException extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'requested_by_pharmacist_id',
        'approved_by_doctor_id',
        'exception_type',
        'reason_for_exception',
        'pharmacist_notes',
        'original_quantity',
        'requested_quantity',
        'original_medication',
        'requested_medication',
        'original_dosage',
        'requested_dosage',
        'status',
        'requested_at',
        'responded_at',
        'doctor_response',
        'rejection_reason',
        'patient_notified',
        'patient_notified_at',
        'audit_log',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'patient_notified_at' => 'datetime',
        'patient_notified' => 'boolean',
        'audit_log' => 'array',
    ];

    /**
     * Exception type configurations
     */
    public static function getExceptionTypes()
    {
        return [
            'quantity_change' => [
                'name' => 'Quantity Change',
                'description' => 'Request to change prescribed quantity',
                'requires_doctor_approval' => true,
            ],
            'substitution' => [
                'name' => 'Medication Substitution',
                'description' => 'Request to substitute with different medication',
                'requires_doctor_approval' => true,
            ],
            'dosage_change' => [
                'name' => 'Dosage Change',
                'description' => 'Request to change dosage instructions',
                'requires_doctor_approval' => true,
            ],
            'other' => [
                'name' => 'Other',
                'description' => 'Other type of exception',
                'requires_doctor_approval' => true,
            ],
        ];
    }

    /**
     * Get exception type configuration
     */
    public function getExceptionTypeConfigAttribute()
    {
        return static::getExceptionTypes()[$this->exception_type] ?? null;
    }

    /**
     * Check if exception is pending
     */
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if exception is approved
     */
    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if exception is overdue (pending for more than 24 hours)
     */
    public function getIsOverdueAttribute()
    {
        return $this->status === 'pending' && $this->requested_at->diffInHours(now()) > 24;
    }

    /**
     * Add audit log entry
     */
    public function addAuditLog($action, $details, $userId = null)
    {
        $auditLog = $this->audit_log ?? [];
        $auditLog[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ];
        $this->audit_log = $auditLog;
        $this->save();
    }

    /**
     * Relationships
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function requestedByPharmacist()
    {
        return $this->belongsTo(User::class, 'requested_by_pharmacist_id');
    }

    public function approvedByDoctor()
    {
        return $this->belongsTo(User::class, 'approved_by_doctor_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('requested_at', '<', now()->subHours(24));
    }

    public function scopeByType($query, $type)
    {
        return $query->where('exception_type', $type);
    }
}
