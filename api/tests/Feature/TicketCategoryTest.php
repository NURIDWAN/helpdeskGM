<?php

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST CATEGORIES TESTS
// =====================================================

test('authenticated user can list all categories', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    TicketCategory::factory()->count(5)->create();

    actingAs($user)
        ->getJson('/api/v1/ticket-categories')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name']
            ]
        ]);
});

test('categories list supports search', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    TicketCategory::factory()->create(['name' => 'Network Issue']);
    TicketCategory::factory()->create(['name' => 'Hardware Problem']);

    actingAs($admin)
        ->getJson('/api/v1/ticket-categories?search=Network')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// =====================================================
// CREATE CATEGORY TESTS
// =====================================================

test('admin can create category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/ticket-categories', [
            'name' => 'New Category',
            'description' => 'Category description',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'New Category');
});

test('category name is required', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/ticket-categories', [
            'description' => 'Only description',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('cannot create duplicate category name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    TicketCategory::factory()->create(['name' => 'Existing Category']);

    actingAs($admin)
        ->postJson('/api/v1/ticket-categories', [
            'name' => 'Existing Category',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

// =====================================================
// UPDATE CATEGORY TESTS
// =====================================================

test('admin can update category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $category = TicketCategory::factory()->create(['name' => 'Old Name']);

    actingAs($admin)
        ->putJson("/api/v1/ticket-categories/{$category->id}", [
            'name' => 'Updated Name',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

// =====================================================
// DELETE CATEGORY TESTS
// =====================================================

test('admin can delete category without tickets', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $category = TicketCategory::factory()->create();

    actingAs($admin)
        ->deleteJson("/api/v1/ticket-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(TicketCategory::find($category->id))->toBeNull();
});

test('cannot delete category with associated tickets', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $category = TicketCategory::factory()->create();
    Ticket::factory()->create(['category_id' => $category->id]);

    actingAs($admin)
        ->deleteJson("/api/v1/ticket-categories/{$category->id}")
        ->assertStatus(422);
});

// =====================================================
// VIEW SINGLE CATEGORY TESTS
// =====================================================

test('can view single category', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $category = TicketCategory::factory()->create();

    actingAs($admin)
        ->getJson("/api/v1/ticket-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id);
});
