import { defineStore } from 'pinia'
import { axiosInstance } from '@/plugins/axios'
import { handleError } from '@/helpers/errorHelper'

export const useWorkReportStore = defineStore('workReport', {
    state: () => ({
        workReports: [],
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
        async fetchWorkReports(params) {
            this.loading = true
            try {
                const { data } = await axiosInstance.get('/work-reports', { params })
                this.workReports = data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchWorkReportsPaginated(params) {
            this.loading = true
            try {
                const { data } = await axiosInstance.get('/work-reports/all/paginated', { params })
                this.workReports = data.data.data
                this.meta = data.data.meta
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchWorkReport(id) {
            this.loading = true
            try {
                const { data } = await axiosInstance.get(`/work-reports/${id}`)
                return data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createWorkReport(payload) {
            this.loading = true
            this.error = null
            try {
                const { data } = await axiosInstance.post('/work-reports', payload)
                this.success = data.message
                return data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateWorkReport(id, payload) {
            this.loading = true
            this.error = null
            try {
                const { data } = await axiosInstance.post(`/work-reports/${id}`, { _method: 'PUT', ...payload })
                this.success = data.message
                return data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteWorkReport(id) {
            this.loading = true
            this.error = null
            try {
                const { data } = await axiosInstance.delete(`/work-reports/${id}`)
                this.success = data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },
    }
})
