<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ElectricityMeter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs, assertDatabaseHas, postJson};

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->branch = Branch::factory()->create();

    // Create and assign permissions
    Permission::create(['name' => 'electricity-meter-create']);
    Permission::create(['name' => 'electricity-meter-edit']);

    $this->user->givePermissionTo(['electricity-meter-create', 'electricity-meter-edit']);
});

test('cannot create meter with duplicate meter_number in same branch', function () {
    actingAs($this->user);

    // Create first meter
    $meterData = [
        'branch_id' => $this->branch->id,
        'meter_name' => 'Meter 1',
        'meter_number' => 'MTR-DUPLICATE-TEST',
        'location' => 'Location 1',
        'power_capacity' => 100,
        'is_active' => true,
    ];

    postJson('/api/v1/electricity-meters', $meterData)
        ->assertStatus(201);

    // Try to create second meter with same meter_number
    $duplicateData = [
        'branch_id' => $this->branch->id,
        'meter_name' => 'Meter 2',
        'meter_number' => 'MTR-DUPLICATE-TEST', // Same number
        'location' => 'Location 2',
        'power_capacity' => 200,
        'is_active' => true,
    ];

    postJson('/api/v1/electricity-meters', $duplicateData)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['meter_number']);
});

test('cannot create meter with duplicate meter_number in different branch', function () {
    actingAs($this->user);

    $branch2 = Branch::factory()->create();

    // Create first meter in branch 1
    $meterData = [
        'branch_id' => $this->branch->id,
        'meter_name' => 'Meter 1',
        'meter_number' => 'MTR-GLOBAL-TEST',
        'location' => 'Location 1',
        'power_capacity' => 100,
        'is_active' => true,
    ];

    postJson('/api/v1/electricity-meters', $meterData)
        ->assertStatus(201);

    // Try to create second meter with same meter_number in branch 2
    $duplicateData = [
        'branch_id' => $branch2->id,
        'meter_name' => 'Meter 2',
        'meter_number' => 'MTR-GLOBAL-TEST', // Same number
        'location' => 'Location 2',
        'power_capacity' => 200,
        'is_active' => true,
    ];

    postJson('/api/v1/electricity-meters', $duplicateData)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['meter_number']);
});

test('can update meter keeping the same meter_number', function () {
    actingAs($this->user);

    // Create meter
    $meter = ElectricityMeter::factory()->create([
        'branch_id' => $this->branch->id,
        'meter_number' => 'MTR-UPDATE-TEST',
    ]);

    // Update meter with same meter_number (should be allowed)
    $updateData = [
        'branch_id' => $this->branch->id,
        'meter_name' => 'Updated Meter Name',
        'meter_number' => 'MTR-UPDATE-TEST', // Keep same number
        'location' => 'Updated Location',
        'power_capacity' => 150,
        'is_active' => true,
    ];

    $response = $this->putJson("/api/v1/electricity-meters/{$meter->id}", $updateData)
        ->assertStatus(200);

    assertDatabaseHas('electricity_meters', [
        'id' => $meter->id,
        'meter_number' => 'MTR-UPDATE-TEST',
        'meter_name' => 'Updated Meter Name',
    ]);
});

test('cannot update meter to duplicate another meters meter_number', function () {
    actingAs($this->user);

    // Create two meters
    $meter1 = ElectricityMeter::factory()->create([
        'branch_id' => $this->branch->id,
        'meter_number' => 'MTR-FIRST',
    ]);

    $meter2 = ElectricityMeter::factory()->create([
        'branch_id' => $this->branch->id,
        'meter_number' => 'MTR-SECOND',
    ]);

    // Try to update meter2 to use meter1's number
    $updateData = [
        'branch_id' => $this->branch->id,
        'meter_number' => 'MTR-FIRST', // Duplicate of meter1
        'location' => 'Updated Location',
        'is_active' => true,
    ];

    $this->putJson("/api/v1/electricity-meters/{$meter2->id}", $updateData)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['meter_number']);
});

test('cannot create duplicate meter_number with different whitespace', function () {
    actingAs($this->user);

    // Create first meter
    $meterData = [
        'branch_id' => $this->branch->id,
        'meter_name' => 'Meter 1',
        'meter_number' => 'MTR-SPACE',
        'is_active' => true,
    ];

    postJson('/api/v1/electricity-meters', $meterData)
        ->assertStatus(201);

    // Try to create second meter with trailing space
    $duplicateData = [
        'branch_id' => $this->branch->id,
        'meter_name' => 'Meter 2',
        'meter_number' => 'MTR-SPACE ', // Trailing space
        'is_active' => true,
    ];

    // Should still fail if trimmed or DB treats as same
    postJson('/api/v1/electricity-meters', $duplicateData)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['meter_number']);
});

test('database constraint prevents duplicate meter_number', function () {
    // This test directly inserts into database to verify constraint
    $meter1 = ElectricityMeter::create([
        'branch_id' => $this->branch->id,
        'meter_name' => 'Meter 1',
        'meter_number' => 'MTR-CONSTRAINT-TEST',
        'is_active' => true,
    ]);

    // Try to create duplicate via raw insert (bypassing validation)
    try {
        ElectricityMeter::create([
            'branch_id' => $this->branch->id,
            'meter_name' => 'Meter 2',
            'meter_number' => 'MTR-CONSTRAINT-TEST',
            'is_active' => true,
        ]);
        
        // If we reach here, constraint failed
        expect(true)->toBe(false, 'Database constraint should have prevented duplicate');
    } catch (\Illuminate\Database\QueryException $e) {
        // Expected: constraint violation
        expect($e->getCode())->toBe('23000'); // Integrity constraint violation
    }
});
