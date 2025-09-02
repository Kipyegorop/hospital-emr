<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'generic_name',
        'brand',
        'form',
        'strength',
        'unit',
        'package_size',
        'current_stock',
        'reorder_level',
        'reorder_quantity',
        'unit_cost',
        'selling_price',
        'nhif_price',
        'is_controlled',
        'requires_refrigeration',
        'batch_number',
        'expiry_date',
        'manufacturer',
        'vendor',
        'notes',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'unit_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'nhif_price' => 'decimal:2',
        'expiry_date' => 'date',
        'is_controlled' => 'boolean',
        'requires_refrigeration' => 'boolean',
    ];

    public function isLowStock()
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_level');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereDate('expiry_date', '<=', now()->addDays($days));
    }
}
