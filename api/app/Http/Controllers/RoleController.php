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
    /**
     * System roles that cannot be deleted or renamed.
     */
    const SYSTEM_ROLES = ['admin', 'staff', 'user', 'superadmin'];

    /**
     * Immutable roles whose permissions cannot be modified.
     */
    const IMMUTABLE_ROLES = ['superadmin'];

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['role-list']), only: ['index', 'show', 'permissions', 'matrix', 'presets']),
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
            $query = Role::with('permissions')->withCount('users');

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
                    'users_count' => $role->users_count,
                    'is_system' => in_array($role->name, self::SYSTEM_ROLES),
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
                // Group by module - extract all parts except the last one (action)
                // e.g., "ticket-category-list" -> module = "ticket-category", action = "list"
                // e.g., "ticket-list" -> module = "ticket", action = "list"
                $parts = explode('-', $permission->name);

                // The last part is the action (list, create, edit, delete, menu, etc.)
                $action = array_pop($parts);

                // Everything else is the module name
                $module = count($parts) > 0 ? implode('-', $parts) : 'other';

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $module,
                    'action' => $action,
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
     * Get permission matrix data (all roles with permissions grouped by feature).
     */
    public function matrix()
    {
        try {
            $roles = Role::with('permissions')->get();
            $featureGroups = config('permission_features');

            $rolesData = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'is_system' => in_array($role->name, self::SYSTEM_ROLES),
                ];
            })->values();

            $matrix = [];
            foreach ($roles as $role) {
                $rolePermissions = $role->permissions->pluck('name')->toArray();
                $matrix[$role->name] = [];

                foreach ($featureGroups as $featureKey => $feature) {
                    $featurePermissions = $feature['permissions'];
                    $total = count($featurePermissions);
                    $selected = count(array_intersect($rolePermissions, $featurePermissions));

                    if ($selected === $total) {
                        $status = 'full';
                    } elseif ($selected > 0) {
                        $status = 'partial';
                    } else {
                        $status = 'empty';
                    }

                    $matrix[$role->name][$featureKey] = [
                        'selected' => $selected,
                        'total' => $total,
                        'status' => $status,
                    ];
                }
            }

            $features = [];
            foreach ($featureGroups as $featureKey => $feature) {
                $features[] = [
                    'key' => $featureKey,
                    'label' => $feature['label'],
                    'total' => count($feature['permissions']),
                ];
            }

            return ResponseHelper::jsonResponse(true, 'Data matrix berhasil diambil', [
                'roles' => $rolesData,
                'matrix' => $matrix,
                'features' => $features,
            ], 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get available role preset configurations.
     */
    public function presets()
    {
        try {
            $presets = config('role_presets');

            return ResponseHelper::jsonResponse(true, 'Data preset berhasil diambil', [
                'presets' => $presets,
            ], 200);
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
                $permissions = array_values(array_unique($validated['permissions']));
                $role->syncPermissions($permissions);
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
            $role = Role::with('permissions')->withCount('users')->findOrFail($id);

            return ResponseHelper::jsonResponse(true, 'Data Role Berhasil Diambil', [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'users_count' => $role->users_count,
                'is_system' => in_array($role->name, self::SYSTEM_ROLES),
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
            if (in_array($role->name, self::SYSTEM_ROLES) && isset($validated['name']) && $validated['name'] !== $role->name) {
                return ResponseHelper::jsonResponse(false, 'Nama role sistem tidak dapat diubah', null, 403);
            }

            // Protect immutable roles (superadmin) from permission changes
            if (in_array($role->name, self::IMMUTABLE_ROLES) && array_key_exists('permissions', $validated)) {
                return ResponseHelper::jsonResponse(false, 'Permission role superadmin tidak dapat diubah', null, 403);
            }

            if (isset($validated['name'])) {
                $role->name = $validated['name'];
                $role->save();
            }

            // Only sync permissions if the key was explicitly sent and is not null
            if (array_key_exists('permissions', $validated) && is_array($validated['permissions'])) {
                $permissions = array_values(array_unique($validated['permissions']));
                $role->syncPermissions($permissions);
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
            if (in_array($role->name, self::SYSTEM_ROLES)) {
                return ResponseHelper::jsonResponse(false, 'Role sistem tidak dapat dihapus', null, 403);
            }

            // Check if role has users
            $userCount = $role->users()->count();
            if ($userCount > 0) {
                return ResponseHelper::jsonResponse(false, "Role tidak dapat dihapus karena masih digunakan oleh {$userCount} user", null, 422);
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
