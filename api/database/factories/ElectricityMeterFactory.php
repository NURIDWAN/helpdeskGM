<?php

namespace Database\Factories;

use App\Models\ElectricityMeter;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElectricityMeterFactory extends Factory
{
    protected $model = ElectricityMeter::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'meter_name' => 'Meter ' . $this->faker->randomNumber(3),
            'meter_number' => $this->faker->unique()->numerify('PLN-#########'),
            'location' => $this->faker->randomElement(['Lantai 1', 'Lantai 2', 'Gedung Utama', 'Gedung Belakang']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
