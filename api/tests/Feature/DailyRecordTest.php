<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\DailyRecord;
use App\Models\UtilityReading;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST DAILY RECORDS TESTS
// =====================================================

test('admin can list all daily records', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    DailyRecord::factory()->count(3)->create();

    actingAs($admin)
        ->getJson('/api/v1/daily-records')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'branch_id', 'user_id']
            ]
        ]);
});

test('admin can list paginated daily records', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    DailyRecord::factory()->count(15)->create();

    actingAs($admin)
        ->getJson('/api/v1/daily-records/all/paginated?row_per_page=10')
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

test('can filter daily records by branch', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();
    DailyRecord::factory()->count(3)->create(['branch_id' => $branch->id]);
    DailyRecord::factory()->count(2)->create();

    actingAs($admin)
        ->getJson("/api/v1/daily-records?branch_id={$branch->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter daily records by date range', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    DailyRecord::factory()->create(['record_date' => '2024-01-15']);
    DailyRecord::factory()->create(['record_date' => '2024-01-20']);
    DailyRecord::factory()->create(['record_date' => '2024-02-01']);

    actingAs($admin)
        ->getJson('/api/v1/daily-records?start_date=2024-01-01&end_date=2024-01-31')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

// =====================================================
// CREATE DAILY RECORD TESTS
// =====================================================

test('staff can create daily record for branch', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    actingAs($staff)
        ->postJson('/api/v1/daily-records', [
            'branch_id' => $branch->id,
            'record_date' => now()->format('Y-m-d'),
            'notes' => 'Daily record notes',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

test('daily record requires branch_id', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    actingAs($staff)
        ->postJson('/api/v1/daily-records', [
            'record_date' => now()->format('Y-m-d'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['branch_id']);
});

// =====================================================
// VIEW DAILY RECORD TESTS
// =====================================================

test('can view single daily record with relations', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $dailyRecord = DailyRecord::factory()->create();
    UtilityReading::factory()->count(2)->create(['daily_record_id' => $dailyRecord->id]);

    actingAs($admin)
        ->getJson("/api/v1/daily-records/{$dailyRecord->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $dailyRecord->id);
});

// =====================================================
// UPDATE DAILY RECORD TESTS
// =====================================================

test('staff can update daily record', function () {
    $branch = Branch::factory()->create();
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $dailyRecord = DailyRecord::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $staff->id,
    ]);

    actingAs($staff)
        ->putJson("/api/v1/daily-records/{$dailyRecord->id}", [
            'notes' => 'Updated notes',
        ])
        ->assertOk();
});

// =====================================================
// DELETE DAILY RECORD TESTS
// =====================================================

test('admin can delete daily record', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $dailyRecord = DailyRecord::factory()->create();

    actingAs($admin)
        ->deleteJson("/api/v1/daily-records/{$dailyRecord->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(DailyRecord::find($dailyRecord->id))->toBeNull();
});

// =====================================================
// PREVIOUS READINGS TESTS
// =====================================================

test('can get previous readings for branch', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();
    DailyRecord::factory()->create([
        'branch_id' => $branch->id,
        'record_date' => now()->subDay()->format('Y-m-d'),
    ]);

    actingAs($admin)
        ->getJson("/api/v1/daily-records/previous-readings?branch_id={$branch->id}")
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// DAILY USAGE REPORT TESTS
// =====================================================

test('can get daily usage report', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();
    DailyRecord::factory()->count(5)->create(['branch_id' => $branch->id]);

    actingAs($admin)
        ->getJson('/api/v1/daily-records/report/daily-usage')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});
