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

    public function __construct()
    {
        $this->currentUser = auth()->user();
    }

    public function getMetrics(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Base query for tickets - filter by user role
        $ticketQuery = DB::table('tickets');
        $workOrderQuery = DB::table('work_orders');

        // For staff users, filter by their assigned tickets and work orders
        if ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $ticketQuery->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });

            $workOrderQuery->where('assigned_to', $this->currentUser->id);
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
        $avgResolutionTime = (clone $ticketQuery)
            ->where('status', TicketStatus::RESOLVED->value)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
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

        // For staff users, filter by their assigned tickets
        if ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
        }

        $statusData = $query
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'open' => $statusData[TicketStatus::OPEN->value] ?? 0,
            'in_progress' => $statusData[TicketStatus::IN_PROGRESS->value] ?? 0,
            'resolved' => $statusData[TicketStatus::RESOLVED->value] ?? 0,
            'closed' => $statusData[TicketStatus::CLOSED->value] ?? 0,
        ];
    }

    public function getTicketsPerBranch(): array
    {
        $query = DB::table('tickets')
            ->join('branches', 'tickets.branch_id', '=', 'branches.id');

        // For staff users, filter by their assigned tickets
        if ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
        }

        return $query
            ->select('branches.name', DB::raw('count(tickets.id) as count'))
            ->groupBy('branches.id', 'branches.name')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'branch' => $item->name,
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
        return DB::table('tickets')
            ->join('ticket_staff', 'tickets.id', '=', 'ticket_staff.ticket_id')
            ->join('users', 'ticket_staff.user_id', '=', 'users.id')
            ->where('tickets.status', TicketStatus::RESOLVED->value)
            ->whereNotNull('tickets.completed_at')
            ->select(
                'users.name',
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, tickets.created_at, tickets.completed_at)) as avg_resolution_hours'),
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

        // For staff users, filter by their assigned tickets
        if ($this->currentUser && $this->currentUser->hasRole('staff')) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('ticket_staff')
                    ->whereColumn('ticket_staff.ticket_id', 'tickets.id')
                    ->where('ticket_staff.user_id', $this->currentUser->id);
            });
        }

        if ($period === 'week') {
            $data = $query->select(
                DB::raw('YEARWEEK(created_at) as period'),
                DB::raw('count(*) as count')
            )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => Carbon::createFromFormat('Y-m-d', $item->period . '-1')->format('M d'),
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
            $data = $query->select(
                DB::raw('YEARWEEK(created_at) as period'),
                DB::raw('count(*) as count')
            )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => Carbon::createFromFormat('Y-m-d', $item->period . '-1')->format('M d'),
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
}
