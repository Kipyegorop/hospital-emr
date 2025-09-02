<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'loinc_code_id',
        'item_code',
        'item_name',
        'item_description',
        'item_category',
        'loinc_code',
        'loinc_display_name',
        'loinc_system',
        'quantity',
        'unit_of_measure',
        'specimen_type',
        'collection_instructions',
        'preparation_instructions',
        'status',
        'collected_at',
        'resulted_at',
        'result_value',
        'result_unit',
        'reference_range',
        'result_status',
        'result_notes',
        'unit_price',
        'total_price',
        'billing_code',
        'performed_by_user_id',
        'verified_by_user_id',
        'verified_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'resulted_at' => 'datetime',
        'verified_at' => 'datetime',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Calculate total price based on quantity and unit price
     */
    public function calculateTotalPrice()
    {
        if ($this->unit_price && $this->quantity) {
            $this->total_price = $this->unit_price * $this->quantity;
        }
    }

    /**
     * Check if result is critical
     */
    public function getIsCriticalAttribute()
    {
        return $this->result_status === 'critical';
    }

    /**
     * Check if result is abnormal
     */
    public function getIsAbnormalAttribute()
    {
        return in_array($this->result_status, ['abnormal', 'critical']);
    }

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function loincCode()
    {
        return $this->belongsTo(LoincCode::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCritical($query)
    {
        return $query->where('result_status', 'critical');
    }

    public function scopeAbnormal($query)
    {
        return $query->whereIn('result_status', ['abnormal', 'critical']);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('item_category', $category);
    }
}
