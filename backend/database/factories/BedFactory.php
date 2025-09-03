<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Bed;
use App\Models\Ward;

class BedFactory extends Factory
{
    protected $model = Bed::class;

    public function definition()
    {
        return [
            'ward_id' => Ward::factory(),
            'bed_number' => strtoupper($this->faker->bothify('B##')),
            'bed_code' => strtoupper($this->faker->bothify('BED-###')),
            'bed_type' => 'standard',
            'bed_size' => 'standard',
            'has_oxygen' => false,
            'has_monitor' => false,
            'has_ventilator' => false,
            'special_features' => null,
            'status' => 'available',
            'current_patient_id' => null,
            'occupied_at' => null,
            'expected_vacancy_at' => null,
            'bed_history' => null,
            'last_maintenance_date' => null,
            'next_maintenance_date' => null,
            'maintenance_notes' => null,
            'daily_rate' => null,
            'rate_type' => 'standard',
        ];
    }
}
