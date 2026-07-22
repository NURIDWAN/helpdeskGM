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

class UtilityReadingMultipartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_can_update_meter_value_using_multipart_method_spoofing()
    {
        // 1. Setup Data
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('user');

        $dailyRecord = DailyRecord::factory()->create(['branch_id' => $branch->id]);

        $initialValue = 100.00;
        $utilityReading = UtilityReading::factory()->create([
            'daily_record_id' => $dailyRecord->id,
            'category' => UtilityCategory::WATER,
            'meter_value' => $initialValue,
            'location' => 'Backyard',
            'photo' => 'dummy/path.jpg'
        ]);

        // 2. Perform Update (Simulate Frontend: POST with _method=PUT)
        $newMeterValue = 150.00;
        
        $payload = [
            '_method' => 'PUT',
            'meter_value' => (string) $newMeterValue,
            'category' => 'water',
        ];

        $headers = [
            'Accept' => 'application/json',
        ];

        $response = $this->actingAs($user)->post("/api/v1/utility-readings/{$utilityReading->id}", $payload, $headers);

        // 3. Verify Response
        $response->assertStatus(200);
        
        // 4. Verify Database
        $this->assertDatabaseHas('utility_readings', [
            'id' => $utilityReading->id,
            'meter_value' => $newMeterValue,
            'category' => 'water'
        ]);
        
        // Ensure it actually changed
        $this->assertDatabaseMissing('utility_readings', [
             'id' => $utilityReading->id,
             'meter_value' => $initialValue
        ]);
    }
}
