<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\DailyRecord;
use App\Models\ElectricityMeter;
use App\Models\ElectricityReading;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{postJson, putJson, deleteJson, getJson, actingAs};

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole('user'); // user role has electricity-reading-create permission
    $this->branch = Branch::factory()->create();
    $this->dailyRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
    ]);
    $this->electricityMeter = ElectricityMeter::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
});

test('can create electricity reading with meter_value and photo', function () {
    $photo = UploadedFile::fake()->image('meter.jpg');

    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings', [
            'daily_record_id' => $this->dailyRecord->id,
            'electricity_meter_id' => $this->electricityMeter->id,
            'meter_value' => 12345.67,
            'photo' => $photo,
        ])
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'daily_record_id',
                'electricity_meter_id',
                'meter_value',
                'photo',
            ]
        ])
        ->assertJsonPath('data.meter_value', '12345.67'); // JSON returns string for decimal
});

test('electricity reading requires photo', function () {
    // Photo is required for electricity readings per validation rules
    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings', [
            'daily_record_id' => $this->dailyRecord->id,
            'electricity_meter_id' => $this->electricityMeter->id,
            'meter_value' => 9999.99,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['photo']);
});

test('can update electricity reading meter_value', function () {
    $reading = ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $this->electricityMeter->id,
        'meter_value' => 1000.00,
    ]);

    actingAs($this->user)
        ->putJson("/api/v1/electricity-readings/{$reading->id}", [
            'meter_value' => 2000.50,
        ])
        ->assertOk()
        ->assertJsonPath('data.meter_value', '2000.50'); // JSON returns string for decimal
});

test('can update electricity reading photo', function () {
    $reading = ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $this->electricityMeter->id,
        'meter_value' => 1000.00,
    ]);

    $newPhoto = UploadedFile::fake()->image('new_meter.jpg');

    // meter_value is required for update per validation rules
    actingAs($this->user)
        ->putJson("/api/v1/electricity-readings/{$reading->id}", [
            'meter_value' => 1000.00, // must include meter_value as it's required
            'photo' => $newPhoto,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $reading->refresh();
    expect($reading->photo)->not->toBeNull();
});

test('can delete electricity reading', function () {
    // Admin has electricity-reading-delete permission
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $reading = ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $this->electricityMeter->id,
        'meter_value' => 1000.00,
    ]);

    actingAs($admin)
        ->deleteJson("/api/v1/electricity-readings/{$reading->id}")
        ->assertOk();

    expect(ElectricityReading::find($reading->id))->toBeNull();
});

test('can store multiple electricity readings with existing readings', function () {
    // Create existing readings (photos are only required for NEW readings)
    $meter1 = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);
    $meter2 = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);
    
    // Create existing readings so photo is not required
    ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $meter1->id,
        'meter_value' => 50.00,
    ]);
    ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $meter2->id,
        'meter_value' => 75.00,
    ]);

    // Update existing readings (no photo required)
    actingAs($this->user)
        ->postJson("/api/v1/daily-records/{$this->dailyRecord->id}/electricity-readings/multiple", [
            'readings' => [
                [
                    'electricity_meter_id' => $meter1->id,
                    'meter_value' => 100.00,
                ],
                [
                    'electricity_meter_id' => $meter2->id,
                    'meter_value' => 200.00,
                ],
            ],
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    // Verify readings were updated
    $reading1 = ElectricityReading::where('daily_record_id', $this->dailyRecord->id)
        ->where('electricity_meter_id', $meter1->id)
        ->first();
    $reading2 = ElectricityReading::where('daily_record_id', $this->dailyRecord->id)
        ->where('electricity_meter_id', $meter2->id)
        ->first();
    
    expect((float)$reading1->meter_value)->toBe(100.00);
    expect((float)$reading2->meter_value)->toBe(200.00);
});

test('electricity reading requires valid daily_record_id', function () {
    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings', [
            'daily_record_id' => 99999,
            'electricity_meter_id' => $this->electricityMeter->id,
            'meter_value' => 100.00,
        ])
        ->assertStatus(422);
});

test('electricity reading requires valid electricity_meter_id', function () {
    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings', [
            'daily_record_id' => $this->dailyRecord->id,
            'electricity_meter_id' => 99999,
            'meter_value' => 100.00,
        ])
        ->assertStatus(422);
});

test('meter_value must be numeric', function () {
    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings', [
            'daily_record_id' => $this->dailyRecord->id,
            'electricity_meter_id' => $this->electricityMeter->id,
            'meter_value' => 'not-a-number',
        ])
        ->assertStatus(422);
});

test('electricity reading model has correct fillable attributes', function () {
    $reading = new ElectricityReading();

    expect($reading->getFillable())->toContain('meter_value')
        ->toContain('photo')
        ->toContain('daily_record_id')
        ->toContain('electricity_meter_id');
});

test('electricity reading model does not have wbp/lwbp attributes', function () {
    $reading = new ElectricityReading();

    expect($reading->getFillable())->not->toContain('meter_value_wbp')
        ->not->toContain('meter_value_lwbp')
        ->not->toContain('photo_wbp')
        ->not->toContain('photo_lwbp');
});
