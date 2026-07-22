<?php

namespace Database\Factories;

use App\Models\DailyRecord;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyRecordFactory extends Factory
{
    protected $model = DailyRecord::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'date' => $this->faker->unique()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'total_customers' => $this->faker->numberBetween(0, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
