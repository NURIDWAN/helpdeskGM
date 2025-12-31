import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useElectricityReadingStore = defineStore("electricityReading", {
    state: () => ({
        electricityReadings: [],
        electricityReading: null,
        multiMeterReport: null,
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
        async fetchElectricityReadings(params) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get('/electricity-readings', { params });
                this.electricityReadings = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchElectricityReadingsPaginated(params) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get('/electricity-readings/all/paginated', { params });
                this.electricityReadings = response.data.data.data;
                this.meta = response.data.data.meta;
                return response.data.data;
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
                const response = await axiosInstance.get(`/daily-records/${dailyRecordId}/electricity-readings`);
                this.electricityReadings = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchElectricityReading(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get(`/electricity-readings/${id}`);
                this.electricityReading = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createElectricityReading(data, dailyRecordId = null) {
            this.loading = true;
            this.error = null;
            try {
                const url = dailyRecordId
                    ? `/daily-records/${dailyRecordId}/electricity-readings`
                    : '/electricity-readings';

                // Use FormData if photos are File objects
                const formData = new FormData();
                Object.keys(data).forEach(key => {
                    if (key === 'photo_wbp' || key === 'photo_lwbp') {
                        // Only append photo if it's a File object
                        if (data[key] instanceof File) {
                            formData.append(key, data[key]);
                        }
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

        async createMultipleReadings(dailyRecordId, readings) {
            this.loading = true;
            this.error = null;
            try {
                const formData = new FormData();

                readings.forEach((reading, index) => {
                    Object.keys(reading).forEach(key => {
                        if (reading[key] !== null && reading[key] !== undefined) {
                            if ((key === 'photo_wbp' || key === 'photo_lwbp')) {
                                if (reading[key] instanceof File) {
                                    formData.append(`readings[${index}][${key}]`, reading[key]);
                                }
                            } else {
                                formData.append(`readings[${index}][${key}]`, reading[key]);
                            }
                        }
                    });
                });

                const response = await axiosInstance.post(
                    `/daily-records/${dailyRecordId}/electricity-readings/multiple`,
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    }
                );
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateElectricityReading(id, data) {
            this.loading = true;
            this.error = null;
            try {
                // Use FormData if photos are File objects
                const formData = new FormData();
                Object.keys(data).forEach(key => {
                    if (key === 'photo_wbp' || key === 'photo_lwbp') {
                        // Only append photo if it's a File object
                        if (data[key] instanceof File) {
                            formData.append(key, data[key]);
                        }
                    } else if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
                        formData.append(key, data[key]);
                    }
                });

                const response = await axiosInstance.put(`/electricity-readings/${id}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                this.success = response.data.message;
                this.electricityReading = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteElectricityReading(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.delete(`/electricity-readings/${id}`);
                this.success = response.data.message;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async getMultiMeterReport(params) {
            this.loading = true;
            this.error = null;
            try {
                const response = await axiosInstance.get('/electricity-readings/report/multi-meter', { params });
                this.multiMeterReport = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
    }
});
