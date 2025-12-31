import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useTicketAttachmentStore = defineStore("ticketAttachment", {
    state: () => ({
        attachments: [],
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async getByTicketId(ticketId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/tickets/${ticketId}/attachments`);
                this.attachments = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createAttachment(ticketId, formData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.post(`/tickets/${ticketId}/attachments`, formData, {
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
        }
    }
});
