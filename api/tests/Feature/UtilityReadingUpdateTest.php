<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\DailyRecord;
use App\Models\Branch;
use App\Enums\UtilityCategory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UtilityReadingUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_can_update_meter_value_without_sending_category()
    {
        // 1. Setup Data
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('user');

        $dailyRecord = DailyRecord::factory()->create(['branch_id' => $branch->id]);

        $utilityReading = UtilityReading::factory()->create([
            'daily_record_id' => $dailyRecord->id,
            'category' => UtilityCategory::GAS,
            'meter_value' => 100.00,
            'location' => 'Kitchen',
            'stove_type' => '2 Burner',
            'gas_type' => 'LPG 12kg',
            'photo' => 'dummy/path.jpg'
        ]);

        // 2. Perform Update (Only meter_value, NO category)
        $newMeterValue = 150.00;
        $response = $this->actingAs($user)->putJson("/api/v1/utility-readings/{$utilityReading->id}", [
            'meter_value' => $newMeterValue,
            // category is intentionally omitted
        ]);

        // 3. Verify Response and Persistence
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('utility_readings', [
            'id' => $utilityReading->id,
            'meter_value' => $newMeterValue,
            'category' => 'gas' // Ensure category wasn't lost or mutated
        ]);
    }
}
