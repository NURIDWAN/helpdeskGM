<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DailyRecord;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\ElectricityReading;
use App\Models\ElectricityMeter;
use App\Enums\UtilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageFormulaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_is_sum_of_opening_and_closing()
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        // Day 1 (To set Opening)
        $record1 = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        // Gas Day 1
        UtilityReading::factory()->create([
            'daily_record_id' => $record1->id,
            'category' => UtilityCategory::GAS->value,
            'meter_value' => 10.00,
            'location' => 'Kitchen',
        ]);

        // Water Day 1
        UtilityReading::factory()->create([
            'daily_record_id' => $record1->id,
            'category' => UtilityCategory::WATER->value,
            'meter_value' => 20.00,
            'location' => 'Sink',
        ]);

        // Electricity Day 1
         ElectricityReading::factory()->create([
            'daily_record_id' => $record1->id,
             'electricity_meter_id' => ElectricityMeter::factory()->create(['branch_id' => $branch->id])->id,
            'meter_value' => 100.00,
        ]);


        // Day 2 (Current)
        $record2 = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Gas Day 2
        UtilityReading::factory()->create([
            'daily_record_id' => $record2->id,
            'category' => UtilityCategory::GAS->value,
            'meter_value' => 30.00, // Opening=10, Closing=30. Usage SHOULD BE 40.
            'location' => 'Kitchen',
        ]);

        // Water Day 2
        UtilityReading::factory()->create([
            'daily_record_id' => $record2->id,
            'category' => UtilityCategory::WATER->value,
            'meter_value' => 50.00, // Opening=20, Closing=50. Usage SHOULD BE 70.
            'location' => 'Sink',
        ]);
        
        // Electricity Day 2
         ElectricityReading::factory()->create([
            'daily_record_id' => $record2->id,
            'electricity_meter_id' => ElectricityMeter::first()->id,
            'meter_value' => 200.00, // Opening=100, Closing=200. Usage SHOULD BE 300.
        ]);

        // Call API
        $response = $this->getJson("/api/v1/daily-records/report/daily-usage?branch_id={$branch->id}");
        $response->assertStatus(200);

        $data = $response->json('data');
        $day2 = $data[1]; // Index 1 is Day 2

        // Verify Gas
        // Note: API returns 'gas' object
        $this->assertEquals(40.00, $day2['gas']['usage'], 'Gas Usage should be Sum (10+30)');

        // Verify Water
        // API returns 'water' array
        $this->assertEquals(70.00, $day2['water'][0]['usage'], 'Water Usage should be Sum (20+50)');

        // Verify Electricity
        // API returns 'electricity' array
        $this->assertEquals(300.00, $day2['electricity'][0]['usage'], 'Electricity Usage should be Sum (100+200)');
    }
}
