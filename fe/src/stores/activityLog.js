import { defineStore } from "pinia";
import { axiosInstance } from "@/plugins/axios";
import { handleError } from "@/helpers/errorHelper";

export const useActivityLogStore = defineStore("activityLog", {
    state: () => ({
        logs: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
        },
        currentLog: null,
        statistics: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchLogs(params = {}) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/activity-logs", { params });
                const data = response.data.data;

                this.logs = data.data || [];
                this.meta = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total,
                };

                return this.logs;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchLog(id) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/activity-logs/${id}`);
                this.currentLog = response.data.data;
                return this.currentLog;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchStatistics() {
            this.error = null;

            try {
                const response = await axiosInstance.get("/activity-logs/statistics");
                this.statistics = response.data.data;
                return this.statistics;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            }
        },
    },
});
