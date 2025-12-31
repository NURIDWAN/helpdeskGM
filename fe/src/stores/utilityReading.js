import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useUtilityReadingStore = defineStore("utilityReading", {
    state: () => ({
        utilityReadings: [],
        utilityReading: null,
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
        async fetchUtilityReadings(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/utility-readings', { params });
                this.utilityReadings = response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchUtilityReadingsPaginated(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/utility-readings/all/paginated', { params });
                this.utilityReadings = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async getByDailyRecordId(dailyRecordId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get(`/daily-records/${dailyRecordId}/utility-readings`);
                this.utilityReadings = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchUtilityReading(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get(`/utility-readings/${id}`);
                this.utilityReading = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createUtilityReading(data, dailyRecordId = null) {
            this.loading = true;
            this.error = null;
            try {
                const url = dailyRecordId 
                    ? `/daily-records/${dailyRecordId}/utility-readings`
                    : '/utility-readings';
                
                // Use FormData if photo is a File object
                const formData = new FormData();
                Object.keys(data).forEach(key => {
                    if (key === 'photo' || key === 'photo_wbp' || key === 'photo_lwbp') {
                        // Only append photo if it's a File object
                        if (data[key] instanceof File) {
                            formData.append(key, data[key]);
                        }
                        // If null, don't append (optional field)
                    } else if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
                        formData.append(key, data[key]);
                    }
                });

                const response = await axiosInstance.post(url, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateUtilityReading(id, data) {
            this.loading = true;
            this.error = null;
            try {
                // Use FormData if photo is a File object
                const formData = new FormData();
                Object.keys(data).forEach(key => {
                    if (key === 'photo' || key === 'photo_wbp' || key === 'photo_lwbp') {
                        // Only append photo if it's a File object
                        if (data[key] instanceof File) {
                            formData.append(key, data[key]);
                        }
                        // If null or not a File, don't append (backend will keep existing photo)
                    } else if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
                        formData.append(key, data[key]);
                    }
                });

                const response = await axiosInstance.put(`/utility-readings/${id}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                this.success = response.data.message;
                this.utilityReading = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteUtilityReading(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.delete(`/utility-readings/${id}`);
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

