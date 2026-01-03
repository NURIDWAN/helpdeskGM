<?php

namespace App\Http\Controllers;

use App\Interfaces\DashboardRepositoryInterface;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class DashboardController extends Controller implements HasMiddleware
{
    protected $dashboardRepository;

    public function __construct(DashboardRepositoryInterface $dashboardRepository)
    {
        $this->dashboardRepository = $dashboardRepository;
    }

    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['dashboard-view']), only: ['getAllData']),
            new Middleware(PermissionMiddleware::using(['dashboard-view-metrics']), only: ['getMetrics']),
            new Middleware(PermissionMiddleware::using(['dashboard-view-charts']), only: ['getStatusDistribution', 'getTicketsPerBranch']),
            new Middleware(PermissionMiddleware::using(['dashboard-view-staff-rankings']), only: ['getTopStaffResolved', 'getFastestStaff']),
            new Middleware(PermissionMiddleware::using(['dashboard-view-trends']), only: ['getTicketsTrend', 'getStaffReportsTrend']),
        ];
    }

    /**
     * Get dashboard metrics
     */
    public function getMetrics()
    {
        try {
            $metrics = $this->dashboardRepository->getMetrics();
            return ResponseHelper::jsonResponse(true, 'Dashboard metrics berhasil diambil', $metrics, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get status distribution data
     */
    public function getStatusDistribution()
    {
        try {
            $data = $this->dashboardRepository->getStatusDistribution();
            return ResponseHelper::jsonResponse(true, 'Status distribution berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get tickets per branch data
     */
    public function getTicketsPerBranch()
    {
        try {
            $data = $this->dashboardRepository->getTicketsPerBranch();
            return ResponseHelper::jsonResponse(true, 'Tickets per branch berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get top 5 staff with most resolved tickets
     */
    public function getTopStaffResolved()
    {
        try {
            $data = $this->dashboardRepository->getTopStaffResolved();
            return ResponseHelper::jsonResponse(true, 'Top staff resolved berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get staff with fastest average resolution time
     */
    public function getFastestStaff()
    {
        try {
            $data = $this->dashboardRepository->getFastestStaff();
            return ResponseHelper::jsonResponse(true, 'Fastest staff berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get tickets trend data
     */
    public function getTicketsTrend(Request $request)
    {
        try {
            $period = $request->get('period', 'day'); // day or week
            $data = $this->dashboardRepository->getTicketsTrend($period);
            return ResponseHelper::jsonResponse(true, 'Tickets trend berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get staff reports trend data
     */
    public function getStaffReportsTrend(Request $request)
    {
        try {
            $period = $request->get('period', 'day'); // day or week
            $data = $this->dashboardRepository->getStaffReportsTrend($period);
            return ResponseHelper::jsonResponse(true, 'Staff reports trend berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Get all dashboard data
     */
    public function getAllData(Request $request)
    {
        try {
            $period = $request->get('period', 'day');

            $data = [
                'metrics' => $this->dashboardRepository->getMetrics(),
                'status_distribution' => $this->dashboardRepository->getStatusDistribution(),
                'tickets_per_branch' => $this->dashboardRepository->getTicketsPerBranch(),
                'top_staff_resolved' => $this->dashboardRepository->getTopStaffResolved(),
                'fastest_staff' => $this->dashboardRepository->getFastestStaff(),
                'tickets_trend' => $this->dashboardRepository->getTicketsTrend($period),
                'staff_reports_trend' => $this->dashboardRepository->getStaffReportsTrend($period),
                'unconfirmed_tickets' => $this->dashboardRepository->getUnconfirmedTickets(),
                'unconfirmed_work_orders' => $this->dashboardRepository->getUnconfirmedWorkOrders(),
                'user_recent_tickets' => $this->dashboardRepository->getUserRecentTickets(),
            ];

            return ResponseHelper::jsonResponse(true, 'Dashboard data berhasil diambil', $data, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
