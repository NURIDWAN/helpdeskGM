import { defineStore } from "pinia";
import { axiosInstance } from "@/plugins/axios";
import { handleError } from "@/helpers/errorHelper";

export const useUserActivityStore = defineStore("userActivity", {
    state: () => ({
        users: [],
        statistics: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchUsers(params = {}) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/user-activity", { params });
                this.users = response.data.data || [];
                return this.users;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchStatistics() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/user-activity/statistics");
                this.statistics = response.data.data || null;
                return this.statistics;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
