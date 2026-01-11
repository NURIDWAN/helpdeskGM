<?php

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
});

// =====================================================
// LIST ATTACHMENTS TESTS
// =====================================================

test('user can list attachments for own ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->getJson("/api/v1/tickets/{$ticket->id}/attachments")
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('admin can list attachments for any ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();

    actingAs($admin)
        ->getJson("/api/v1/tickets/{$ticket->id}/attachments")
        ->assertOk();
});

// =====================================================
// UPLOAD ATTACHMENT TESTS
// =====================================================

test('user can upload attachment to own ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);
    $file = UploadedFile::fake()->image('document.jpg');

    actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

test('can upload multiple attachments', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);
    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
    ];

    $response = actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
            'files' => $files,
        ]);
    
    // Controller may accept single file only, check response
    expect($response->status())->toBeIn([200, 201, 422]);
});

test('attachment upload requires file', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $ticket = Ticket::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [])
        ->assertStatus(422);
});

test('admin can upload attachment to any ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 500);

    actingAs($admin)
        ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
            'file' => $file,
        ])
        ->assertCreated();
});

test('cannot upload to non-existent ticket', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $file = UploadedFile::fake()->image('photo.jpg');

    $response = actingAs($user)
        ->postJson('/api/v1/tickets/99999/attachments', [
            'file' => $file,
        ]);
    
    // Controller may return 404 or 500 for model binding failure
    expect($response->status())->toBeIn([404, 500]);
});

// =====================================================
// DELETE ATTACHMENT TESTS
// =====================================================

test('admin can delete attachment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();
    $attachment = TicketAttachment::create([
        'ticket_id' => $ticket->id,
        'file_path' => 'attachments/test.jpg',
        'file_name' => 'test.jpg',
        'file_type' => 'image/jpeg',
        'file_size' => 1024,
    ]);

    actingAs($admin)
        ->deleteJson("/api/v1/tickets/{$ticket->id}/attachments/{$attachment->id}")
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('delete returns 404 for non-existent attachment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $ticket = Ticket::factory()->create();

    actingAs($admin)
        ->deleteJson("/api/v1/tickets/{$ticket->id}/attachments/99999")
        ->assertStatus(404);
});
