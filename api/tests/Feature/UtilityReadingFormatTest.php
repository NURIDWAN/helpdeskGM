<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DailyRecord;
use App\Models\UtilityReading;
use App\Enums\UtilityCategory;
use App\Enums\UtilitySubType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UtilityReadingFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_previous_readings_have_object_category()
    {
        // 1. Create a user and authenticate
        $user = User::factory()->create();
        
        // Setup permissions
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'daily-record-list', 'guard_name' => 'sanctum']);
        $user->givePermissionTo($permission);
        
        // Create a Branch
        $branch = \App\Models\Branch::factory()->create();

        // 2. Create a "Previous" DailyRecord with UtilityReadings
        $prevRecord = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'created_at' => now()->subDay(),
        ]);
        
        $prevUtility = UtilityReading::factory()->create([
            'daily_record_id' => $prevRecord->id,
            'category' => UtilityCategory::GAS, 
            'meter_value' => 100,
            'location' => 'Test Kitchen'
        ]);

        // 3. Create a "Current" DailyRecord (so we can call show($current->id))
        $currentRecord = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'created_at' => now(),
        ]);

        // 4. Call the API
        $response = $this->actingAs($user)->getJson("/api/v1/daily-records/{$currentRecord->id}");

        // 5. Check the structure
        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Check previous_readings structure
        $this->assertArrayHasKey('previous_readings', $data);
        $this->assertArrayHasKey('utility', $data['previous_readings']);
        
        // DEBUG: Dump the structure if needed or just assert strict type
        // Ensure 'utility' is a direct list (indexed array), NOT an associative array with 'data' key
        $this->assertIsList($data['previous_readings']['utility'], "previous_readings.utility should be a list, maybe it is wrapped in 'data'?");
        
        $prevUtilityData = $data['previous_readings']['utility'][0];
        
        // CRITICAL CHECK: category should be an array/object, not a string
        $this->assertIsArray($prevUtilityData['category'], "Category should be an array (object in JSON)");
        $this->assertArrayHasKey('value', $prevUtilityData['category']);
        $this->assertArrayHasKey('label', $prevUtilityData['category']);
        $this->assertEquals('gas', $prevUtilityData['category']['value']);
        $this->assertEquals('Gas', $prevUtilityData['category']['label']);
        
        // Ensure location matches what we expect
        $this->assertEquals('Test Kitchen', $prevUtilityData['location']);
    }
}
