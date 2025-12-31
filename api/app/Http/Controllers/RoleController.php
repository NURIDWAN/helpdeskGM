<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['role-list']), only: ['index', 'show', 'permissions']),
            new Middleware(PermissionMiddleware::using(['role-create']), only: ['store']),
            new Middleware(PermissionMiddleware::using(['role-edit']), only: ['update']),
            new Middleware(PermissionMiddleware::using(['role-delete']), only: ['destroy']),
        ];
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        try {
            $query = Role::with('permissions');

            if ($request->has('search') && $request->search) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $roles = $query->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name'),
                    'permissions_count' => $role->permissions->count(),
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                ];
            });

            return ResponseHelper::jsonResponse(true, 'Data Role Berhasil Diambil', $roles, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get all available permissions.
     */
    public function permissions()
    {
        try {
            $permissions = Permission::orderBy('name')->get()->map(function ($permission) {
                // Group by module (first part of permission name)
                $parts = explode('-', $permission->name);
                $module = $parts[0] ?? 'other';
                
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $module,
                ];
            });

            // Group permissions by module
            $grouped = $permissions->groupBy('module');

            return ResponseHelper::jsonResponse(true, 'Data Permission Berhasil Diambil', $grouped, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Store a newly created role.
     */
    public function store(RoleStoreRequest $request)
    {
        try {
            $validated = $request->validated();

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'sanctum',
            ]);

            if (isset($validated['permissions']) && is_array($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            return ResponseHelper::jsonResponse(true, 'Role Berhasil Dibuat', [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ], 201);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(string $id)
    {
        try {
            $role = Role::with('permissions')->findOrFail($id);

            return ResponseHelper::jsonResponse(true, 'Data Role Berhasil Diambil', [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Role Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(RoleUpdateRequest $request, string $id)
    {
        try {
            $role = Role::findOrFail($id);
            $validated = $request->validated();

            // Protect system roles from being renamed
            $protectedRoles = ['admin', 'staff', 'user'];
            if (in_array($role->name, $protectedRoles) && isset($validated['name']) && $validated['name'] !== $role->name) {
                return ResponseHelper::jsonResponse(false, 'Nama role sistem tidak dapat diubah', null, 403);
            }

            if (isset($validated['name'])) {
                $role->name = $validated['name'];
                $role->save();
            }

            if (isset($validated['permissions']) && is_array($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            return ResponseHelper::jsonResponse(true, 'Role Berhasil Diperbarui', [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Role Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string $id)
    {
        try {
            $role = Role::findOrFail($id);

            // Protect system roles from deletion
            $protectedRoles = ['admin', 'staff', 'user'];
            if (in_array($role->name, $protectedRoles)) {
                return ResponseHelper::jsonResponse(false, 'Role sistem tidak dapat dihapus', null, 403);
            }

            // Check if role has users
            if ($role->users()->count() > 0) {
                return ResponseHelper::jsonResponse(false, 'Role tidak dapat dihapus karena masih memiliki user', null, 400);
            }

            $role->delete();

            return ResponseHelper::jsonResponse(true, 'Role Berhasil Dihapus', null, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Role Tidak Ditemukan', null, 404);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }
}
