<?php

namespace App\Repositories;

use App\Interfaces\DashboardRepositoryInterface;
use App\Enums\TicketStatus;
use App\Enums\WorkOrderStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardRepository implements DashboardRepositoryInterface
{
    protected $currentUser;
    protected $isSqlite;

    public function __construct()
    {
        $this->currentUser = auth()->user();
        $this->isSqlite = DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Get time difference expression compatible with both SQLite and MySQL
     */
    private function getTimeDiffHoursExpression(string $startCol, string $endCol): string
    {
        if ($this->isSqlite) {
            // SQLite: use julianday for time difference in days, then convert to hours
            return "(julianday({$endCol}) - julianday({$startCol})) * 24";
        }
        // MySQL
        return "TIMESTAMPDIFF(HOUR, {$startCol}, {$endCol})";
    }

    /**
     * Get YEARWEEK expression compatible with both SQLite and MySQL
     */
    private function getYearWeekExpression(string $dateCol): string
    {
        if ($this->isSqlite) {
            // SQLite: strftime('%Y%W', date) gives year + week number
            return "strftime('%Y%W', {$dateCol})";
        }
        // MySQL
        return "YEARWEEK({$dateCol}, 1)";
    }

    public function getMetrics(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Base query for tickets - filter by user role
        $ticketQuery = DB::table('tickets');
        $workOrderQuery = DB::table('work_orders');

        // Role-based filtering
        if ($this->currentUser && $this->currentUser->can('dashboard-view-all')) {
            // admins and superadmins can see all data
        } elseif ($this->currentUser && ($this->currentUser->hasRole('staff') || !$this->currentUser->can('dashboard-view-all'))) {
            // Staff: Assigned tickets
            $ticketQuery->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
            // Staff: Assigned work orders
            $workOrderQuery->where('assigned_to', $this->currentUser->id);
        } else {
            // Regular User: Own tickets only
            if ($this->currentUser) {
                $ticketQuery->where('user_id', $this->currentUser->id);
                // Regular users typically don't see work orders in dashboard metrics, keep WO count 0 for now
                $workOrderQuery->whereRaw('1=0');
            }
        }

        // Total tickets today
        $totalTicketsToday = (clone $ticketQuery)
            ->whereDate('created_at', $today)
            ->count();

        // Total tickets this month
        $totalTicketsThisMonth = (clone $ticketQuery)
            ->where('created_at', '>=', $thisMonth)
            ->count();

        // Open tickets
        $openTickets = (clone $ticketQuery)
            ->whereIn('status', [TicketStatus::OPEN->value, TicketStatus::IN_PROGRESS->value])
            ->count();

        // Average resolution time (in hours) - only for staff's resolved tickets
        $timeDiffExpr = $this->getTimeDiffHoursExpression('created_at', 'completed_at');
        $avgResolutionTime = (clone $ticketQuery)
            ->where('status', TicketStatus::RESOLVED->value)
            ->whereNotNull('completed_at')
            ->selectRaw("AVG({$timeDiffExpr}) as avg_hours")
            ->value('avg_hours') ?? 0;

        // Active work orders
        $activeWorkOrders = $workOrderQuery
            ->whereIn('status', [WorkOrderStatus::PENDING->value, WorkOrderStatus::IN_PROGRESS->value])
            ->count();

        // Comparison with last month
        $lastMonthTickets = (clone $ticketQuery)
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $thisMonth)
            ->count();

        $ticketGrowth = $lastMonthTickets > 0
            ? round((($totalTicketsThisMonth - $lastMonthTickets) / $lastMonthTickets) * 100, 1)
            : 0;

        return [
            'total_tickets_today' => $totalTicketsToday,
            'total_tickets_this_month' => $totalTicketsThisMonth,
            'open_tickets' => $openTickets,
            'avg_resolution_time' => round($avgResolutionTime, 1),
            'active_work_orders' => $activeWorkOrders,
            'ticket_growth_percentage' => $ticketGrowth,
        ];
    }

    public function getStatusDistribution(): array
    {
        $query = DB::table('tickets');

        // Role-based filtering
        if ($this->currentUser && $this->currentUser->can('dashboard-view-all')) {
            // No filter by permission
        } elseif ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
        } else {
            // Regular user
            if ($this->currentUser) {
                $query->where('user_id', $this->currentUser->id);
            }
        }

        $rawStatusData = $query
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Normalize keys to ensure matching with frontend expectations
        $statusData = [];
        foreach ($rawStatusData as $key => $value) {
            $statusData[strtolower($key)] = $value;
        }

        return [
            'open' => $statusData['open'] ?? 0,
            'in_progress' => $statusData['in_progress'] ?? 0,
            'resolved' => $statusData['resolved'] ?? 0,
            'closed' => $statusData['closed'] ?? 0,
        ];
    }

    public function getTicketsPerBranch(): array
    {
        $query = DB::table('tickets')
            ->leftJoin('branches', 'tickets.branch_id', '=', 'branches.id');

        // Role-based filtering
        if ($this->currentUser && $this->currentUser->can('dashboard-view-all')) {
            // No filter by permission
        } elseif ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
        } else {
            // Regular user
            if ($this->currentUser) {
                $query->where('tickets.user_id', $this->currentUser->id);
            }
        }

        return $query
            ->select(DB::raw('COALESCE(branches.name, "Tanpa Cabang") as branch_name'), DB::raw('count(tickets.id) as count'))
            ->groupBy('branch_name', 'branches.id') // Group by the coalesced name or ID
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'branch' => $item->branch_name,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    public function getTopStaffResolved(): array
    {
        return DB::table('tickets')
            ->join('ticket_staff', 'tickets.id', '=', 'ticket_staff.ticket_id')
            ->join('users', 'ticket_staff.user_id', '=', 'users.id')
            ->where('tickets.status', TicketStatus::RESOLVED->value)
            ->select('users.name', DB::raw('count(tickets.id) as resolved_count'))
            ->groupBy('users.id', 'users.name')
            ->orderBy('resolved_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->name,
                    'resolved_count' => $item->resolved_count,
                ];
            })
            ->toArray();
    }

    public function getFastestStaff(): array
    {
        $timeDiffExpr = $this->getTimeDiffHoursExpression('tickets.created_at', 'tickets.completed_at');
        
        return DB::table('tickets')
            ->join('ticket_staff', 'tickets.id', '=', 'ticket_staff.ticket_id')
            ->join('users', 'ticket_staff.user_id', '=', 'users.id')
            ->where('tickets.status', TicketStatus::RESOLVED->value)
            ->whereNotNull('tickets.completed_at')
            ->select(
                'users.name',
                DB::raw("AVG({$timeDiffExpr}) as avg_resolution_hours"),
                DB::raw('count(tickets.id) as total_resolved')
            )
            ->groupBy('users.id', 'users.name')
            ->having('total_resolved', '>=', 3) // Minimum 3 resolved tickets
            ->orderBy('avg_resolution_hours', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'staff_name' => $item->name,
                    'avg_resolution_hours' => round($item->avg_resolution_hours, 1),
                    'total_resolved' => $item->total_resolved,
                ];
            })
            ->toArray();
    }

    public function getTicketsTrend(string $period = 'day'): array
    {
        $startDate = $period === 'week'
            ? Carbon::now()->subWeeks(4)->startOfWeek()
            : Carbon::now()->subDays(30)->startOfDay();

        $query = DB::table('tickets')
            ->where('created_at', '>=', $startDate);

        // Role-based filtering
        if ($this->currentUser && $this->currentUser->can('dashboard-view-all')) {
            // No filter by permission
        } elseif ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
        } else {
            // Regular User
            if ($this->currentUser) {
                $query->where('user_id', $this->currentUser->id);
            }
        }

        if ($period === 'week') {
            $yearWeekExpr = $this->getYearWeekExpression('created_at');
            $data = $query->select(
                DB::raw("{$yearWeekExpr} as period"),
                DB::raw('count(*) as count')
            )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    // YEARWEEK returns format like 202601 (year + week number)
                    $yearWeek = (string) $item->period;
                    $year = (int) substr($yearWeek, 0, 4);
                    $week = (int) substr($yearWeek, 4);
                    return [
                        'period' => Carbon::now()->setISODate($year, $week)->startOfWeek()->format('M d'),
                        'count' => $item->count,
                    ];
                });
        } else {
            $data = $query->select(
                DB::raw('DATE(created_at) as period'),
                DB::raw('count(*) as count')
            )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => Carbon::parse($item->period)->format('M d'),
                        'count' => $item->count,
                    ];
                });
        }

        return $data->toArray();
    }

    public function getStaffReportsTrend(string $period = 'day'): array
    {
        $startDate = $period === 'week'
            ? Carbon::now()->subWeeks(4)->startOfWeek()
            : Carbon::now()->subDays(30)->startOfDay();

        $query = DB::table('work_reports')
            ->where('created_at', '>=', $startDate);

        // For staff users, filter by their own reports
        if ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->where('user_id', $this->currentUser->id);
        }

        if ($period === 'week') {
            $yearWeekExpr = $this->getYearWeekExpression('created_at');
            $data = $query->select(
                DB::raw("{$yearWeekExpr} as period"),
                DB::raw('count(*) as count')
            )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    // YEARWEEK returns format like 202601 (year + week number)
                    $yearWeek = (string) $item->period;
                    $year = (int) substr($yearWeek, 0, 4);
                    $week = (int) substr($yearWeek, 4);
                    return [
                        'period' => Carbon::now()->setISODate($year, $week)->startOfWeek()->format('M d'),
                        'count' => $item->count,
                    ];
                });
        } else {
            $data = $query->select(
                DB::raw('DATE(created_at) as period'),
                DB::raw('count(*) as count')
            )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => Carbon::parse($item->period)->format('M d'),
                        'count' => $item->count,
                    ];
                });
        }

        return $data->toArray();
    }

    public function getUnconfirmedTickets(): array
    {
        if (!$this->currentUser || !$this->currentUser->hasRole('staff')) {
            return [];
        }

        return DB::table('tickets')
            ->join('ticket_staff', 'tickets.id', '=', 'ticket_staff.ticket_id')
            ->where('ticket_staff.user_id', $this->currentUser->id)
            ->where('tickets.status', TicketStatus::OPEN->value)
            ->select('tickets.*')
            ->orderBy('tickets.created_at', 'asc') // Oldest first to prioritize
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getUnconfirmedWorkOrders(): array
    {
        if (!$this->currentUser || !$this->currentUser->hasRole('staff')) {
            return [];
        }

        return DB::table('work_orders')
            ->where('assigned_to', $this->currentUser->id)
            ->where('status', WorkOrderStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getUserRecentTickets(): array
    {
        if (!$this->currentUser) {
            return [];
        }

        return DB::table('tickets')
            ->where('user_id', $this->currentUser->id)
            ->select('tickets.*')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function getTopOutletUsage(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $today = Carbon::today()->toDateString();

        // Top 5 by electricity (from electricity_readings table)
        $electricity = DB::table('electricity_readings')
            ->join('daily_records', 'electricity_readings.daily_record_id', '=', 'daily_records.id')
            ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
            ->whereBetween('daily_records.date', [$startOfMonth, $today])
            ->select('branches.name as branch_name', DB::raw('SUM(electricity_readings.meter_value) as value'))
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn($item) => ['branch_name' => $item->branch_name, 'value' => (float) $item->value])
            ->toArray();

        // Top 5 by water (from utility_readings with category='water')
        $water = DB::table('utility_readings')
            ->join('daily_records', 'utility_readings.daily_record_id', '=', 'daily_records.id')
            ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
            ->where('utility_readings.category', 'water')
            ->whereBetween('daily_records.date', [$startOfMonth, $today])
            ->select('branches.name as branch_name', DB::raw('SUM(utility_readings.meter_value) as value'))
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn($item) => ['branch_name' => $item->branch_name, 'value' => (float) $item->value])
            ->toArray();

        // Top 5 by gas (from utility_readings with category='gas')
        $gas = DB::table('utility_readings')
            ->join('daily_records', 'utility_readings.daily_record_id', '=', 'daily_records.id')
            ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
            ->where('utility_readings.category', 'gas')
            ->whereBetween('daily_records.date', [$startOfMonth, $today])
            ->select('branches.name as branch_name', DB::raw('SUM(utility_readings.meter_value) as value'))
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn($item) => ['branch_name' => $item->branch_name, 'value' => (float) $item->value])
            ->toArray();

        // Top 5 by customer count (from daily_records)
        $customer = DB::table('daily_records')
            ->join('branches', 'daily_records.branch_id', '=', 'branches.id')
            ->whereBetween('daily_records.date', [$startOfMonth, $today])
            ->select('branches.name as branch_name', DB::raw('SUM(daily_records.total_customers) as value'))
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn($item) => ['branch_name' => $item->branch_name, 'value' => (int) $item->value])
            ->toArray();

        return [
            'electricity' => $electricity,
            'water' => $water,
            'gas' => $gas,
            'customer' => $customer,
        ];
    }
}
