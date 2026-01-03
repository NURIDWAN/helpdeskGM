import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";
import { can } from "@/helpers/permissionHelper";

export const useDashboardStore = defineStore("dashboard", {
    state: () => ({
        metrics: null,
        statusDistribution: null,
        ticketsPerBranch: [],
        topStaffResolved: [],
        fastestStaff: [],
        ticketsTrend: [],
        staffReportsTrend: [],
        unconfirmedTickets: [],
        unconfirmedWorkOrders: [],
        userRecentTickets: [],
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async fetchAllData(period = 'day') {
            this.loading = true;
            this.error = null;

            try {
                // Check if user has full dashboard access
                if (can('dashboard-view')) {
                    const response = await axiosInstance.get(`/dashboard/all?period=${period}`);

                    if (response.data.success) {
                        const data = response.data.data;
                        this.metrics = data.metrics;
                        this.statusDistribution = data.status_distribution;
                        this.ticketsPerBranch = data.tickets_per_branch;
                        this.topStaffResolved = data.top_staff_resolved;
                        this.fastestStaff = data.fastest_staff;
                        this.ticketsTrend = data.tickets_trend;
                        this.staffReportsTrend = data.staff_reports_trend;
                        this.unconfirmedTickets = data.unconfirmed_tickets || [];
                        this.unconfirmedWorkOrders = data.unconfirmed_work_orders || [];
                        this.userRecentTickets = data.user_recent_tickets || [];
                    }
                } else {
                    // Fetch data based on individual permissions
                    await this.fetchDataByPermissions(period);
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Dashboard fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchDataByPermissions(period = 'day') {
            const promises = [];

            // Fetch metrics if user has permission
            if (can('dashboard-view-metrics')) {
                promises.push(this.fetchMetrics());
            }

            // Fetch charts if user has permission
            if (can('dashboard-view-charts')) {
                promises.push(this.fetchStatusDistribution());
                promises.push(this.fetchTicketsPerBranch());
            }

            // Fetch staff rankings if user has permission
            if (can('dashboard-view-staff-rankings')) {
                promises.push(this.fetchTopStaffResolved());
                promises.push(this.fetchFastestStaff());
            }

            // Fetch trends if user has permission
            if (can('dashboard-view-trends')) {
                promises.push(this.fetchTicketsTrend(period));
                promises.push(this.fetchStaffReportsTrend(period));
            }

            await Promise.all(promises);
        },

        async fetchMetrics() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/dashboard/metrics");
                if (response.data.success) {
                    this.metrics = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Metrics fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchStatusDistribution() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/dashboard/status-distribution");
                if (response.data.success) {
                    this.statusDistribution = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Status distribution fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTicketsPerBranch() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/dashboard/tickets-per-branch");
                if (response.data.success) {
                    this.ticketsPerBranch = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Tickets per branch fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTopStaffResolved() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/dashboard/top-staff-resolved");
                if (response.data.success) {
                    this.topStaffResolved = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Top staff resolved fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchFastestStaff() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/dashboard/fastest-staff");
                if (response.data.success) {
                    this.fastestStaff = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Fastest staff fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTicketsTrend(period = 'day') {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/dashboard/tickets-trend?period=${period}`);
                if (response.data.success) {
                    this.ticketsTrend = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Tickets trend fetch error:", error);
            } finally {
                this.loading = false;
            }
        },

        async fetchStaffReportsTrend(period = 'day') {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/dashboard/staff-reports-trend?period=${period}`);
                if (response.data.success) {
                    this.staffReportsTrend = response.data.data;
                }
            } catch (error) {
                this.error = handleError(error);
                console.error("Staff reports trend fetch error:", error);
            } finally {
                this.loading = false;
            }
        },
    },
});