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
use Illuminate\Http\UploadedFile;

class UtilityReadingMultipartTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_meter_value_using_multipart_method_spoofing()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'utility-reading-edit', 'guard_name' => 'sanctum']);
        $user->givePermissionTo($permission);

        $branch = Branch::factory()->create();
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
        
        // Prepare the payload as if it were FormData
        $payload = [
            '_method' => 'PUT',
            'meter_value' => (string) $newMeterValue, // FormData sends strings
            'category' => 'water',
            // 'location' => 'Backyard', // Optional, let's keep it same or omit
        ];

        // Headers for multipart
        $headers = [
            'Accept' => 'application/json',
            // Content-Type is auto-set by Laravel's test helper when using files or standard post, 
            // but for simple array often it sends json.
            // To ensure multipart/form-data simulation in Laravel tests usually implies sending a file 
            // OR using call() with parameters. 
            // actingAs(...)->post(...) handles array as form inputs if notJson.
        ];

        // using 'post' instead of 'postJson' to simulate form data submission
        $response = $this->actingAs($user)->post("/api/v1/utility-readings/{$utilityReading->id}", $payload, $headers);

        // 3. Verify Response
        if ($response->status() !== 200) {
            dump($response->json());
        }
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
