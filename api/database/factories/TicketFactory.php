<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'code' => fake()->unique()->bothify('TCKT-####'),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(TicketPriority::values()),
            'status' => TicketStatus::OPEN->value,
        ];
    }
}
