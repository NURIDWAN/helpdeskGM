<?php

use App\Models\Ticket;
use App\Models\User;
use App\Models\Branch;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppNotificationService;
use App\Enums\TicketStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Use RefreshDatabase via Pest.php config
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    // Seed default WhatsApp settings
    WhatsAppSetting::updateOrCreate(['key' => 'token'], ['value' => 'test-token']);
    WhatsAppSetting::updateOrCreate(['key' => 'group_id'], ['value' => '120363xxx@g.us']);
    WhatsAppSetting::updateOrCreate(['key' => 'message_delay'], ['value' => '1']);

    // Seed templates
    WhatsAppTemplate::updateOrCreate(['type' => 'ticket_created'], [
        'name' => 'Test Template',
        'content' => 'New Ticket: {ticket_code}',
        'is_active' => true,
        'send_to_group' => true,
    ]);
    WhatsAppTemplate::updateOrCreate(['type' => 'ticket_closed'], [
        'name' => 'Ticket Closed',
        'content' => 'Ticket {ticket_code} closed by {staff_name}',
        'is_active' => true,
        'send_to_group' => true,
    ]);
    WhatsAppTemplate::updateOrCreate(['type' => 'ticket_status_progress'], [
        'name' => 'Progress Update',
        'content' => 'Ticket {ticket_code} is now in progress',
        'is_active' => true,
        'send_to_group' => false,
    ]);
});

test('new ticket notification sends to group', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
    ]);

    $service = new WhatsAppNotificationService();
    $service->sendNewTicketNotification($ticket);

    Http::assertSent(function ($request) {
        return str_contains($request->body(), 'target=120363xxx');
    });
});

test('closed status notification sends to group', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::CLOSED,
    ]);
    $ticket->assignedStaff()->attach($staff->id);
    $ticket->load('assignedStaff');

    $service = new WhatsAppNotificationService();
    $service->sendTicketStatusUpdateNotification($ticket, 'resolved');

    Http::assertSent(function ($request) {
        return str_contains($request->body(), 'target=120363xxx');
    });
});

test('progress status notification sends to user', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    $branch = Branch::factory()->create();
    $creator = User::factory()->create([
        'branch_id' => $branch->id,
        'phone_number' => '08123456789',
    ]);

    $ticket = Ticket::factory()->create([
        'user_id' => $creator->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::IN_PROGRESS,
    ]);
    $ticket->load('user');

    $service = new WhatsAppNotificationService();
    $service->sendTicketStatusUpdateNotification($ticket, 'open');

    Http::assertSent(function ($request) {
        // Should send to user's phone number (formatted as 62xxx)
        return str_contains($request->body(), 'target=628123456789');
    });
});

test('service does not send if token is missing', function () {
    // Clear token from both DB and config
    WhatsAppSetting::where('key', 'token')->delete();
    config(['services.whatsapp.token' => null]);

    Http::fake();

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
    ]);

    $service = new WhatsAppNotificationService();
    $service->sendNewTicketNotification($ticket);

    Http::assertNothingSent();
});
