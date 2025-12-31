import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useTicketReplyStore = defineStore("ticketReply", {
    state: () => ({
        replies: [],
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async getByTicketId(ticketId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/tickets/${ticketId}/replies`);
                this.replies = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createReply(ticketId, data) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.post(`/tickets/${ticketId}/replies`, data);
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
