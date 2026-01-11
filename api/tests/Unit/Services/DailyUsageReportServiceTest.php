<?php

use App\Models\Branch;
use App\Models\User;
use App\Models\DailyRecord;
use App\Models\ElectricityMeter;
use App\Models\ElectricityReading;
use App\Models\UtilityReading;
use App\Services\DailyUsageReportService;
use App\Enums\UtilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DailyUsageReportService();
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create();
});

// =====================================================
// INITIALIZE PREVIOUS CLOSINGS TESTS
// =====================================================

test('initialize previous closings returns empty array when no previous records', function () {
    $filters = [
        'branch_id' => $this->branch->id,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ];

    $result = $this->service->initializePreviousClosings($filters);

    expect($result)->toBeArray();
});

test('initialize previous closings finds last record before start date', function () {
    // Create a previous day record - use created_at instead of record_date
    $previousRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);

    // Create utility reading for it
    UtilityReading::factory()->water()->create([
        'daily_record_id' => $previousRecord->id,
        'meter_value' => 1000.00,
        'location' => 'Main',
    ]);

    $filters = [
        'branch_id' => $this->branch->id,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ];

    $result = $this->service->initializePreviousClosings($filters);

    // Check water has values (water readings are grouped by location)
    expect($result)->toHaveKey('water');
    expect($result['water'])->not->toBeEmpty();
});

// =====================================================
// PROCESS ELECTRICITY READINGS TESTS
// =====================================================

test('process electricity readings handles multi-meter data', function () {
    $meter1 = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);
    $meter2 = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);

    $dailyRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
    ]);

    ElectricityReading::factory()->create([
        'daily_record_id' => $dailyRecord->id,
        'electricity_meter_id' => $meter1->id,
        'meter_value' => 5000.00,
    ]);

    ElectricityReading::factory()->create([
        'daily_record_id' => $dailyRecord->id,
        'electricity_meter_id' => $meter2->id,
        'meter_value' => 3000.00,
    ]);

    $readings = ElectricityReading::where('daily_record_id', $dailyRecord->id)->get();
    $previousClosings = [];

    $result = $this->service->processElectricityReadings($readings, collect([]), $previousClosings);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
});

test('process electricity readings calculates usage from previous readings', function () {
    $meter = ElectricityMeter::factory()->create(['branch_id' => $this->branch->id]);

    $previousRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
        'created_at' => now()->subDay(),
    ]);

    ElectricityReading::factory()->create([
        'daily_record_id' => $previousRecord->id,
        'electricity_meter_id' => $meter->id,
        'meter_value' => 1000.00,
    ]);

    $currentRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    ElectricityReading::factory()->create([
        'daily_record_id' => $currentRecord->id,
        'electricity_meter_id' => $meter->id,
        'meter_value' => 1500.00,
    ]);

    $readings = ElectricityReading::where('daily_record_id', $currentRecord->id)->get();
    // Previous closings format: electricity[meter_id] => numeric value (not array)
    $previousClosings = [
        'electricity' => [
            $meter->id => 1000.00,
        ],
    ];

    $result = $this->service->processElectricityReadings($readings, collect([]), $previousClosings);

    expect($result)->toBeArray();
    expect($result[0]['usage'])->toBe(500.00);
});

// =====================================================
// BUILD REPORT ROW TESTS
// =====================================================

test('build report row creates complete data structure', function () {
    $dailyRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
    ]);

    $gasData = [
        'reading' => null,
        'opening' => 100.00,
        'closing' => 150.00,
        'usage' => 50.00,
    ];

    $waterData = [
        'opening' => 1000.00,
        'closing' => 1200.00,
        'usage' => 200.00,
    ];

    $electricityData = [
        [
            'meter_id' => 1,
            'meter_name' => 'Main',
            'opening' => 5000.00,
            'closing' => 5500.00,
            'usage' => 500.00,
        ],
    ];

    $result = $this->service->buildReportRow($dailyRecord, $gasData, $waterData, $electricityData);

    // Service returns 'tanggal' and 'outlet' instead of 'date' and 'branch_name'
    expect($result)->toHaveKey('tanggal');
    expect($result)->toHaveKey('outlet');
    expect($result)->toHaveKey('gas');
    expect($result)->toHaveKey('water');
    expect($result)->toHaveKey('electricity');
});

test('build report row handles empty electricity data', function () {
    $dailyRecord = DailyRecord::factory()->create([
        'branch_id' => $this->branch->id,
        'user_id' => $this->user->id,
    ]);

    $result = $this->service->buildReportRow($dailyRecord, [], [], []);

    expect($result)->toBeArray();
    expect($result['electricity'])->toBeEmpty();
});
