<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\DailyRecord;
use App\Models\ElectricityMeter;
use App\Models\ElectricityReading;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{postJson, putJson, deleteJson, getJson, actingAs};

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
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
        ->assertJsonPath('data.meter_value', 12345.67);
});

test('can create electricity reading without photo', function () {
    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings', [
            'daily_record_id' => $this->dailyRecord->id,
            'electricity_meter_id' => $this->electricityMeter->id,
            'meter_value' => 9999.99,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.meter_value', 9999.99)
        ->assertJsonPath('data.photo', null);
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
        ->assertJsonPath('data.meter_value', 2000.50);
});

test('can update electricity reading photo', function () {
    $reading = ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $this->electricityMeter->id,
        'meter_value' => 1000.00,
    ]);

    $newPhoto = UploadedFile::fake()->image('new_meter.jpg');

    actingAs($this->user)
        ->putJson("/api/v1/electricity-readings/{$reading->id}", [
            'photo' => $newPhoto,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $reading->refresh();
    expect($reading->photo)->not->toBeNull();
});

test('can delete electricity reading', function () {
    $reading = ElectricityReading::factory()->create([
        'daily_record_id' => $this->dailyRecord->id,
        'electricity_meter_id' => $this->electricityMeter->id,
        'meter_value' => 1000.00,
    ]);

    actingAs($this->user)
        ->deleteJson("/api/v1/electricity-readings/{$reading->id}")
        ->assertOk();

    expect(ElectricityReading::find($reading->id))->toBeNull();
});

test('can store multiple electricity readings', function () {
    $meter1 = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);
    $meter2 = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);

    actingAs($this->user)
        ->postJson('/api/v1/electricity-readings/store-multiple', [
            'daily_record_id' => $this->dailyRecord->id,
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
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(ElectricityReading::where('daily_record_id', $this->dailyRecord->id)->count())->toBe(2);
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
