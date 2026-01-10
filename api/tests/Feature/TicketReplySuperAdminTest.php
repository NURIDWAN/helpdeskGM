<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TicketReplySuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private $superadmin;
    private $ticket;
    private $replies;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $role = Role::create(['name' => 'superadmin', 'guard_name' => 'sanctum']);
        
        // Create superadmin user
        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole($role);

        // Create a ticket owned by another user
        $otherUser = User::factory()->create();
        $this->ticket = Ticket::factory()->create([
            'user_id' => $otherUser->id
        ]);
        
        // Create replies
        $this->replies = TicketReply::factory()->count(3)->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $otherUser->id
        ]);
    }

    public function test_superadmin_can_view_any_ticket_replies()
    {
        $response = $this->actingAs($this->superadmin)
            ->getJson("/api/v1/tickets/{$this->ticket->id}/replies");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'content', 'user_id', 'created_at']
                ]
            ]);
    }

    public function test_superadmin_can_update_any_ticket_reply()
    {
        $reply = $this->replies->first();
        $updatedContent = 'Updated by Superadmin';

        $response = $this->actingAs($this->superadmin)
            ->putJson("/api/v1/tickets/{$this->ticket->id}/replies/{$reply->id}", [
                'content' => $updatedContent
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', $updatedContent);
            
        $this->assertDatabaseHas('ticket_replies', [
            'id' => $reply->id,
            'content' => $updatedContent
        ]);
    }

    public function test_superadmin_can_delete_any_ticket_reply()
    {
        $reply = $this->replies->first();

        $response = $this->actingAs($this->superadmin)
            ->deleteJson("/api/v1/tickets/{$this->ticket->id}/replies/{$reply->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ticket_replies', ['id' => $reply->id]);
    }
}
