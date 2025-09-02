<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Medication;

class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition()
    {
        return [
            'medication_code' => strtoupper($this->faker->bothify('MED-###')),
            'name' => $this->faker->word() . ' ' . $this->faker->randomNumber(3),
            'generic_name' => $this->faker->word(),
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'manufacturer' => $this->faker->company(),
            'supplier' => $this->faker->company(),
            'therapeutic_class' => 'Analgesic',
            'pharmacological_class' => 'NSAID',
            'is_controlled' => false,
            'requires_prescription' => true,
            'current_stock' => 10,
            'minimum_stock' => 5,
            'maximum_stock' => 100,
            'unit' => 'tablet',
            'storage_location' => 'Shelf A1',
            'unit_cost' => 10.00,
            'selling_price' => 15.00,
            'nhif_price' => 12.00,
            'currency' => 'KES',
            'expiry_date' => null,
            'batch_number' => null,
            'manufacturing_date' => null,
            'status' => 'active',
            'is_available' => true,
            'description' => null,
        ];
    }
}
