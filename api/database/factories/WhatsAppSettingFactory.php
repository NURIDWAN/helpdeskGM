<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppSetting>
 */
class WhatsAppSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->word(),
        ];
    }

    public function apiUrl(): static
    {
        return $this->state(fn(array $attributes) => [
            'key' => 'api_url',
            'value' => 'https://api.whatsapp.test/v1',
        ]);
    }

    public function apiKey(): static
    {
        return $this->state(fn(array $attributes) => [
            'key' => 'api_key',
            'value' => 'test-api-key-123',
        ]);
    }

    public function groupId(): static
    {
        return $this->state(fn(array $attributes) => [
            'key' => 'group_id',
            'value' => '123456789@g.us',
        ]);
    }

    public function enabled(): static
    {
        return $this->state(fn(array $attributes) => [
            'key' => 'enabled',
            'value' => 'true',
        ]);
    }
}
