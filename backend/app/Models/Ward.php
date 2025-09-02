<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','code','department_id','ward_type','total_beds','available_beds','floor','building','location_description','ward_sister_id','contact_number','email','special_equipment','has_private_rooms','has_air_conditioning','has_oxygen_supply','status','notes'
    ];

    protected $casts = [
        'special_equipment' => 'array',
        'has_private_rooms' => 'boolean',
        'has_air_conditioning' => 'boolean',
        'has_oxygen_supply' => 'boolean',
    ];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
