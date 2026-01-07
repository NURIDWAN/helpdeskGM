<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\JobTemplate;
use App\Enums\WorkReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkReport>
 */
class WorkReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'work_order_id' => WorkOrder::factory(),
            'job_template_id' => null,
            'description' => fake()->paragraph(),
            'custom_job' => fake()->optional()->sentence(),
            'status' => WorkReportStatus::PROGRESS->value,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WorkReportStatus::COMPLETED->value,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WorkReportStatus::FAILED->value,
        ]);
    }

    public function withJobTemplate(): static
    {
        return $this->state(fn(array $attributes) => [
            'job_template_id' => JobTemplate::factory(),
        ]);
    }
}
