<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedureConsumable extends Model
{
    use HasFactory;

    protected $fillable = [
        'theatre_schedule_id',
        'procedure_id',
        'item_code',
        'item_name',
        'item_description',
        'item_type',
        'planned_quantity',
        'actual_quantity_used',
        'unit_of_measure',
        'batch_number',
        'lot_number',
        'serial_number',
        'expiry_date',
        'manufacturing_date',
        'manufacturer',
        'supplier',
        'unit_cost',
        'unit_price',
        'total_cost',
        'total_charge',
        'billable',
        'billing_code',
        'stock_before_use',
        'stock_after_use',
        'stock_updated',
        'stock_updated_at',
        'stock_updated_by',
        'usage_status',
        'issued_at',
        'used_at',
        'issued_by',
        'used_by',
        'requires_tracking',
        'tracking_notes',
        'adverse_event',
        'adverse_event_notes',
        'quantity_returned',
        'quantity_wasted',
        'return_reason',
        'waste_reason',
        'waste_cost',
        'regulatory_approval',
        'requires_patient_consent',
        'patient_consent_obtained',
        'patient_information',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_charge' => 'decimal:2',
        'waste_cost' => 'decimal:2',
        'billable' => 'boolean',
        'stock_updated' => 'boolean',
        'stock_updated_at' => 'datetime',
        'issued_at' => 'datetime',
        'used_at' => 'datetime',
        'requires_tracking' => 'boolean',
        'adverse_event' => 'boolean',
        'requires_patient_consent' => 'boolean',
        'patient_consent_obtained' => 'boolean',
    ];

    /**
     * Item types
     */
    public static function getItemTypes()
    {
        return [
            'consumable' => [
                'name' => 'Consumable',
                'description' => 'Single-use disposable items',
                'requires_tracking' => false,
            ],
            'implant' => [
                'name' => 'Implant',
                'description' => 'Permanent implantable devices',
                'requires_tracking' => true,
            ],
            'medication' => [
                'name' => 'Medication',
                'description' => 'Drugs used during procedure',
                'requires_tracking' => false,
            ],
            'equipment' => [
                'name' => 'Equipment',
                'description' => 'Reusable equipment',
                'requires_tracking' => true,
            ],
            'other' => [
                'name' => 'Other',
                'description' => 'Other items',
                'requires_tracking' => false,
            ],
        ];
    }

    /**
     * Usage statuses
     */
    public static function getUsageStatuses()
    {
        return [
            'planned' => 'Planned',
            'issued' => 'Issued',
            'used' => 'Used',
            'returned' => 'Returned',
            'wasted' => 'Wasted',
        ];
    }

    /**
     * Calculate total cost and charge
     */
    public function calculateTotals()
    {
        $quantity = $this->actual_quantity_used ?: $this->planned_quantity;
        $this->total_cost = $this->unit_cost * $quantity;
        $this->total_charge = $this->unit_price * $quantity;
        $this->waste_cost = $this->unit_cost * $this->quantity_wasted;
    }

    /**
     * Get item type configuration
     */
    public function getItemTypeConfigAttribute()
    {
        return static::getItemTypes()[$this->item_type] ?? null;
    }

    /**
     * Check if item is an implant
     */
    public function getIsImplantAttribute()
    {
        return $this->item_type === 'implant';
    }

    /**
     * Check if item requires special tracking
     */
    public function getRequiresSpecialTrackingAttribute()
    {
        return $this->is_implant || $this->requires_tracking;
    }

    /**
     * Get remaining quantity
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->planned_quantity - $this->actual_quantity_used - $this->quantity_returned - $this->quantity_wasted;
    }

    /**
     * Check if item is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Generate tracking number for implants
     */
    public function generateTrackingNumber()
    {
        if ($this->is_implant && !$this->serial_number) {
            $prefix = 'TRK';
            $year = date('Y');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            return $prefix . $year . $random;
        }
        return $this->serial_number;
    }

    /**
     * Relationships
     */
    public function theatreSchedule()
    {
        return $this->belongsTo(TheatreSchedule::class);
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function stockUpdatedBy()
    {
        return $this->belongsTo(User::class, 'stock_updated_by');
    }

    /**
     * Scopes
     */
    public function scopeByType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeImplants($query)
    {
        return $query->where('item_type', 'implant');
    }

    public function scopeConsumables($query)
    {
        return $query->where('item_type', 'consumable');
    }

    public function scopeUsed($query)
    {
        return $query->where('usage_status', 'used');
    }

    public function scopeWasted($query)
    {
        return $query->where('usage_status', 'wasted');
    }

    public function scopeRequiresTracking($query)
    {
        return $query->where('requires_tracking', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeWithAdverseEvents($query)
    {
        return $query->where('adverse_event', true);
    }

    public function scopeByBatch($query, $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeBySerial($query, $serialNumber)
    {
        return $query->where('serial_number', $serialNumber);
    }
}
