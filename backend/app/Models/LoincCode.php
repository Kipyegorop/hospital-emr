<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoincCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'loinc_num',
        'component',
        'property',
        'time_aspct',
        'system',
        'scale_typ',
        'method_typ',
        'class',
        'long_common_name',
        'short_name',
        'consumer_name',
        'status',
        'version_first_released',
        'version_last_changed',
        'kenya_code',
        'moh_code',
        'is_common',
        'department',
        'standard_price',
        'specimen_requirements',
        'reference_range',
        'turnaround_time_hours',
    ];

    protected $casts = [
        'is_common' => 'boolean',
        'standard_price' => 'decimal:2',
    ];

    /**
     * Get common LOINC codes for quick selection
     */
    public static function getCommonCodes($department = null)
    {
        $query = static::where('is_common', true)
                      ->where('status', 'ACTIVE');

        if ($department) {
            $query->where('department', $department);
        }

        return $query->orderBy('component')->get();
    }

    /**
     * Search LOINC codes by term
     */
    public static function search($term, $department = null, $limit = 50)
    {
        $query = static::where('status', 'ACTIVE')
                      ->where(function ($q) use ($term) {
                          $q->where('component', 'like', "%{$term}%")
                            ->orWhere('long_common_name', 'like', "%{$term}%")
                            ->orWhere('short_name', 'like', "%{$term}%")
                            ->orWhere('loinc_num', 'like', "%{$term}%");
                      });

        if ($department) {
            $query->where('department', $department);
        }

        return $query->orderBy('is_common', 'desc')
                    ->orderBy('component')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get LOINC codes by class (e.g., CHEM, HEMA, MICRO)
     */
    public static function getByClass($class)
    {
        return static::where('class', $class)
                    ->where('status', 'ACTIVE')
                    ->orderBy('component')
                    ->get();
    }

    /**
     * Get formatted display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->short_name ?: $this->component;
    }

    /**
     * Get full LOINC identifier with display name
     */
    public function getFullIdentifierAttribute()
    {
        return "{$this->loinc_num} - {$this->display_name}";
    }

    /**
     * Check if this is a quantitative test
     */
    public function getIsQuantitativeAttribute()
    {
        return $this->scale_typ === 'Qn';
    }

    /**
     * Check if this is a qualitative test
     */
    public function getIsQualitativeAttribute()
    {
        return $this->scale_typ === 'Nom' || $this->scale_typ === 'Ord';
    }

    /**
     * Relationships
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeCommon($query)
    {
        return $query->where('is_common', true);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByClass($query, $class)
    {
        return $query->where('class', $class);
    }

    public function scopeQuantitative($query)
    {
        return $query->where('scale_typ', 'Qn');
    }

    public function scopeQualitative($query)
    {
        return $query->whereIn('scale_typ', ['Nom', 'Ord']);
    }
}
