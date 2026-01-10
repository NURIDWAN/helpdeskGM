<?php

use App\Models\Ticket;
use App\Models\User;
use App\Models\Branch;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use App\Enums\TicketStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    // Seed default WhatsApp settings
    WhatsAppSetting::updateOrCreate(['key' => 'token'], ['value' => 'test-token']);
    WhatsAppSetting::updateOrCreate(['key' => 'group_id'], ['value' => '120363xxx@g.us']);
    WhatsAppSetting::updateOrCreate(['key' => 'delay'], ['value' => '1']);

    // Seed unassigned templates
    WhatsAppTemplate::updateOrCreate(['type' => 'ticket_unassigned_user_alert'], [
        'name' => 'Alert Unassigned (User)',
        'content' => 'Tiket {ticket_code} belum di-assign',
        'is_active' => true,
        'send_to_group' => false,
    ]);
    WhatsAppTemplate::updateOrCreate(['type' => 'ticket_unassigned_admin_alert'], [
        'name' => 'Alert Unassigned (Admin)',
        'content' => 'Alert: Tiket {ticket_code} belum di-assign > 1 jam',
        'is_active' => true,
        'send_to_group' => true,
    ]);
});

test('command sends alert for unassigned tickets older than 1 hour', function () {
    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    $branch = Branch::factory()->create();
    $user = User::factory()->create([
        'branch_id' => $branch->id,
        'phone_number' => '08123456789',
    ]);
    $user->assignRole('user');

    // Create ticket older than 1 hour, not assigned
    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::OPEN,
        'created_at' => now()->subHours(2),
        'unassigned_alert_sent_at' => null,
    ]);

    $this->artisan('tickets:check-unassigned')
        ->expectsOutput('Checking for unassigned tickets...')
        ->expectsOutput('Found 1 tickets.')
        ->expectsOutput("Sending alert for ticket {$ticket->code}...")
        ->expectsOutput('Done.')
        ->assertExitCode(0);

    // Verify alert was sent (HTTP request made)
    Http::assertSent(function ($request) {
        return true; // At least one request was made
    });

    // Verify ticket was marked as alerted
    $ticket->refresh();
    expect($ticket->unassigned_alert_sent_at)->not->toBeNull();
});

test('command skips tickets already alerted', function () {
    Http::fake();

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    // Create ticket that was already alerted
    Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::OPEN,
        'created_at' => now()->subHours(2),
        'unassigned_alert_sent_at' => now()->subMinutes(30), // Already alerted
    ]);

    $this->artisan('tickets:check-unassigned')
        ->expectsOutput('Checking for unassigned tickets...')
        ->expectsOutput('No unassigned tickets found requiring alert.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('command skips tickets created less than 1 hour ago', function () {
    Http::fake();

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    // Create ticket less than 1 hour old
    Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::OPEN,
        'created_at' => now()->subMinutes(30), // Only 30 minutes old
        'unassigned_alert_sent_at' => null,
    ]);

    $this->artisan('tickets:check-unassigned')
        ->expectsOutput('Checking for unassigned tickets...')
        ->expectsOutput('No unassigned tickets found requiring alert.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('command skips tickets that have assigned staff', function () {
    Http::fake();

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    $staff = User::factory()->create(['branch_id' => $branch->id]);
    $staff->assignRole('staff');

    // Create ticket older than 1 hour but has staff assigned
    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::OPEN,
        'created_at' => now()->subHours(2),
        'unassigned_alert_sent_at' => null,
    ]);
    $ticket->assignedStaff()->attach($staff->id);

    $this->artisan('tickets:check-unassigned')
        ->expectsOutput('Checking for unassigned tickets...')
        ->expectsOutput('No unassigned tickets found requiring alert.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

test('command skips non-open tickets', function () {
    Http::fake();

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['branch_id' => $branch->id]);
    $user->assignRole('user');

    // Create ticket with status in_progress (not open)
    Ticket::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'status' => TicketStatus::IN_PROGRESS,
        'created_at' => now()->subHours(2),
        'unassigned_alert_sent_at' => null,
    ]);

    $this->artisan('tickets:check-unassigned')
        ->expectsOutput('Checking for unassigned tickets...')
        ->expectsOutput('No unassigned tickets found requiring alert.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});
