<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Department;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' Department',
            'code' => strtoupper($this->faker->bothify('D###')),
            'description' => $this->faker->sentence(),
            'location' => 'Main Building',
            'contact_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'is_active' => true,
        ];
    }
}
