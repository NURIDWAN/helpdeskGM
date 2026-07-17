import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";
import { useToastStore } from '@/stores/toast';

export const useFormPermintaanStore = defineStore("formPermintaan", {
    state: () => ({
        forms: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
        form: null,
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async createFormPermintaan(payload) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const response = await axiosInstance.post('/form-permintaan', payload);
                this.success = response.data.message;
                toast.success(response.data.message || 'Form permintaan berhasil dibuat');
                return response.data.data;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal membuat form permintaan');
                toast.error(errorText);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateFormPermintaan(id, payload) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const response = await axiosInstance.put(`/form-permintaan/${id}`, payload);
                this.success = response.data.message;
                toast.success(response.data.message || 'Form permintaan berhasil diubah');
                return response.data.data;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal mengubah form permintaan');
                toast.error(errorText);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteFormPermintaan(id) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const response = await axiosInstance.delete(`/form-permintaan/${id}`);
                this.success = response.data.message;
                toast.success(response.data.message || 'Form permintaan berhasil dihapus');
                return true;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal menghapus form permintaan');
                toast.error(errorText);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async confirmFormPermintaan(id) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const response = await axiosInstance.put(`/form-permintaan/${id}/confirm`);
                this.success = response.data.message;
                toast.success(response.data.message || 'Form permintaan berhasil dikonfirmasi');
                return response.data.data;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal mengonfirmasi form permintaan');
                toast.error(errorText);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateFormPermintaanStatus(id, status, reason = null) {
            this.error = null;
            const toast = useToastStore();
            try {
                const payload = { status };
                if (reason) payload.reason = reason;
                const response = await axiosInstance.put(`/form-permintaan/${id}/status`, payload);
                this.success = response.data.message;
                toast.success(response.data.message || 'Status berhasil diubah');
                return response.data.data;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal mengubah status');
                toast.error(errorText);
                throw error;
            }
        },

        async fetchFormPermintaanPaginated(params) {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/form-permintaan', { params });
                this.forms = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchFormPermintaan(id) {
            this.loading = true;
            try {
                const response = await axiosInstance.get(`/form-permintaan/${id}`);
                this.form = response.data.data;
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async uploadAttachment(formId, file, itemId = null) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const formData = new FormData();
                formData.append('file', file);
                if (itemId) {
                    formData.append('form_permintaan_item_id', itemId);
                }
                const response = await axiosInstance.post(`/form-permintaan/${formId}/attachments`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                this.success = response.data.message;
                return response.data.data;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal mengupload attachment');
                toast.error(errorText);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteAttachment(formId, attachmentId) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const response = await axiosInstance.delete(`/form-permintaan/${formId}/attachments/${attachmentId}`);
                this.success = response.data.message;
                toast.success(response.data.message || 'Attachment berhasil dihapus');
                return true;
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal menghapus attachment');
                toast.error(errorText);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async downloadAttachment(formId, attachment) {
            const response = await axiosInstance.get(`/form-permintaan/${formId}/attachments/${attachment.id}/download`, {
                responseType: 'blob',
            });
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.download = attachment.file_name || 'lampiran';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        },

        async downloadPDF(id, requestNumber) {
            this.loading = true;
            this.error = null;
            const toast = useToastStore();
            try {
                const response = await axiosInstance.get(`/form-permintaan/${id}/pdf`, {
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `Form_Permintaan_${requestNumber || id}.pdf`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);

                toast.success('PDF berhasil diunduh');
            } catch (error) {
                const errorMsg = handleError(error);
                this.error = errorMsg;
                const errorText = typeof errorMsg === 'string'
                    ? errorMsg
                    : (errorMsg?.message || 'Gagal mengunduh PDF');
                toast.error(errorText);
            } finally {
                this.loading = false;
            }
        },
    }
});
