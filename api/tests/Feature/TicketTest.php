<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Branch;
use App\Models\TicketCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_view_tickets()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);

        $response = $this->getJson('/api/v1/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'status']
                ]
            ]);
    }

    public function test_user_can_create_ticket()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);

        $branch = Branch::factory()->create();
        $category = TicketCategory::factory()->create();

        $data = [
            'title' => 'Feature Test Ticket',
            'description' => 'Description from feature test',
            'priority' => 'high',
            'category_id' => $category->id,
            'branch_id' => $branch->id,
            'status' => 'open'
        ];

        $response = $this->postJson('/api/v1/tickets', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', $data['title']);
    }

    public function test_user_can_view_single_ticket()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ticket->id);
    }
}
