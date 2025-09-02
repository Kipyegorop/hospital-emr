<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_number',
        'patient_id',
        'prescription_id',
        'medication_id',
        'pharmacist_id',
        'department_id',
        'sale_type',
        'patient_category',
        'medication_name',
        'batch_number',
        'expiry_date',
        'quantity_sold',
        'unit',
        'unit_cost',
        'unit_price',
        'opd_price',
        'ipd_price',
        'otc_price',
        'nhif_price',
        'discount_amount',
        'total_amount',
        'profit_margin',
        'payment_method',
        'payment_status',
        'payment_reference',
        'amount_paid',
        'balance_due',
        'stock_before_sale',
        'stock_after_sale',
        'stock_updated',
        'customer_name',
        'customer_phone',
        'customer_address',
        'indication',
        'dosage_instructions',
        'pharmacist_counseling_notes',
        'counseling_provided',
        'requires_prescription',
        'is_controlled_substance',
        'prescriber_license',
        'regulatory_notes',
        'sale_date',
        'status',
        'completed_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'is_returnable',
        'return_deadline',
        'returned_amount',
        'return_reason',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'sale_date' => 'datetime',
        'completed_at' => 'datetime',
        'return_deadline' => 'datetime',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'opd_price' => 'decimal:2',
        'ipd_price' => 'decimal:2',
        'otc_price' => 'decimal:2',
        'nhif_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'returned_amount' => 'decimal:2',
        'requires_prescription' => 'boolean',
        'is_controlled_substance' => 'boolean',
        'counseling_provided' => 'boolean',
        'stock_updated' => 'boolean',
        'is_returnable' => 'boolean',
    ];

    /**
     * Sale type configurations
     */
    public static function getSaleTypes()
    {
        return [
            'otc' => [
                'name' => 'Over-the-Counter',
                'description' => 'Walk-in OTC medication sales',
                'requires_prescription' => false,
                'price_list' => 'otc_price',
            ],
            'opd_prescription' => [
                'name' => 'OPD Prescription',
                'description' => 'Outpatient prescription dispensing',
                'requires_prescription' => true,
                'price_list' => 'opd_price',
            ],
            'ipd_prescription' => [
                'name' => 'IPD Prescription',
                'description' => 'Inpatient prescription dispensing',
                'requires_prescription' => true,
                'price_list' => 'ipd_price',
            ],
            'walk_in' => [
                'name' => 'Walk-in Sale',
                'description' => 'Walk-in customer purchase',
                'requires_prescription' => false,
                'price_list' => 'otc_price',
            ],
            'ward_issue' => [
                'name' => 'Ward Issue',
                'description' => 'Medication issued to ward',
                'requires_prescription' => true,
                'price_list' => 'ipd_price',
            ],
        ];
    }

    /**
     * Generate unique sale number
     */
    public static function generateSaleNumber($saleType = null)
    {
        $prefix = $saleType ? strtoupper(substr($saleType, 0, 3)) : 'SAL';
        $year = date('Y');
        $month = date('m');

        $lastSale = static::where('sale_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('sale_number', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->sale_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get sale type configuration
     */
    public function getSaleTypeConfigAttribute()
    {
        return static::getSaleTypes()[$this->sale_type] ?? null;
    }

    /**
     * Get appropriate price based on sale type and patient category
     */
    public function getApplicablePriceAttribute()
    {
        // NHIF patients get NHIF price if available
        if ($this->patient_category === 'nhif' && $this->nhif_price) {
            return $this->nhif_price;
        }

        // Use price list based on sale type
        $saleTypeConfig = $this->sale_type_config;
        if ($saleTypeConfig) {
            $priceField = $saleTypeConfig['price_list'];
            if ($this->$priceField) {
                return $this->$priceField;
            }
        }

        // Fallback to unit price
        return $this->unit_price;
    }

    /**
     * Calculate profit margin
     */
    public function calculateProfitMargin()
    {
        if ($this->unit_cost && $this->unit_price) {
            $this->profit_margin = ($this->unit_price - $this->unit_cost) * $this->quantity_sold;
        }
    }

    /**
     * Calculate balance due
     */
    public function calculateBalanceDue()
    {
        $this->balance_due = max(0, $this->total_amount - $this->amount_paid - $this->discount_amount);
    }

    /**
     * Check if sale is fully paid
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->balance_due <= 0;
    }

    /**
     * Check if sale is returnable (within return period)
     */
    public function getCanReturnAttribute()
    {
        return $this->is_returnable &&
               $this->return_deadline &&
               now()->lte($this->return_deadline) &&
               $this->status === 'completed';
    }

    /**
     * Relationships
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * Scopes
     */
    public function scopeByType($query, $type)
    {
        return $query->where('sale_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('patient_category', $category);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sale_date', now()->month)
                    ->whereYear('sale_date', now()->year);
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeReturnable($query)
    {
        return $query->where('is_returnable', true)
                    ->where('return_deadline', '>=', now())
                    ->where('status', 'completed');
    }

    public function scopeOtc($query)
    {
        return $query->whereIn('sale_type', ['otc', 'walk_in']);
    }

    public function scopeOpd($query)
    {
        return $query->where('sale_type', 'opd_prescription');
    }

    public function scopeIpd($query)
    {
        return $query->whereIn('sale_type', ['ipd_prescription', 'ward_issue']);
    }
}
