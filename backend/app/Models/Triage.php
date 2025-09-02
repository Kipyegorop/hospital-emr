<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Triage extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'encounter_id',
        'nurse_id',
        'department_id',
        'temperature',
        'systolic_bp',
        'diastolic_bp',
        'heart_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'height',
        'bmi',
        'triage_level',
        'triage_color',
        'chief_complaint',
        'presenting_symptoms',
        'pain_scale',
        'allergies',
        'current_medications',
        'medical_history',
        'assessment_notes',
        'queue_number',
        'queue_status',
        'queue_time',
        'called_time',
        'started_time',
        'completed_time',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'bmi' => 'decimal:2',
        'oxygen_saturation' => 'decimal:2',
        'queue_time' => 'datetime',
        'called_time' => 'datetime',
        'started_time' => 'datetime',
        'completed_time' => 'datetime',
    ];

    /**
     * Triage level configurations with colors and priorities
     */
    public static function getTriageLevels()
    {
        return [
            'emergency' => [
                'color' => 'red',
                'priority' => 1,
                'name' => 'Emergency',
                'description' => 'Life-threatening condition requiring immediate attention',
                'target_time' => 0, // minutes
            ],
            'urgent' => [
                'color' => 'orange',
                'priority' => 2,
                'name' => 'Urgent',
                'description' => 'Serious condition requiring prompt attention',
                'target_time' => 10,
            ],
            'semi_urgent' => [
                'color' => 'yellow',
                'priority' => 3,
                'name' => 'Semi-Urgent',
                'description' => 'Moderately serious condition',
                'target_time' => 30,
            ],
            'non_urgent' => [
                'color' => 'green',
                'priority' => 4,
                'name' => 'Non-Urgent',
                'description' => 'Less serious condition',
                'target_time' => 60,
            ],
            'fast_track' => [
                'color' => 'blue',
                'priority' => 5,
                'name' => 'Fast Track',
                'description' => 'Minor condition suitable for fast track',
                'target_time' => 120,
            ],
        ];
    }

    /**
     * Get triage level configuration
     */
    public function getTriageLevelConfigAttribute()
    {
        return static::getTriageLevels()[$this->triage_level] ?? null;
    }

    /**
     * Calculate BMI automatically
     */
    public function calculateBmi()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            $this->bmi = round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
    }

    /**
     * Generate next queue number
     */
    public static function generateQueueNumber($departmentId = null)
    {
        $query = static::whereDate('created_at', today());

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $lastQueue = $query->orderBy('queue_number', 'desc')->first();

        return $lastQueue ? $lastQueue->queue_number + 1 : 1;
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

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Scopes
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeWaiting($query)
    {
        return $query->where('queue_status', 'waiting');
    }

    public function scopeByPriority($query)
    {
        return $query->orderByRaw("
            CASE triage_level
                WHEN 'emergency' THEN 1
                WHEN 'urgent' THEN 2
                WHEN 'semi_urgent' THEN 3
                WHEN 'non_urgent' THEN 4
                WHEN 'fast_track' THEN 5
            END
        ")->orderBy('queue_time');
    }
}
