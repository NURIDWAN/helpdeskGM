<?php

namespace Database\Factories;

use App\Models\ElectricityReading;
use App\Models\DailyRecord;
use App\Models\ElectricityMeter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElectricityReadingFactory extends Factory
{
    protected $model = ElectricityReading::class;

    public function definition(): array
    {
        return [
            'daily_record_id' => DailyRecord::factory(),
            'electricity_meter_id' => ElectricityMeter::factory(),
            'meter_value' => $this->faker->randomFloat(2, 1000, 99999),
            'photo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the reading has a photo
     */
    public function withPhoto(): static
    {
        return $this->state(fn(array $attributes) => [
            'photo' => 'electricity_readings/' . $this->faker->uuid() . '.jpg',
        ]);
    }
}
