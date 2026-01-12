<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DailyRecord;
use App\Models\UtilityReading;
use App\Enums\UtilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class UtilityReadingUpdateNullFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_gas_with_null_stove_type_fails()
    {
        // 1. Setup
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'utility-reading-edit', 'guard_name' => 'sanctum']);
        $user->givePermissionTo($permission);
        
        $dailyRecord = DailyRecord::factory()->create(['user_id' => $user->id]);
        
        $reading = UtilityReading::factory()->create([
            'daily_record_id' => $dailyRecord->id,
            'category' => UtilityCategory::GAS,
            'meter_value' => 100,
            'location' => 'Kitchen',
            'photo' => 'dummy.jpg',
            'stove_type' => 'Standard',
            'gas_type' => 'LPG'
        ]);

        // 2. Act: Update value to 200, but send stove_type as null (simulating Frontend)
        $response = $this->actingAs($user)->putJson("/api/v1/utility-readings/{$reading->id}", [
            'daily_record_id' => $dailyRecord->id,
            'category' => 'gas',
            'meter_value' => 200,
            'location' => 'Kitchen',
            // Simulating frontend behavior:
            'stove_type' => null, 
            'gas_type' => null
        ]);

        // 3. Assert
        // Expecting 200 OK because stove_type is now nullable
        $response->assertStatus(200);
    }
}
