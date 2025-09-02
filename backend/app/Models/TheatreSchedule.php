<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TheatreSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_number',
        'patient_id',
        'procedure_id',
        'primary_surgeon_id',
        'anesthetist_id',
        'theatre_room_id',
        'encounter_id',
        'scheduled_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'estimated_duration_minutes',
        'priority',
        'session',
        'procedure_name',
        'procedure_notes',
        'anesthesia_type',
        'special_requirements',
        'surgeon_notes',
        'pre_op_checklist',
        'pre_op_completed',
        'pre_op_completed_at',
        'pre_op_completed_by',
        'pre_op_notes',
        'consent_obtained',
        'consent_obtained_at',
        'consent_obtained_by',
        'consent_notes',
        'consent_form_path',
        'assigned_staff',
        'scrub_nurses',
        'circulating_nurses',
        'other_staff',
        'required_equipment',
        'equipment_checklist',
        'equipment_ready',
        'equipment_checked_at',
        'equipment_checked_by',
        'consumables_list',
        'implants_list',
        'consumables_cost',
        'implants_cost',
        'consumables_ready',
        'status',
        'actual_start_time',
        'actual_end_time',
        'actual_duration_minutes',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'rescheduled_date',
        'rescheduled_time',
        'post_op_instructions',
        'complications',
        'outcome',
        'outcome_notes',
        'total_procedure_cost',
        'charges_posted',
        'charges_posted_at',
        'charges_posted_by',
        'quality_metrics',
        'audit_required',
        'audit_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_start_time' => 'datetime',
        'scheduled_end_time' => 'datetime',
        'pre_op_completed_at' => 'datetime',
        'consent_obtained_at' => 'datetime',
        'equipment_checked_at' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'rescheduled_date' => 'date',
        'rescheduled_time' => 'datetime',
        'charges_posted_at' => 'datetime',
        'pre_op_checklist' => 'array',
        'assigned_staff' => 'array',
        'scrub_nurses' => 'array',
        'circulating_nurses' => 'array',
        'other_staff' => 'array',
        'required_equipment' => 'array',
        'equipment_checklist' => 'array',
        'consumables_list' => 'array',
        'implants_list' => 'array',
        'quality_metrics' => 'array',
        'consumables_cost' => 'decimal:2',
        'implants_cost' => 'decimal:2',
        'total_procedure_cost' => 'decimal:2',
        'pre_op_completed' => 'boolean',
        'consent_obtained' => 'boolean',
        'equipment_ready' => 'boolean',
        'consumables_ready' => 'boolean',
        'charges_posted' => 'boolean',
        'audit_required' => 'boolean',
    ];

    /**
     * Generate unique schedule number
     */
    public static function generateScheduleNumber()
    {
        $prefix = 'TS';
        $year = date('Y');
        $month = date('m');

        $lastSchedule = static::where('schedule_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('schedule_number', 'desc')
            ->first();

        if ($lastSchedule) {
            $lastNumber = (int) substr($lastSchedule->schedule_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Priority configurations
     */
    public static function getPriorities()
    {
        return [
            'elective' => [
                'name' => 'Elective',
                'color' => 'green',
                'urgency' => 3,
                'scheduling_window' => '1-4 weeks',
            ],
            'urgent' => [
                'name' => 'Urgent',
                'color' => 'orange',
                'urgency' => 2,
                'scheduling_window' => '24-72 hours',
            ],
            'emergency' => [
                'name' => 'Emergency',
                'color' => 'red',
                'urgency' => 1,
                'scheduling_window' => 'Immediate',
            ],
        ];
    }

    /**
     * Get priority configuration
     */
    public function getPriorityConfigAttribute()
    {
        return static::getPriorities()[$this->priority] ?? null;
    }

    /**
     * Check if surgery is ready to proceed
     */
    public function getIsReadyAttribute()
    {
        return $this->pre_op_completed &&
               $this->consent_obtained &&
               $this->equipment_ready &&
               $this->consumables_ready;
    }

    /**
     * Get actual duration in hours
     */
    public function getActualDurationHoursAttribute()
    {
        return $this->actual_duration_minutes ? round($this->actual_duration_minutes / 60, 2) : null;
    }

    /**
     * Check if surgery is overdue
     */
    public function getIsOverdueAttribute()
    {
        if ($this->status !== 'scheduled') {
            return false;
        }

        return now()->gt($this->scheduled_start_time);
    }

    /**
     * Calculate total cost including consumables and implants
     */
    public function calculateTotalCost()
    {
        $procedureCost = $this->procedure ? $this->procedure->total_cost : 0;
        $this->total_procedure_cost = $procedureCost + $this->consumables_cost + $this->implants_cost;
    }

    /**
     * Relationships
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }

    public function primarySurgeon()
    {
        return $this->belongsTo(User::class, 'primary_surgeon_id');
    }

    public function anesthetist()
    {
        return $this->belongsTo(User::class, 'anesthetist_id');
    }

    public function theatreRoom()
    {
        return $this->belongsTo(Department::class, 'theatre_room_id');
    }

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }

    public function preOpCompletedBy()
    {
        return $this->belongsTo(User::class, 'pre_op_completed_by');
    }

    public function consentObtainedBy()
    {
        return $this->belongsTo(User::class, 'consent_obtained_by');
    }

    public function equipmentCheckedBy()
    {
        return $this->belongsTo(User::class, 'equipment_checked_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function chargesPostedBy()
    {
        return $this->belongsTo(User::class, 'charges_posted_by');
    }

    public function procedureConsumables()
    {
        return $this->hasMany(ProcedureConsumable::class);
    }

    /**
     * Scopes
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', today())
                    ->where('status', 'scheduled');
    }

    public function scopeByPriority($query)
    {
        return $query->orderByRaw("
            CASE priority
                WHEN 'emergency' THEN 1
                WHEN 'urgent' THEN 2
                WHEN 'elective' THEN 3
            END
        ")->orderBy('scheduled_start_time');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySurgeon($query, $surgeonId)
    {
        return $query->where('primary_surgeon_id', $surgeonId);
    }

    public function scopeByTheatre($query, $theatreId)
    {
        return $query->where('theatre_room_id', $theatreId);
    }

    public function scopeReady($query)
    {
        return $query->where('pre_op_completed', true)
                    ->where('consent_obtained', true)
                    ->where('equipment_ready', true)
                    ->where('consumables_ready', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_start_time', '<', now());
    }
}
