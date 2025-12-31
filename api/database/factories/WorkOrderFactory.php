<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'assigned_to' => User::factory(),
            'number' => fake()->unique()->bothify('WO-####'),
            'description' => fake()->paragraph(),
            'status' => WorkOrderStatus::PENDING->value,
            'damage_unit' => fake()->sentence(3),
            'contact_person' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
        ];
    }
}
