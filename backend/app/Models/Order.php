<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'patient_id',
        'encounter_id',
        'ordering_physician_id',
        'department_id',
        'target_department_id',
        'order_type',
        'priority',
        'status',
        'ordered_at',
        'scheduled_at',
        'acknowledged_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'clinical_indication',
        'special_instructions',
        'notes',
        'estimated_cost',
        'billable',
        'billing_code',
        'acknowledged_by_user_id',
        'completed_by_user_id',
        'cancelled_by_user_id',
        'cancellation_reason',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'billable' => 'boolean',
    ];

    /**
     * Order type configurations
     */
    public static function getOrderTypes()
    {
        return [
            'laboratory' => [
                'name' => 'Laboratory',
                'icon' => 'lab',
                'color' => 'blue',
                'target_departments' => ['laboratory'],
            ],
            'radiology' => [
                'name' => 'Radiology',
                'icon' => 'x-ray',
                'color' => 'purple',
                'target_departments' => ['radiology'],
            ],
            'procedure' => [
                'name' => 'Procedure',
                'icon' => 'medical',
                'color' => 'green',
                'target_departments' => ['surgery', 'procedure_room'],
            ],
            'medication' => [
                'name' => 'Medication',
                'icon' => 'pill',
                'color' => 'orange',
                'target_departments' => ['pharmacy'],
            ],
            'consultation' => [
                'name' => 'Consultation',
                'icon' => 'user-md',
                'color' => 'teal',
                'target_departments' => ['cardiology', 'neurology', 'orthopedics'],
            ],
            'other' => [
                'name' => 'Other',
                'icon' => 'clipboard',
                'color' => 'gray',
                'target_departments' => [],
            ],
        ];
    }

    /**
     * Priority configurations
     */
    public static function getPriorities()
    {
        return [
            'routine' => [
                'name' => 'Routine',
                'color' => 'green',
                'target_time_hours' => 24,
                'sort_order' => 4,
            ],
            'urgent' => [
                'name' => 'Urgent',
                'color' => 'yellow',
                'target_time_hours' => 4,
                'sort_order' => 2,
            ],
            'stat' => [
                'name' => 'STAT',
                'color' => 'red',
                'target_time_hours' => 1,
                'sort_order' => 1,
            ],
            'asap' => [
                'name' => 'ASAP',
                'color' => 'orange',
                'target_time_hours' => 2,
                'sort_order' => 3,
            ],
        ];
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber($orderType = null)
    {
        $prefix = $orderType ? strtoupper(substr($orderType, 0, 3)) : 'ORD';
        $year = date('Y');
        $month = date('m');

        $lastOrder = static::where('order_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get order type configuration
     */
    public function getOrderTypeConfigAttribute()
    {
        return static::getOrderTypes()[$this->order_type] ?? null;
    }

    /**
     * Get priority configuration
     */
    public function getPriorityConfigAttribute()
    {
        return static::getPriorities()[$this->priority] ?? null;
    }

    /**
     * Check if order is overdue
     */
    public function getIsOverdueAttribute()
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }

        $priorityConfig = $this->priority_config;
        if (!$priorityConfig) {
            return false;
        }

        $targetTime = $this->ordered_at->addHours($priorityConfig['target_time_hours']);
        return now()->gt($targetTime);
    }

    /**
     * Get time remaining until due
     */
    public function getTimeRemainingAttribute()
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return null;
        }

        $priorityConfig = $this->priority_config;
        if (!$priorityConfig) {
            return null;
        }

        $targetTime = $this->ordered_at->addHours($priorityConfig['target_time_hours']);
        $diff = now()->diffInMinutes($targetTime, false);

        return $diff > 0 ? $diff : 0;
    }

    /**
     * Relationships
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }

    public function orderingPhysician()
    {
        return $this->belongsTo(User::class, 'ordering_physician_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function targetDepartment()
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scopes
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("
            CASE priority
                WHEN 'stat' THEN 1
                WHEN 'urgent' THEN 2
                WHEN 'asap' THEN 3
                WHEN 'routine' THEN 4
            END
        ")->orderBy('ordered_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('target_department_id', $departmentId);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('priority', 'stat')
                         ->where('ordered_at', '<', now()->subHour());
                })->orWhere(function ($subQ) {
                    $subQ->where('priority', 'asap')
                         ->where('ordered_at', '<', now()->subHours(2));
                })->orWhere(function ($subQ) {
                    $subQ->where('priority', 'urgent')
                         ->where('ordered_at', '<', now()->subHours(4));
                })->orWhere(function ($subQ) {
                    $subQ->where('priority', 'routine')
                         ->where('ordered_at', '<', now()->subHours(24));
                });
            });
    }
}
