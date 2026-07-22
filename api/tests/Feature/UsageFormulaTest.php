<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DailyRecord;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\ElectricityReading;
use App\Models\ElectricityMeter;
use App\Enums\UtilityCategory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageFormulaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_usage_is_sum_of_opening_and_closing()
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('admin');
        $this->actingAs($user);

        // Day 1 (To set Opening)
        $record1 = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'date' => now()->subDay()->format('Y-m-d'),
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
        $meter = ElectricityMeter::factory()->create(['branch_id' => $branch->id]);
        ElectricityReading::factory()->create([
            'daily_record_id' => $record1->id,
            'electricity_meter_id' => $meter->id,
            'meter_value' => 100.00,
        ]);

        // Day 2 (Current)
        $record2 = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
        ]);

        // Gas Day 2
        UtilityReading::factory()->create([
            'daily_record_id' => $record2->id,
            'category' => UtilityCategory::GAS->value,
            'meter_value' => 30.00,
            'location' => 'Kitchen',
        ]);

        // Water Day 2
        UtilityReading::factory()->create([
            'daily_record_id' => $record2->id,
            'category' => UtilityCategory::WATER->value,
            'meter_value' => 50.00,
            'location' => 'Sink',
        ]);
        
        // Electricity Day 2
        ElectricityReading::factory()->create([
            'daily_record_id' => $record2->id,
            'electricity_meter_id' => $meter->id,
            'meter_value' => 200.00,
        ]);

        // Call API
        $response = $this->getJson("/api/v1/daily-records/report/daily-usage?branch_id={$branch->id}");
        $response->assertStatus(200);

        $data = $response->json('data');
        // The report returns rows ordered by date, so index 1 is Day 2
        $this->assertNotEmpty($data);
        // Find the record for today
        $day2 = collect($data)->last();

        // Verify Gas - usage = closing - opening = 30 - 10 = 20
        $this->assertEquals(20.00, $day2['gas']['usage'], 'Gas Usage should be Closing - Opening (30-10)');

        // Verify Water - usage = closing - opening = 50 - 20 = 30
        $this->assertEquals(30.00, $day2['water'][0]['usage'], 'Water Usage should be Closing - Opening (50-20)');

        // Verify Electricity - usage = closing - opening = 200 - 100 = 100
        $this->assertEquals(100.00, $day2['electricity'][0]['usage'], 'Electricity Usage should be Closing - Opening (200-100)');
    }
}
