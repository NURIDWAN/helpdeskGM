<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['activity-log-list']), only: ['index', 'show', 'statistics']),
        ];
    }

    /**
     * List activity logs dengan pagination dan filter.
     */
    public function index(Request $request)
    {
        try {
            $query = ActivityLog::with('user:id,name,email');

            // Filter by user
            if ($request->filled('user_id')) {
                $query->byUser($request->user_id);
            }

            // Filter by action
            if ($request->filled('action')) {
                $query->byAction($request->action);
            }

            // Filter by module
            if ($request->filled('module')) {
                $query->byModule($request->module);
            }

            // Filter by date range
            if ($request->filled('date_from') || $request->filled('date_to')) {
                $query->byDateRange($request->date_from, $request->date_to);
            }

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $query->orderBy('created_at', 'desc');

            $perPage = $request->input('per_page', 20);
            $logs = $query->paginate($perPage);

            return ResponseHelper::jsonResponse(true, 'Activity logs berhasil diambil', $logs, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Detail log individual.
     */
    public function show($id)
    {
        try {
            $log = ActivityLog::with('user:id,name,email')->findOrFail($id);

            return ResponseHelper::jsonResponse(true, 'Detail log berhasil diambil', $log, 200);
        } catch (\Throwable $e) {
            $status = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500;
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, $status);
        }
    }

    /**
     * Statistik ringkasan activity log.
     */
    public function statistics(Request $request)
    {
        try {
            $today = now()->startOfDay();

            // Total log hari ini
            $todayTotal = ActivityLog::where('created_at', '>=', $today)->count();

            // Breakdown per action hari ini
            $todayByAction = ActivityLog::where('created_at', '>=', $today)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray();

            // Breakdown per module (all time, last 30 days)
            $thirtyDaysAgo = now()->subDays(30);
            $byModule = ActivityLog::where('created_at', '>=', $thirtyDaysAgo)
                ->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->orderByDesc('count')
                ->limit(15)
                ->pluck('count', 'module')
                ->toArray();

            // Login per hari (last 7 days)
            $sevenDaysAgo = now()->subDays(7);
            $loginTrend = ActivityLog::where('action', 'login')
                ->where('created_at', '>=', $sevenDaysAgo)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            // Top 5 user paling aktif (last 30 days)
            $topUsers = ActivityLog::where('created_at', '>=', $thirtyDaysAgo)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $user = User::select('id', 'name', 'email')->find($item->user_id);
                    return [
                        'user' => $user,
                        'count' => $item->count,
                    ];
                });

            // Total keseluruhan
            $totalLogs = ActivityLog::count();

            return ResponseHelper::jsonResponse(true, 'Statistik berhasil diambil', [
                'total_logs' => $totalLogs,
                'today_total' => $todayTotal,
                'today_by_action' => $todayByAction,
                'by_module' => $byModule,
                'login_trend' => $loginTrend,
                'top_users' => $topUsers,
            ], 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
