<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ward;
use App\Models\Department;

class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' Ward',
            'code' => strtoupper($this->faker->bothify('W-###')),
            'department_id' => Department::factory(),
            'ward_type' => 'general',
            'total_beds' => 10,
            'available_beds' => 10,
            'floor' => (string) $this->faker->numberBetween(1,5),
            'building' => 'Main',
            'location_description' => $this->faker->sentence(),
            'ward_sister_id' => null,
            'contact_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'special_equipment' => null,
            'has_private_rooms' => false,
            'has_air_conditioning' => false,
            'has_oxygen_supply' => true,
            'status' => 'active',
            'notes' => null,
        ];
    }
}

