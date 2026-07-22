<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DailyRecord;
use App\Models\UtilityReading;
use App\Enums\UtilityCategory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UtilityReadingFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_previous_readings_have_object_category()
    {
        // 1. Create a user with proper role and branch
        $branch = \App\Models\Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('user');

        // 2. Create a "Previous" DailyRecord with UtilityReadings
        $prevRecord = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'date' => now()->subDay()->format('Y-m-d'),
        ]);
        
        UtilityReading::factory()->create([
            'daily_record_id' => $prevRecord->id,
            'category' => UtilityCategory::GAS, 
            'meter_value' => 100,
            'location' => 'Test Kitchen'
        ]);

        // 3. Create a "Current" DailyRecord (so we can call show($current->id))
        $currentRecord = DailyRecord::factory()->create([
            'branch_id' => $branch->id,
            'date' => now()->format('Y-m-d'),
        ]);

        // 4. Call the API
        $response = $this->actingAs($user)->getJson("/api/v1/daily-records/{$currentRecord->id}");

        // 5. Check the structure
        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Check previous_readings structure
        $this->assertArrayHasKey('previous_readings', $data);
        $this->assertArrayHasKey('utility', $data['previous_readings']);
        
        // Ensure 'utility' is a direct list (indexed array)
        $this->assertIsList($data['previous_readings']['utility'], "previous_readings.utility should be a list");
        
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
