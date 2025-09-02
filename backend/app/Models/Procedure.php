<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedure_code',
        'procedure_name',
        'description',
        'category',
        'specialty',
        'estimated_duration_minutes',
        'complexity_level',
        'anesthesia_type',
        'pre_procedure_requirements',
        'post_procedure_care',
        'contraindications',
        'complications',
        'base_price',
        'surgeon_fee',
        'anesthetist_fee',
        'theatre_fee',
        'consumables_cost',
        'total_cost',
        'nhif_rate',
        'billing_code',
        'required_equipment',
        'required_staff',
        'consumables_list',
        'requires_implants',
        'requires_blood_products',
        'requires_consent',
        'is_emergency_procedure',
        'is_day_case',
        'typical_los_days',
        'quality_indicators',
        'status',
        'is_available',
        'notes',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'surgeon_fee' => 'decimal:2',
        'anesthetist_fee' => 'decimal:2',
        'theatre_fee' => 'decimal:2',
        'consumables_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'nhif_rate' => 'decimal:2',
        'required_equipment' => 'array',
        'required_staff' => 'array',
        'consumables_list' => 'array',
        'requires_implants' => 'boolean',
        'requires_blood_products' => 'boolean',
        'requires_consent' => 'boolean',
        'is_emergency_procedure' => 'boolean',
        'is_day_case' => 'boolean',
        'is_available' => 'boolean',
    ];

    /**
     * Procedure categories
     */
    public static function getCategories()
    {
        return [
            'surgery' => 'Surgery',
            'diagnostic' => 'Diagnostic',
            'therapeutic' => 'Therapeutic',
            'interventional' => 'Interventional',
            'emergency' => 'Emergency',
            'cosmetic' => 'Cosmetic',
            'preventive' => 'Preventive',
        ];
    }

    /**
     * Medical specialties
     */
    public static function getSpecialties()
    {
        return [
            'general_surgery' => 'General Surgery',
            'orthopedics' => 'Orthopedics',
            'cardiology' => 'Cardiology',
            'neurosurgery' => 'Neurosurgery',
            'gynecology' => 'Gynecology',
            'urology' => 'Urology',
            'ophthalmology' => 'Ophthalmology',
            'ent' => 'ENT',
            'plastic_surgery' => 'Plastic Surgery',
            'pediatric_surgery' => 'Pediatric Surgery',
            'vascular_surgery' => 'Vascular Surgery',
            'thoracic_surgery' => 'Thoracic Surgery',
        ];
    }

    /**
     * Complexity levels
     */
    public static function getComplexityLevels()
    {
        return [
            'simple' => [
                'name' => 'Simple',
                'duration_range' => '15-30 minutes',
                'risk_level' => 'Low',
                'anesthesia' => 'Local/None',
            ],
            'moderate' => [
                'name' => 'Moderate',
                'duration_range' => '30-90 minutes',
                'risk_level' => 'Medium',
                'anesthesia' => 'Local/Regional',
            ],
            'complex' => [
                'name' => 'Complex',
                'duration_range' => '90-180 minutes',
                'risk_level' => 'High',
                'anesthesia' => 'General',
            ],
            'high_risk' => [
                'name' => 'High Risk',
                'duration_range' => '180+ minutes',
                'risk_level' => 'Very High',
                'anesthesia' => 'General + Monitoring',
            ],
        ];
    }

    /**
     * Generate unique procedure code
     */
    public static function generateProcedureCode($category = null)
    {
        $prefix = $category ? strtoupper(substr($category, 0, 3)) : 'PROC';
        $year = date('Y');

        $lastProcedure = static::where('procedure_code', 'like', "{$prefix}{$year}%")
            ->orderBy('procedure_code', 'desc')
            ->first();

        if ($lastProcedure) {
            $lastNumber = (int) substr($lastProcedure->procedure_code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total cost including all components
     */
    public function calculateTotalCost()
    {
        $this->total_cost = $this->base_price +
                           ($this->surgeon_fee ?? 0) +
                           ($this->anesthetist_fee ?? 0) +
                           ($this->theatre_fee ?? 0) +
                           ($this->consumables_cost ?? 0);
    }

    /**
     * Get complexity level configuration
     */
    public function getComplexityConfigAttribute()
    {
        return static::getComplexityLevels()[$this->complexity_level] ?? null;
    }

    /**
     * Check if procedure is high risk
     */
    public function getIsHighRiskAttribute()
    {
        return in_array($this->complexity_level, ['complex', 'high_risk']);
    }

    /**
     * Get estimated duration in hours
     */
    public function getEstimatedDurationHoursAttribute()
    {
        return round($this->estimated_duration_minutes / 60, 2);
    }

    /**
     * Relationships
     */
    public function theatreSchedules()
    {
        return $this->hasMany(TheatreSchedule::class);
    }

    public function procedureConsumables()
    {
        return $this->hasMany(ProcedureConsumable::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_available', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySpecialty($query, $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    public function scopeByComplexity($query, $complexity)
    {
        return $query->where('complexity_level', $complexity);
    }

    public function scopeEmergency($query)
    {
        return $query->where('is_emergency_procedure', true);
    }

    public function scopeDayCase($query)
    {
        return $query->where('is_day_case', true);
    }

    public function scopeRequiresImplants($query)
    {
        return $query->where('requires_implants', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('procedure_name', 'like', "%{$term}%")
              ->orWhere('procedure_code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
