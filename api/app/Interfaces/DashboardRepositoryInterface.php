<?php

namespace App\Interfaces;

interface DashboardRepositoryInterface
{
    /**
     * Get dashboard metrics
     */
    public function getMetrics(): array;

    /**
     * Get status distribution data
     */
    public function getStatusDistribution(): array;

    /**
     * Get tickets per branch data
     */
    public function getTicketsPerBranch(): array;

    /**
     * Get top 5 staff with most resolved tickets
     */
    public function getTopStaffResolved(): array;

    /**
     * Get staff with fastest average resolution time
     */
    public function getFastestStaff(): array;

    /**
     * Get tickets trend data (per day/week)
     */
    public function getTicketsTrend(string $period = 'day'): array;

    /**
     * Get staff reports trend data
     */
    public function getStaffReportsTrend(string $period = 'day'): array;

    public function getUnconfirmedTickets(): array;

    public function getUnconfirmedWorkOrders(): array;

    public function getUserRecentTickets(): array;

    /**
     * Get top 5 outlet usage for electricity, water, gas, and customer count
     * for the current month.
     *
     * @return array{
     *     electricity: array<int, array{branch_name: string, value: float}>,
     *     water: array<int, array{branch_name: string, value: float}>,
     *     gas: array<int, array{branch_name: string, value: float}>,
     *     customer: array<int, array{branch_name: string, value: int}>
     * }
     */
    public function getTopOutletUsage(): array;
}
