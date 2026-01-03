import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";
import { useToast } from 'vue-toastification';

export const useTicketStore = defineStore("ticket", {
    state: () => ({
        tickets: [],
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
        async fetchTickets(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/tickets', { params });
                this.tickets = response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTicketsPaginated(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/tickets/all/paginated', { params });
                this.tickets = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTicket(id) {
            this.loading = true;
            try {
                const response = await axiosInstance.get(`/tickets/${id}`);
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchTicketByCode(code) {
            this.loading = true;
            try {
                const response = await axiosInstance.get(`/tickets/code/${code}`);
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async createTicket(payload) {
            this.loading = true;
            this.error = null;
            const toast = useToast();
            try {
                const response = await axiosInstance.post('/tickets', payload);
                this.success = response.data.message;
                toast.success(response.data.message || 'Tiket berhasil dibuat');
                return response.data.data;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                // Show toast for error
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal membuat tiket');
                toast.error(errorText);
            } finally {
                this.loading = false;
            }
        },

        async updateTicket(id, payload) {
            this.loading = true;
            this.error = null;
            const toast = useToast();
            try {
                const response = await axiosInstance.post(`/tickets/${id}`, { _method: 'PUT', ...payload });
                this.success = response.data.message;
                toast.success(response.data.message || 'Tiket berhasil diupdate');
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal mengupdate tiket');
                toast.error(errorText);
            } finally {
                this.loading = false;
            }
        },

        async deleteTicket(id) {
            this.loading = true;
            try {
                const response = await axiosInstance.delete(`/tickets/${id}`);
                this.success = response.data.message;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async createTicketReply(ticketId, payload) {
            this.loading = true;
            try {
                const response = await axiosInstance.post(`/tickets/${ticketId}/replies`, payload);
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        }
    }
});
