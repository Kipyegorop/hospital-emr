<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'patient_id',
        'doctor_id',
        'prescription_number',
        'medication_id',
        'medication_name',
        'generic_name',
        'dosage_form',
        'strength',
        'dosage_instructions',
        'quantity_prescribed',
        'quantity_locked',
        'unit',
        'duration_days',
        'frequency',
        'status',
        'prescribed_date',
        'expiry_date',
        'special_instructions',
        'side_effects_warning',
        'requires_refrigeration',
        'dispensing_status',
        'quantity_dispensed',
        'quantity_remaining',
        'dispensed_at',
        'dispensed_by',
        'pharmacy_notes',
        'prescription_type',
        'patient_category',
        'unit_price',
        'opd_price',
        'ipd_price',
        'nhif_price',
        'total_cost',
        'payment_status',
        'has_exception_request',
        'exception_status',
    ];

    protected $casts = [
        'prescribed_date' => 'date',
        'expiry_date' => 'date',
        'dispensed_at' => 'datetime',
        'requires_refrigeration' => 'boolean',
        'quantity_locked' => 'boolean',
        'unit_price' => 'decimal:2',
        'opd_price' => 'decimal:2',
        'ipd_price' => 'decimal:2',
        'nhif_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'has_exception_request' => 'boolean',
    ];

    /**
     * Generate unique prescription number
     */
    public static function generatePrescriptionNumber()
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');

        $lastPrescription = static::where('prescription_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('prescription_number', 'desc')
            ->first();

        if ($lastPrescription) {
            $lastNumber = (int) substr($lastPrescription->prescription_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get appropriate price based on prescription type and patient category
     */
    public function getApplicablePriceAttribute()
    {
        // NHIF patients get NHIF price if available
        if ($this->patient_category === 'nhif' && $this->nhif_price) {
            return $this->nhif_price;
        }

        // IPD patients get IPD price if available
        if ($this->prescription_type === 'ipd' && $this->ipd_price) {
            return $this->ipd_price;
        }

        // OPD patients get OPD price if available
        if ($this->prescription_type === 'opd' && $this->opd_price) {
            return $this->opd_price;
        }

        // Fallback to unit price
        return $this->unit_price;
    }

    /**
     * Calculate total cost based on quantity and applicable price
     */
    public function calculateTotalCost()
    {
        $price = $this->applicable_price;
        if ($price && $this->quantity_prescribed) {
            $this->total_cost = $price * $this->quantity_prescribed;
        }
    }

    /**
     * Check if prescription can be modified (quantity not locked or has approved exception)
     */
    public function canModifyQuantity()
    {
        return !$this->quantity_locked || $this->exception_status === 'approved';
    }

    /**
     * Check if prescription is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if prescription is fully dispensed
     */
    public function getIsFullyDispensedAttribute()
    {
        return $this->quantity_dispensed >= $this->quantity_prescribed;
    }

    /**
     * Get remaining quantity to dispense
     */
    public function getRemainingQuantityAttribute()
    {
        return max(0, $this->quantity_prescribed - $this->quantity_dispensed);
    }

    /**
     * Check if prescription requires exception for quantity change
     */
    public function requiresExceptionForQuantity($newQuantity)
    {
        return $this->quantity_locked && $newQuantity != $this->quantity_prescribed;
    }

    /**
     * Relationships
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function dispensedBy()
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function exceptions()
    {
        return $this->hasMany(PrescriptionException::class);
    }

    public function pharmacySales()
    {
        return $this->hasMany(PharmacySale::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('dispensing_status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('prescription_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('patient_category', $category);
    }

    public function scopeWithExceptions($query)
    {
        return $query->where('has_exception_request', true);
    }

    public function scopeQuantityLocked($query)
    {
        return $query->where('quantity_locked', true);
    }
}
