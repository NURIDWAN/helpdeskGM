import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";
import router from "@/router";

export const useJobTemplateStore = defineStore("jobTemplate", {
    state: () => ({
        jobTemplates: [],
        branches: [],
        frequencies: [
            { value: 'daily', label: 'Harian' },
            { value: 'weekly', label: 'Mingguan' },
            { value: 'monthly', label: 'Bulanan' },
            { value: 'quarterly', label: 'Triwulanan' },
            { value: 'yearly', label: 'Tahunan' },
            { value: 'on_demand', label: 'Sesuai Kebutuhan' }
        ],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        },
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async fetchJobTemplates(params) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`/job-templates`, { params })

                this.jobTemplates = response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchJobTemplatesPaginated(params) {
            this.loading = true;

            try {
                const response = await axiosInstance.get('/job-templates/all/paginated', { params });

                this.jobTemplates = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchJobTemplate(id) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`/job-templates/${id}`)

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createJobTemplate(payload) {
            this.loading = true

            try {
                const response = await axiosInstance.post("/job-templates", payload)

                this.success = response.data.message

                router.push({ name: 'admin.job-templates' })
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateJobTemplate(id, payload) {
            this.loading = true

            try {
                const response = await axiosInstance.put(`/job-templates/${id}`, payload)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteJobTemplate(id) {
            this.loading = true

            try {
                const response = await axiosInstance.delete(`/job-templates/${id}`)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async assignBranches(id, payload) {
            this.loading = true

            try {
                const response = await axiosInstance.post(`/job-templates/${id}/branches`, payload)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async removeBranch(id, branchId) {
            this.loading = true

            try {
                const response = await axiosInstance.delete(`/job-templates/${id}/branches/${branchId}`)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchBranches() {
            try {
                const response = await axiosInstance.get('/branches')
                this.branches = response.data.data
            } catch (error) {
                this.error = handleError(error)
            }
        }
    }
})
