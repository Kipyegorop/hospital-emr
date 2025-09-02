<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'head_id',
        'location',
        'contact_number',
        'email',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the head of department.
     */
    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    /**
     * Get users in this department.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get appointments for this department.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get consultations for this department.
     */
    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    /**
     * Get wards in this department.
     */
    public function wards()
    {
        return $this->hasMany(Ward::class);
    }

    /**
     * Get lab tests for this department.
     */
    public function labTests()
    {
        return $this->hasMany(LabTest::class);
    }

    /**
     * Scope to get only active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get department by code.
     */
    public static function findByCode($code)
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all department codes.
     */
    public static function getDepartmentCodes()
    {
        return static::pluck('code')->toArray();
    }

    /**
     * Get department names with codes.
     */
    public static function getDepartmentNamesWithCodes()
    {
        return static::pluck('name', 'code')->toArray();
    }

    /**
     * Get department by name.
     */
    public static function findByName($name)
    {
        return static::where('name', $name)->first();
    }

    /**
     * Get departments with their heads.
     */
    public static function withHeads()
    {
        return static::with('head')->get();
    }

    /**
     * Get department statistics.
     */
    public function getStatistics()
    {
        return [
            'total_users' => $this->users()->count(),
            'total_appointments' => $this->appointments()->count(),
            'total_consultations' => $this->consultations()->count(),
            'total_wards' => $this->wards()->count(),
            'total_beds' => $this->wards()->withSum('beds', 'total_beds')->get()->sum('beds_sum_total_beds'),
            'available_beds' => $this->wards()->withSum('beds', 'available_beds')->get()->sum('beds_sum_available_beds'),
        ];
    }
}
