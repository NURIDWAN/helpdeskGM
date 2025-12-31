<?php

use App\Models\User;
use App\Models\Ticket;
use App\Models\Branch;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson};

beforeEach(function () {
    // Seed permissions and roles for every test
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

test('user can view tickets', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    actingAs($user)
        ->getJson('/api/v1/tickets/all/paginated?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure(['success', 'data']);
});

test('admin can create ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();

    // Create a staff user for the branch (required by business logic)
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $response = actingAs($admin)
        ->postJson('/api/v1/tickets', [
            'title' => 'Test Ticket',
            'description' => 'Description',
            'priority' => 'medium',
            'branch_id' => $branch->id,
            'status' => 'open'
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);
});

test('unauthorized user cannot create ticket', function () {
    // A user with no permissions
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/v1/tickets', [
            'title' => 'Fail Ticket',
            'description' => 'Desc',
            'priority' => 'low',
        ])
        ->assertStatus(403);
});

test('user can view their own ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
    ]);

    actingAs($user)
        ->getJson("/api/v1/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id);
});
