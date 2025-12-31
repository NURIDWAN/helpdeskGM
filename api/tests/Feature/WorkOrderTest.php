<?php

use App\Models\User;
use App\Models\Ticket;
use App\Models\Branch;
use App\Models\WorkOrder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

test('admin can view work orders', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/work-orders/all/paginated?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure(['success', 'data']);
});

test('admin can create work order for ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create([
        'branch_id' => $branch->id,
    ]);

    $response = actingAs($admin)
        ->postJson('/api/v1/work-orders', [
            'ticket_id' => $ticket->id,
            'assigned_to' => $staff->id,
            'description' => 'Work order description',
            'damage_unit' => 'AC Unit',
            'contact_person' => 'John Doe',
            'contact_phone' => '08123456789',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);
});

test('staff can view their assigned work orders', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create(['branch_id' => $branch->id]);
    $ticket->assignedStaff()->attach($staff->id);

    $workOrder = WorkOrder::factory()->create([
        'ticket_id' => $ticket->id,
        'assigned_to' => $staff->id,
    ]);

    actingAs($staff)
        ->getJson("/api/v1/work-orders/{$workOrder->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $workOrder->id);
});
