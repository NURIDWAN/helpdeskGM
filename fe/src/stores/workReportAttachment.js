import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";

export const useWorkReportAttachmentStore = defineStore("workReportAttachment", {
    state: () => ({
        attachments: [],
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async getByWorkReportId(workReportId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/work-reports/${workReportId}/attachments`);
                this.attachments = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createAttachment(workReportId, formData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.post(`/work-reports/${workReportId}/attachments`, formData, {
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

        async deleteAttachment(workReportId, attachmentId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.delete(`/work-reports/${workReportId}/attachments/${attachmentId}`);
                this.success = response.data.message;
                return response.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
