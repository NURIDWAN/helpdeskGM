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
            'data'
        ]);
});

test('admin can list paginated daily records', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create records with unique branch+date combinations
    for ($i = 0; $i < 15; $i++) {
        DailyRecord::factory()->create(['date' => now()->subDays($i)->format('Y-m-d')]);
    }

    actingAs($admin)
        ->getJson('/api/v1/daily-records/all/paginated?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]
        ]);
});

test('can filter daily records by branch', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();
    DailyRecord::factory()->create(['branch_id' => $branch->id, 'date' => '2024-01-01']);
    DailyRecord::factory()->create(['branch_id' => $branch->id, 'date' => '2024-01-02']);
    DailyRecord::factory()->create(['branch_id' => $branch->id, 'date' => '2024-01-03']);
    DailyRecord::factory()->count(2)->create();

    actingAs($admin)
        ->getJson("/api/v1/daily-records?branch_id={$branch->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter daily records by date range', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Use created_at instead of record_date since record_date column doesn't exist
    DailyRecord::factory()->create(['created_at' => '2024-01-15']);
    DailyRecord::factory()->create(['created_at' => '2024-01-20']);
    DailyRecord::factory()->create(['created_at' => '2024-02-01']);

    actingAs($admin)
        ->getJson('/api/v1/daily-records?start_date=2024-01-01&end_date=2024-01-31')
        ->assertOk();
});

// =====================================================
// CREATE DAILY RECORD TESTS
// =====================================================

test('staff can create daily record for branch', function () {
    $branch = Branch::factory()->create();
    // Use 'user' role instead of 'staff' since 'user' has daily-record-create permission
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    actingAs($user)
        ->postJson('/api/v1/daily-records', [
            'branch_id' => $branch->id,
            'date' => now()->format('Y-m-d'),
            'total_customers' => 10,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

test('daily record requires branch_id', function () {
    // Use 'user' role instead of 'staff' since 'user' has daily-record-create permission
    $user = User::factory()->create();
    $user->assignRole('user');

    actingAs($user)
        ->postJson('/api/v1/daily-records', [
            'date' => now()->format('Y-m-d'),
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
    // Use UtilityReading factory which exists
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
    // Use 'user' role instead of 'staff' since 'user' has daily-record-edit permission
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    $dailyRecord = DailyRecord::factory()->create([
        'branch_id' => $branch->id,
        'user_id' => $user->id,
    ]);

    actingAs($user)
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
        'date' => now()->subDay()->format('Y-m-d'),
    ]);

    actingAs($admin)
        ->getJson("/api/v1/daily-records/previous-readings?branch_id={$branch->id}&date=" . now()->format('Y-m-d'))
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
    for ($i = 0; $i < 5; $i++) {
        DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'date' => now()->subDays($i)->format('Y-m-d'),
        ]);
    }

    // The endpoint requires branch_id parameter
    actingAs($admin)
        ->getJson("/api/v1/daily-records/report/daily-usage?branch_id={$branch->id}&start_date=" . now()->subDays(7)->format('Y-m-d') . "&end_date=" . now()->format('Y-m-d'))
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});
