<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Patient;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition()
    {
        return [
            'patient_number' => 'P' . date('Y') . $this->faker->unique()->numerify('####'),
            'uhid' => 'UHID' . date('Y') . $this->faker->unique()->numerify('######'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male','female','other']),
            'phone' => $this->faker->phoneNumber(),
            'status' => 'active',
        ];
    }
}
