<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson};

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);
});

// =====================================================
// LIST ROLES TESTS
// =====================================================

test('admin can list all roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name']
            ]
        ]);
});

test('roles include permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'permissions']
            ]
        ]);
});

// =====================================================
// LIST PERMISSIONS TESTS
// =====================================================

test('admin can list all permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->getJson('/api/v1/permissions')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data'
        ]);
});

// =====================================================
// CREATE ROLE TESTS
// =====================================================

test('admin can create role with permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $permissions = Permission::take(3)->pluck('name')->toArray();

    actingAs($admin)
        ->postJson('/api/v1/roles', [
            'name' => 'test-role',
            'permissions' => $permissions,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $role = Role::where('name', 'test-role')->first();
    expect($role)->not->toBeNull();
    expect($role->permissions->count())->toBe(3);
});

test('role creation requires name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/roles', [
            'permissions' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('cannot create role with duplicate name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->postJson('/api/v1/roles', [
            'name' => 'admin',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

// =====================================================
// UPDATE ROLE TESTS
// =====================================================

test('admin can update role permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'custom-role', 'guard_name' => 'web']);
    $newPermissions = Permission::take(2)->pluck('name')->toArray();

    actingAs($admin)
        ->putJson("/api/v1/roles/{$role->id}", [
            'name' => 'updated-role',
            'permissions' => $newPermissions,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $role->refresh();
    expect($role->name)->toBe('updated-role');
    expect($role->permissions->count())->toBe(2);
});

test('update returns 404 for non-existent role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)
        ->putJson('/api/v1/roles/99999', [
            'name' => 'updated-role',
        ])
        ->assertStatus(404);
});

// =====================================================
// DELETE ROLE TESTS
// =====================================================

test('admin can delete custom role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'deletable-role', 'guard_name' => 'web']);

    actingAs($admin)
        ->deleteJson("/api/v1/roles/{$role->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Role::find($role->id))->toBeNull();
});

test('cannot delete system roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $adminRole = Role::where('name', 'admin')->first();

    actingAs($admin)
        ->deleteJson("/api/v1/roles/{$adminRole->id}")
        ->assertStatus(422);
});

// =====================================================
// VIEW SINGLE ROLE TESTS
// =====================================================

test('admin can view single role with permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::where('name', 'admin')->first();

    actingAs($admin)
        ->getJson("/api/v1/roles/{$role->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'admin')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'permissions']
        ]);
});
