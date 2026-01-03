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
use OpenApi\Annotations as OA;

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
     * @OA\Get(
     *     path="/roles",
     *     tags={"Roles"},
     *     summary="Get all roles",
     *     description="Get a list of all roles",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/SuccessResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="guard_name", type="string"),
     *                             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                             @OA\Property(property="permissions_count", type="integer")
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/roles",
     *     tags={"Roles"},
     *     summary="Create role",
     *     description="Create a new role",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
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
     * @OA\Get(
     *     path="/roles/{id}",
     *     tags={"Roles"},
     *     summary="Get role by ID",
     *     description="Get a specific role by its ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Put(
     *     path="/roles/{id}",
     *     tags={"Roles"},
     *     summary="Update role",
     *     description="Update an existing role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/roles/{id}",
     *     tags={"Roles"},
     *     summary="Delete role",
     *     description="Delete a role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
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
