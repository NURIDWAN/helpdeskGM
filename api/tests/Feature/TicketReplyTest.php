<?php

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketReply;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST REPLIES TESTS
// =====================================================

test('user can list replies for own ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);
    TicketReply::factory()->count(3)->create(['ticket_id' => $ticket->id]);

    actingAs($user)
        ->getJson("/api/v1/tickets/{$ticket->id}/replies")
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'content', 'user_id']
            ]
        ]);
});

test('admin can list replies for any ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();
    TicketReply::factory()->count(2)->create(['ticket_id' => $ticket->id]);

    actingAs($admin)
        ->getJson("/api/v1/tickets/{$ticket->id}/replies")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

// =====================================================
// CREATE REPLY TESTS
// =====================================================

test('user can reply to own ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->id}/replies", [
            'content' => 'This is my reply to the ticket.',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content', 'This is my reply to the ticket.');
});

test('admin can reply to any ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();

    actingAs($admin)
        ->postJson("/api/v1/tickets/{$ticket->id}/replies", [
            'content' => 'Admin reply to ticket.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $admin->id);
});

test('reply content is required', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->id}/replies", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('cannot reply to non-existent ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    actingAs($user)
        ->postJson('/api/v1/tickets/99999/replies', [
            'content' => 'Reply content',
        ])
        ->assertStatus(404);
});

// =====================================================
// UPDATE REPLY TESTS
// =====================================================

test('user can update own reply', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);
    $reply = TicketReply::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'content' => 'Original content',
    ]);

    actingAs($user)
        ->putJson("/api/v1/tickets/{$ticket->id}/replies/{$reply->id}", [
            'content' => 'Updated content',
        ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Updated content');
});

// =====================================================
// DELETE REPLY TESTS
// =====================================================

test('user can delete own reply', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);
    $reply = TicketReply::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
    ]);

    actingAs($user)
        ->deleteJson("/api/v1/tickets/{$ticket->id}/replies/{$reply->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(TicketReply::find($reply->id))->toBeNull();
});

test('admin can delete any reply', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();
    $reply = TicketReply::factory()->create(['ticket_id' => $ticket->id]);

    actingAs($admin)
        ->deleteJson("/api/v1/tickets/{$ticket->id}/replies/{$reply->id}")
        ->assertOk();
});

// =====================================================
// VIEW SINGLE REPLY TESTS
// =====================================================

test('can view single reply', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);
    $reply = TicketReply::factory()->create(['ticket_id' => $ticket->id]);

    actingAs($user)
        ->getJson("/api/v1/tickets/{$ticket->id}/replies/{$reply->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $reply->id);
});
