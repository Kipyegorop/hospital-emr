<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission($permission)
    {
        if (!$this->permissions) {
            return false;
        }
        return in_array($permission, $this->permissions);
    }

    /**
     * Check if role has any of the specified permissions.
     */
    public function hasAnyPermission($permissions)
    {
        if (!$this->permissions) {
            return false;
        }
        if (is_array($permissions)) {
            return !empty(array_intersect($permissions, $this->permissions));
        }
        return $this->hasPermission($permissions);
    }

    /**
     * Check if role has all of the specified permissions.
     */
    public function hasAllPermissions($permissions)
    {
        if (!$this->permissions) {
            return false;
        }
        if (is_array($permissions)) {
            return empty(array_diff($permissions, $this->permissions));
        }
        return $this->hasPermission($permissions);
    }

    /**
     * Scope to get only active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get role by name.
     */
    public static function findByName($name)
    {
        return static::where('name', $name)->first();
    }

    /**
     * Get all role names.
     */
    public static function getRoleNames()
    {
        return static::pluck('name')->toArray();
    }

    /**
     * Get role display names.
     */
    public static function getRoleDisplayNames()
    {
        return static::pluck('display_name', 'name')->toArray();
    }
}
