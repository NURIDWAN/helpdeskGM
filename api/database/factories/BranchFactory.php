<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('BR???'),
            'name' => fake()->city() . ' Branch',
            'address' => fake()->address(),
            'logo' => null,
        ];
    }
}
