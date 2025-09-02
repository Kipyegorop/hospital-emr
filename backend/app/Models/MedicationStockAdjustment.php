<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationStockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'medication_id', 'user_id', 'stock_before', 'adjustment', 'stock_after', 'reason'
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
