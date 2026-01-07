<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Ticket;
use App\Models\WorkReport;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// DASHBOARD METRICS TESTS
// =====================================================

test('admin can get dashboard metrics', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/dashboard/metrics')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('user without permission cannot access dashboard metrics', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    actingAs($user)
        ->getJson('/api/v1/dashboard/metrics')
        ->assertStatus(403);
});

// =====================================================
// STATUS DISTRIBUTION TESTS
// =====================================================

test('admin can get status distribution', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Ticket::factory()->count(5)->create();

    actingAs($admin)
        ->getJson('/api/v1/dashboard/status-distribution')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// TICKETS PER BRANCH TESTS
// =====================================================

test('admin can get tickets per branch', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();
    Ticket::factory()->count(3)->create(['branch_id' => $branch->id]);

    actingAs($admin)
        ->getJson('/api/v1/dashboard/tickets-per-branch')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// TOP STAFF RESOLVED TESTS
// =====================================================

test('admin can get top staff resolved', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/dashboard/top-staff-resolved')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// FASTEST STAFF TESTS
// =====================================================

test('admin can get fastest staff', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/dashboard/fastest-staff')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// TICKETS TREND TESTS
// =====================================================

test('admin can get tickets trend by day', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Ticket::factory()->count(5)->create();

    actingAs($admin)
        ->getJson('/api/v1/dashboard/tickets-trend?period=day')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('admin can get tickets trend by week', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/dashboard/tickets-trend?period=week')
        ->assertOk();
});

// =====================================================
// STAFF REPORTS TREND TESTS
// =====================================================

test('admin can get staff reports trend', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    WorkReport::factory()->count(3)->create();

    actingAs($admin)
        ->getJson('/api/v1/dashboard/staff-reports-trend?period=day')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// ALL DASHBOARD DATA TESTS
// =====================================================

test('admin can get all dashboard data', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/dashboard/all')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'metrics',
                'status_distribution',
                'tickets_per_branch',
                'top_staff_resolved',
                'fastest_staff',
                'tickets_trend',
                'staff_reports_trend',
            ]
        ]);
});

test('all dashboard data accepts period parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/dashboard/all?period=week')
        ->assertOk();
});
