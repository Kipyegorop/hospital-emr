<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'department_id',
        'employee_id',
        'profile_photo',
        'status',
        'settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /**
     * Get the user's role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user's department.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get appointments where this user is the doctor.
     */
    public function doctorAppointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    /**
     * Get consultations where this user is the doctor.
     */
    public function doctorConsultations()
    {
        return $this->hasMany(Consultation::class, 'doctor_id');
    }

    /**
     * Get prescriptions where this user is the doctor.
     */
    public function doctorPrescriptions()
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    /**
     * Get lab tests requested by this user.
     */
    public function requestedLabTests()
    {
        return $this->hasMany(LabTest::class, 'requested_by');
    }

    /**
     * Get lab tests collected by this user.
     */
    public function collectedLabTests()
    {
        return $this->hasMany(LabTest::class, 'collected_by');
    }

    /**
     * Get lab tests performed by this user.
     */
    public function performedLabTests()
    {
        return $this->hasMany(LabTest::class, 'performed_by');
    }

    /**
     * Get prescriptions dispensed by this user.
     */
    public function dispensedPrescriptions()
    {
        return $this->hasMany(Prescription::class, 'dispensed_by');
    }

    /**
     * Get bills created by this user.
     */
    public function createdBills()
    {
        return $this->hasMany(Bill::class, 'created_by');
    }

    /**
     * Get NHIF claims submitted by this user.
     */
    public function submittedNhifClaims()
    {
        return $this->hasMany(NhifClaim::class, 'submitted_by');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole($role)
    {
        return $this->role->name === $role;
    }

    /**
     * Check if user has any of the specified roles.
     */
    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            return in_array($this->role->name, $roles);
        }
        return $this->hasRole($roles);
    }

    /**
     * Check if user is a doctor.
     */
    public function isDoctor()
    {
        return $this->hasRole('doctor');
    }

    /**
     * Check if user is a nurse.
     */
    public function isNurse()
    {
        return $this->hasRole('nurse');
    }

    /**
     * Check if user is a pharmacist.
     */
    public function isPharmacist()
    {
        return $this->hasRole('pharmacist');
    }

    /**
     * Check if user is a lab technician.
     */
    public function isLabTech()
    {
        return $this->hasRole('lab_tech');
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Get user's full name.
     */
    public function getFullNameAttribute()
    {
        return trim($this->name);
    }

    /**
     * Get user's display name with role.
     */
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->role->display_name . ')';
    }
}
