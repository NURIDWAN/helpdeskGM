<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class UserActivityController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['user-activity-list']), only: ['index', 'statistics']),
        ];
    }

    /**
     * Get all users with activity status
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['roles', 'branch']);

            // Filter by role
            if ($request->has('role') && $request->role) {
                $query->role($request->role);
            }

            // Filter by activity status
            if ($request->has('activity_status') && $request->activity_status) {
                $status = $request->activity_status;

                if ($status === 'active') {
                    $query->where('last_login_at', '>=', now()->subDays(7));
                } elseif ($status === 'rarely') {
                    $query->where('last_login_at', '>=', now()->subDays(30))
                        ->where('last_login_at', '<', now()->subDays(7));
                } elseif ($status === 'inactive') {
                    $query->where(function ($q) {
                        $q->where('last_login_at', '<', now()->subDays(30))
                            ->orWhereNull('last_login_at');
                    });
                } elseif ($status === 'never') {
                    $query->whereNull('last_login_at');
                }
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Order by last login
            $query->orderBy('last_login_at', 'desc');

            $users = $query->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'roles' => $user->roles->pluck('name'),
                    'branch' => $user->branch ? $user->branch->name : null,
                    'last_login_at' => $user->last_login_at,
                    'last_activity_at' => $user->last_activity_at,
                    'activity_status' => $user->activity_status,
                    'days_since_login' => $user->last_login_at
                        ? $user->last_login_at->diffInDays(now())
                        : null,
                ];
            });

            return ResponseHelper::jsonResponse(true, 'Data berhasil diambil', $users, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Get activity statistics
     */
    public function statistics()
    {
        try {
            $totalUsers = User::count();
            $activeUsers = User::where('last_login_at', '>=', now()->subDays(7))->count();
            $rarelyActiveUsers = User::where('last_login_at', '>=', now()->subDays(30))
                ->where('last_login_at', '<', now()->subDays(7))->count();
            $inactiveUsers = User::where('last_login_at', '<', now()->subDays(30))->count();
            $neverLoggedIn = User::whereNull('last_login_at')->count();

            // Per role statistics
            $roles = \Spatie\Permission\Models\Role::all();
            $roleStats = [];

            foreach ($roles as $role) {
                $roleUsers = User::role($role->name);
                $roleStats[$role->name] = [
                    'total' => $roleUsers->count(),
                    'active' => (clone $roleUsers)->where('last_login_at', '>=', now()->subDays(7))->count(),
                    'rarely' => (clone $roleUsers)->where('last_login_at', '>=', now()->subDays(30))
                        ->where('last_login_at', '<', now()->subDays(7))->count(),
                    'inactive' => (clone $roleUsers)->where('last_login_at', '<', now()->subDays(30))->count(),
                    'never' => (clone $roleUsers)->whereNull('last_login_at')->count(),
                ];
            }

            return ResponseHelper::jsonResponse(true, 'Statistik berhasil diambil', [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'rarely' => $rarelyActiveUsers,
                'inactive' => $inactiveUsers,
                'never' => $neverLoggedIn,
                'by_role' => $roleStats,
            ], 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
