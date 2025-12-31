import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

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
            try {
                const response = await axiosInstance.post('/tickets', payload);
                this.success = response.data.message;

                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async updateTicket(id, payload) {
            try {
                const response = await axiosInstance.post(`/tickets/${id}`, { _method: 'PUT', ...payload });
                this.success = response.data.message;
            } catch (error) {
                this.error = handleError(error);
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

        async assignTicket(id, staffId) {
            this.loading = true;
            try {
                const response = await axiosInstance.put(`/tickets/${id}/assign`, { assigned_to: staffId });
                this.success = response.data.message;
                return response.data.data;
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
