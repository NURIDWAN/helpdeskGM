<?php

namespace Database\Factories;

use App\Enums\JobTemplateFrequency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobTemplate>
 */
class JobTemplateFactory extends Factory
{
    public function definition(): array
    {
        $frequencies = [
            JobTemplateFrequency::DAILY->value,
            JobTemplateFrequency::WEEKLY->value,
            JobTemplateFrequency::MONTHLY->value,
            JobTemplateFrequency::QUARTERLY->value,
            JobTemplateFrequency::YEARLY->value,
            JobTemplateFrequency::ON_DEMAND->value,
        ];

        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->paragraph(),
            'frequency' => fake()->randomElement($frequencies),
            'is_active' => true,
            'schedule_details' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn(array $attributes) => [
            'frequency' => JobTemplateFrequency::DAILY->value,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn(array $attributes) => [
            'frequency' => JobTemplateFrequency::WEEKLY->value,
            'schedule_details' => ['days' => [1, 3, 5]], // Mon, Wed, Fri
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn(array $attributes) => [
            'frequency' => JobTemplateFrequency::MONTHLY->value,
            'schedule_details' => ['day_of_month' => 1],
        ]);
    }
}
