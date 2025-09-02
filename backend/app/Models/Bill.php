<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'patient_id',
        'created_by',
        'bill_type',
        'description',
        'bill_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'payment_method',
        'payment_status',
        'paid_at',
        'transaction_reference',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_coverage',
        'nhif_number',
        'nhif_status',
        'billable_items',
        'appointment_id',
        'consultation_id',
        'prescription_id',
        'lab_test_id',
        'notes',
        'payment_notes',
        'attachments',
        'status',
        'is_printed',
        'printed_at',
    ];

    protected $casts = [
        'billable_items' => 'array',
        'attachments' => 'array',
        'bill_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
