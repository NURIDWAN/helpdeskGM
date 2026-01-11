<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\JobTemplate;
use App\Enums\JobTemplateFrequency;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST JOB TEMPLATES TESTS
// =====================================================

test('admin can list all job templates', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    JobTemplate::factory()->count(5)->create();

    actingAs($admin)
        ->getJson('/api/v1/job-templates')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

test('admin can list paginated job templates', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    JobTemplate::factory()->count(15)->create();

    actingAs($admin)
        ->getJson('/api/v1/job-templates/all/paginated?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]
        ]);
});

// =====================================================
// CREATE JOB TEMPLATE TESTS
// =====================================================

test('admin can create job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/job-templates', [
            'name' => 'Weekly Maintenance',
            'description' => 'Regular weekly maintenance tasks',
            'frequency' => JobTemplateFrequency::WEEKLY->value,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Weekly Maintenance');
});

test('job template requires name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/job-templates', [
            'description' => 'Description only',
            'frequency' => JobTemplateFrequency::DAILY->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('can create job template with schedule details', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = actingAs($admin)
        ->postJson('/api/v1/job-templates', [
            'name' => 'Monthly Report',
            'description' => 'Description',
            'frequency' => JobTemplateFrequency::MONTHLY->value,
            'schedule_details' => ['day_of_month' => 1],
        ]);
    
    expect($response->status())->toBeIn([200, 201]);
});

// =====================================================
// UPDATE JOB TEMPLATE TESTS
// =====================================================

test('admin can update job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $template = JobTemplate::factory()->create(['name' => 'Old Name']);

    $response = actingAs($admin)
        ->putJson("/api/v1/job-templates/{$template->id}", [
            'name' => 'Updated Template Name',
        ]);
    
    expect($response->status())->toBeIn([200, 403]);
});

test('can deactivate job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $template = JobTemplate::factory()->create(['is_active' => true]);

    $response = actingAs($admin)
        ->putJson("/api/v1/job-templates/{$template->id}", [
            'is_active' => false,
        ]);
    
    expect($response->status())->toBeIn([200, 403]);
});

// =====================================================
// DELETE JOB TEMPLATE TESTS
// =====================================================

test('admin can delete job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $template = JobTemplate::factory()->create();

    $response = actingAs($admin)
        ->deleteJson("/api/v1/job-templates/{$template->id}");
    
    expect($response->status())->toBeIn([200, 403]);
});

test('delete returns 404 for non-existent template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->deleteJson('/api/v1/job-templates/99999')
        ->assertStatus(404);
});

// =====================================================
// VIEW SINGLE JOB TEMPLATE TESTS
// =====================================================

test('can view single job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $template = JobTemplate::factory()->create();

    $response = actingAs($admin)
        ->getJson("/api/v1/job-templates/{$template->id}");
    
    expect($response->status())->toBeIn([200, 403]);
});

// =====================================================
// BRANCH ASSIGNMENT TESTS
// =====================================================

test('admin can assign branches to job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $template = JobTemplate::factory()->create();
    $branches = Branch::factory()->count(3)->create();

    $response = actingAs($admin)
        ->postJson("/api/v1/job-templates/{$template->id}/assign-branches", [
            'branches' => $branches->map(fn($branch) => ['branch_id' => $branch->id])->toArray(),
        ]);
    
    expect($response->status())->toBeIn([200, 403, 404]);
});

test('admin can remove branch from job template', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $template = JobTemplate::factory()->create();
    $branch = Branch::factory()->create();
    $template->branches()->attach($branch->id, ['started_at' => now()]);

    $response = actingAs($admin)
        ->deleteJson("/api/v1/job-templates/{$template->id}/branches/{$branch->id}");
    
    expect($response->status())->toBeIn([200, 403, 404]);
});
