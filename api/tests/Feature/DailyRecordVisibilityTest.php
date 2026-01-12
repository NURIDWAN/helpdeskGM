<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DailyRecord;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DailyRecordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_can_see_all_daily_records()
    {
        // 1. Setup Data
        $branch = Branch::factory()->create();
        
        // Create User A (The viewer) - assign 'user' role
        $userA = User::factory()->create(['branch_id' => $branch->id]);
        $roleUser = Role::create(['name' => 'user', 'guard_name' => 'sanctum']);
        $permissionList = Permission::create(['name' => 'daily-record-list', 'guard_name' => 'sanctum']);
        $userA->assignRole($roleUser);
        $userA->givePermissionTo($permissionList);

        // Create User B (The other user)
        $userB = User::factory()->create(['branch_id' => $branch->id]);
        
        // Create Daily Record for User B
        $recordB = DailyRecord::factory()->create([
            'user_id' => $userB->id,
            'branch_id' => $branch->id,
            'total_customers' => 50
        ]);

        // 2. Act: User A tries to view the list
        $response = $this->actingAs($userA)->getJson('/api/v1/daily-records');

        // 3. Assert
        $response->assertStatus(200);
        
        // Assert that User A can see User B's record
        $response->assertJsonFragment(['id' => $recordB->id]);
        $response->assertJsonFragment(['total_customers' => 50]);
    }
}
