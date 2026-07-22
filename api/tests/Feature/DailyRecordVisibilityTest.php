<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DailyRecord;
use App\Models\Branch;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DailyRecordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_user_role_can_see_all_daily_records()
    {
        // 1. Setup Data
        $branch = Branch::factory()->create();
        
        // Create User A (The viewer) - assign 'user' role
        $userA = User::factory()->create(['branch_id' => $branch->id]);
        $userA->assignRole('user');

        // Create User B (The other user)
        $userB = User::factory()->create(['branch_id' => $branch->id]);
        
        // Create Daily Record for User B (same branch as User A)
        $recordB = DailyRecord::factory()->create([
            'user_id' => $userB->id,
            'branch_id' => $branch->id,
            'date' => now()->format('Y-m-d'),
            'total_customers' => 50
        ]);

        // 2. Act: User A tries to view the list
        $response = $this->actingAs($userA)->getJson('/api/v1/daily-records');

        // 3. Assert
        $response->assertStatus(200);
        
        // Assert that User A can see User B's record (same branch)
        $response->assertJsonFragment(['id' => $recordB->id]);
        $response->assertJsonFragment(['total_customers' => 50]);
    }
}
