<?php

use App\Models\User;
use App\Models\Branch;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST USERS TESTS
// =====================================================

test('admin can list all users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->count(3)->create();

    actingAs($admin)
        ->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'email']
            ]
        ]);
});

test('admin can list paginated users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->count(15)->create();

    actingAs($admin)
        ->getJson('/api/v1/users/all/paginated?row_per_page=10')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'current_page',
                'per_page',
                'total'
            ]
        ]);
});

test('regular user cannot list users', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    actingAs($user)
        ->getJson('/api/v1/users')
        ->assertStatus(403);
});

// =====================================================
// CREATE USER TESTS
// =====================================================

test('admin can create user with role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $branch = Branch::factory()->create();

    $userData = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'branch_id' => $branch->id,
        'role' => 'staff',
    ];

    actingAs($admin)
        ->postJson('/api/v1/users', $userData)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'New User')
        ->assertJsonPath('data.email', 'newuser@example.com');
});

test('admin cannot create user with duplicate email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    actingAs($admin)
        ->postJson('/api/v1/users', $userData)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user creation requires valid password confirmation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $userData = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ];

    actingAs($admin)
        ->postJson('/api/v1/users', $userData)
        ->assertStatus(422);
});

// =====================================================
// UPDATE USER TESTS
// =====================================================

test('admin can update user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['name' => 'Old Name']);

    actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Updated Name',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

test('admin can update user email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['email' => 'old@example.com']);

    actingAs($admin)
        ->putJson("/api/v1/users/{$user->id}", [
            'email' => 'new@example.com',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'new@example.com');
});

// =====================================================
// DELETE USER TESTS
// =====================================================

test('admin can delete user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    actingAs($admin)
        ->deleteJson("/api/v1/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(User::find($user->id))->toBeNull();
});

test('admin cannot delete non-existent user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->deleteJson('/api/v1/users/99999')
        ->assertStatus(404);
});

// =====================================================
// VIEW SINGLE USER TESTS
// =====================================================

test('admin can view single user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    actingAs($admin)
        ->getJson("/api/v1/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

test('returns 404 for non-existent user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/users/99999')
        ->assertStatus(404);
});
