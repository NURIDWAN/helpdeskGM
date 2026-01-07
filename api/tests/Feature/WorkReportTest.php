<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\WorkOrder;
use App\Models\WorkReport;
use App\Enums\WorkReportStatus;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST WORK REPORTS TESTS
// =====================================================

test('admin can list all work reports', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    WorkReport::factory()->count(3)->create();

    actingAs($admin)
        ->getJson('/api/v1/work-reports')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'description', 'status']
            ]
        ]);
});

test('admin can list paginated work reports', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    WorkReport::factory()->count(15)->create();

    actingAs($admin)
        ->getJson('/api/v1/work-reports/all/paginated?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'current_page',
                'per_page',
                'total'
            ]
        ]);
});

test('staff can list own work reports', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create(['branch_id' => $branch->id]);
    $workOrder = WorkOrder::factory()->create([
        'ticket_id' => $ticket->id,
        'assigned_to' => $staff->id,
    ]);
    WorkReport::factory()->create([
        'user_id' => $staff->id,
        'work_order_id' => $workOrder->id,
        'branch_id' => $branch->id,
    ]);

    actingAs($staff)
        ->getJson('/api/v1/work-reports')
        ->assertOk();
});

// =====================================================
// CREATE WORK REPORT TESTS
// =====================================================

test('staff can create work report for assigned work order', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create(['branch_id' => $branch->id]);
    $workOrder = WorkOrder::factory()->create([
        'ticket_id' => $ticket->id,
        'assigned_to' => $staff->id,
    ]);

    actingAs($staff)
        ->postJson('/api/v1/work-reports', [
            'work_order_id' => $workOrder->id,
            'description' => 'Work completed successfully',
            'status' => WorkReportStatus::COMPLETED->value,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.description', 'Work completed successfully');
});

test('work report requires work order id', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    actingAs($staff)
        ->postJson('/api/v1/work-reports', [
            'description' => 'Work description',
            'status' => WorkReportStatus::PROGRESS->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['work_order_id']);
});

// =====================================================
// UPDATE WORK REPORT TESTS
// =====================================================

test('staff can update own work report', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $workReport = WorkReport::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $branch->id,
        'status' => WorkReportStatus::PROGRESS->value,
    ]);

    actingAs($staff)
        ->putJson("/api/v1/work-reports/{$workReport->id}", [
            'status' => WorkReportStatus::COMPLETED->value,
            'description' => 'Updated description',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', WorkReportStatus::COMPLETED->value);
});

test('admin can update any work report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workReport = WorkReport::factory()->create();

    actingAs($admin)
        ->putJson("/api/v1/work-reports/{$workReport->id}", [
            'description' => 'Admin updated description',
        ])
        ->assertOk();
});

// =====================================================
// DELETE WORK REPORT TESTS
// =====================================================

test('admin can delete work report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workReport = WorkReport::factory()->create();

    actingAs($admin)
        ->deleteJson("/api/v1/work-reports/{$workReport->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(WorkReport::find($workReport->id))->toBeNull();
});

// =====================================================
// VIEW SINGLE WORK REPORT TESTS
// =====================================================

test('can view single work report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workReport = WorkReport::factory()->create();

    actingAs($admin)
        ->getJson("/api/v1/work-reports/{$workReport->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $workReport->id);
});

test('view returns 404 for non-existent work report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/work-reports/99999')
        ->assertStatus(404);
});
