<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UtilityReading;
use App\Models\DailyRecord;
use App\Models\Branch;
use App\Enums\UtilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class UtilityReadingUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_meter_value_without_sending_category()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'utility-reading-edit', 'guard_name' => 'sanctum']);
        $user->givePermissionTo($permission);

        $branch = Branch::factory()->create();
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
        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('utility_readings', [
            'id' => $utilityReading->id,
            'meter_value' => $newMeterValue,
            'category' => 'gas' // Ensure category wasn't lost or mutated
        ]);
    }
}
