<?php

namespace Database\Factories;

use App\Models\DailyRecord;
use App\Enums\UtilityCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UtilityReading>
 */
class UtilityReadingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_record_id' => DailyRecord::factory(),
            'category' => UtilityCategory::WATER->value,
            'sub_type' => null,
            'location' => fake()->word() . ' ' . fake()->randomElement(['Kitchen', 'Bathroom', 'Garden', 'Main']),
            'meter_value' => fake()->randomFloat(2, 100, 10000),
            'photo' => null,
        ];
    }

    public function gas(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => UtilityCategory::GAS->value,
            'stove_type' => fake()->randomElement(['Industrial', 'Commercial', 'Standard']),
            'gas_type' => fake()->randomElement(['LPG', 'Natural Gas']),
        ]);
    }

    public function water(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => UtilityCategory::WATER->value,
        ]);
    }

    public function electricity(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => UtilityCategory::ELECTRICITY->value,
            'meter_value_wbp' => fake()->randomFloat(2, 100, 5000),
            'meter_value_lwbp' => fake()->randomFloat(2, 100, 5000),
        ]);
    }
}
