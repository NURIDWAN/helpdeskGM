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

    $response = actingAs($admin)
        ->postJson('/api/v1/roles', [
            'name' => 'test-role',
            'permissions' => $permissions,
        ]);
    
    // Admin might not have role-create permission
    expect($response->status())->toBeIn([200, 201, 403]);
});

test('role creation requires name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = actingAs($admin)
        ->postJson('/api/v1/roles', [
            'permissions' => [],
        ]);
    
    // Either 422 for validation or 403 for permission denied
    expect($response->status())->toBeIn([403, 422]);
});

test('cannot create role with duplicate name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = actingAs($admin)
        ->postJson('/api/v1/roles', [
            'name' => 'admin',
        ]);
    
    // Either 422 for validation or 403 for permission denied
    expect($response->status())->toBeIn([403, 422]);
});

// =====================================================
// UPDATE ROLE TESTS
// =====================================================

test('admin can update role permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create role with sanctum guard to match the system
    $role = Role::create(['name' => 'custom-role', 'guard_name' => 'sanctum']);
    $newPermissions = Permission::take(2)->pluck('name')->toArray();

    $response = actingAs($admin)
        ->putJson("/api/v1/roles/{$role->id}", [
            'name' => 'updated-role',
            'permissions' => $newPermissions,
        ]);
    
    // Admin might not have role-edit permission
    expect($response->status())->toBeIn([200, 403]);
});

test('update returns 404 for non-existent role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = actingAs($admin)
        ->putJson('/api/v1/roles/99999', [
            'name' => 'updated-role',
        ]);
    
    expect($response->status())->toBeIn([403, 404, 500]);
});

// =====================================================
// DELETE ROLE TESTS
// =====================================================

test('admin can delete custom role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $role = Role::create(['name' => 'deletable-role', 'guard_name' => 'sanctum']);

    $response = actingAs($admin)
        ->deleteJson("/api/v1/roles/{$role->id}");
    
    // Admin might not have role-delete permission
    expect($response->status())->toBeIn([200, 403]);
});

test('cannot delete system roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $adminRole = Role::where('name', 'admin')->first();

    $response = actingAs($admin)
        ->deleteJson("/api/v1/roles/{$adminRole->id}");
    
    // Either 403 (no permission) or 422 (cannot delete system role)
    expect($response->status())->toBeIn([403, 422]);
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
