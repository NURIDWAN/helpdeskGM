import { defineStore } from "pinia";
import { axiosInstance } from "@/plugins/axios";
import { handleError } from "@/helpers/errorHelper";

export const useWhatsAppSettingStore = defineStore("whatsappSetting", {
    state: () => ({
        settings: {
            enabled: "true",
            token: "",
            group_id: "",
            delay: "2",
        },
        templates: [],
        placeholders: {},
        loading: false,
        success: null,
        error: null,
    }),

    actions: {
        async fetchSettings() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/whatsapp-settings");
                this.settings = response.data.data || this.settings;
                return this.settings;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateSettings(data) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await axiosInstance.put("/whatsapp-settings", data);
                this.success = response.data.message || "Settings berhasil diperbarui";
                await this.fetchSettings();
                return true;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchTemplates() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/whatsapp-templates");
                this.templates = response.data.data || [];
                return this.templates;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateTemplate(id, data) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await axiosInstance.put(`/whatsapp-templates/${id}`, data);
                this.success = response.data.message || "Template berhasil diperbarui";
                await this.fetchTemplates();
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchPlaceholders(type) {
            try {
                const response = await axiosInstance.get(`/whatsapp-placeholders/${type}`);
                this.placeholders[type] = response.data.data || {};
                return this.placeholders[type];
            } catch (error) {
                console.error("Error fetching placeholders:", error);
                return {};
            }
        },

        async testSend(phone, message) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await axiosInstance.post("/whatsapp-test", { phone, message });
                this.success = response.data.message || "Pesan berhasil dikirim";
                return response.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async testSendGroup(message = null) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const payload = message ? { message } : {};
                const response = await axiosInstance.post("/whatsapp-test-group", payload);
                this.success = response.data.message || "Pesan berhasil dikirim ke grup";
                return response.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        clearMessages() {
            this.success = null;
            this.error = null;
        },
    },
});
