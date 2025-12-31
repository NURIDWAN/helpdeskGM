import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useElectricityMeterStore = defineStore("electricityMeter", {
    state: () => ({
        electricityMeters: [],
        electricityMeter: null,
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
        async fetchElectricityMeters(params) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get('/electricity-meters', { params });
                this.electricityMeters = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchElectricityMetersPaginated(params) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get('/electricity-meters/all/paginated', { params });
                this.electricityMeters = response.data.data.data;
                this.meta = response.data.data.meta;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async getByBranchId(branchId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get(`/branches/${branchId}/electricity-meters`);
                this.electricityMeters = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchElectricityMeter(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get(`/electricity-meters/${id}`);
                this.electricityMeter = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createElectricityMeter(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.post('/electricity-meters', data);
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateElectricityMeter(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.put(`/electricity-meters/${id}`, data);
                this.success = response.data.message;
                this.electricityMeter = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteElectricityMeter(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.delete(`/electricity-meters/${id}`);
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
