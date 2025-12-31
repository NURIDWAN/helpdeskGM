import { defineStore } from 'pinia'
import { axiosInstance } from '@/plugins/axios'
import { handleError } from '@/helpers/errorHelper'

export const useWorkOrderStore = defineStore('workOrder', {
    state: () => ({
        workOrders: [],
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
        async fetchWorkOrders(params) {
            this.loading = true
            try {
                const { data } = await axiosInstance.get('/work-orders', { params })
                this.workOrders = data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchWorkOrdersPaginated(params) {
            this.loading = true
            try {
                const { data } = await axiosInstance.get('/work-orders/all/paginated', { params })
                this.workOrders = data.data.data
                this.meta = data.data.meta
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchWorkOrder(id) {
            this.loading = true
            try {
                const { data } = await axiosInstance.get(`/work-orders/${id}`)
                return data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createWorkOrder(payload) {
            this.loading = true
            this.error = null
            try {
                const { data } = await axiosInstance.post('/work-orders', payload)
                this.success = data.message
                return data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateWorkOrder(id, payload) {
            this.loading = true
            this.error = null
            try {
                const { data } = await axiosInstance.post(`/work-orders/${id}`, { _method: 'PUT', ...payload })
                this.success = data.message
                return data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteWorkOrder(id) {
            this.loading = true
            this.error = null
            try {
                const { data } = await axiosInstance.delete(`/work-orders/${id}`)
                this.success = data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async downloadPDF(id) {
            this.loading = true
            this.error = null
            try {
                const response = await axiosInstance.get(`/work-orders/${id}/pdf`, {
                    responseType: 'blob'
                })

                // Create blob link to download
                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', `SPK_${id}.pdf`)
                document.body.appendChild(link)
                link.click()
                link.remove()
                window.URL.revokeObjectURL(url)

                this.success = 'PDF berhasil diunduh'
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },
    }
})


