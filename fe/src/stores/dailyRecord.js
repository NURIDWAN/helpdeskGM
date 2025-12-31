import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useDailyRecordStore = defineStore("dailyRecord", {
    state: () => ({
        dailyRecords: [],
        dailyRecord: null,
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async fetchDailyRecords(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/daily-records', { params });
                this.dailyRecords = response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchDailyRecordsPaginated(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/daily-records/all/paginated', { params });
                this.dailyRecords = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchDailyRecord(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get(`/daily-records/${id}`);
                this.dailyRecord = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createDailyRecord(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.post('/daily-records', data);
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateDailyRecord(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.put(`/daily-records/${id}`, data);
                this.success = response.data.message;
                this.dailyRecord = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteDailyRecord(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.delete(`/daily-records/${id}`);
                this.success = response.data.message;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
    }
});

