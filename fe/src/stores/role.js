import { defineStore } from "pinia";
import { axiosInstance } from "@/plugins/axios";
import { handleError } from "@/helpers/errorHelper";

export const useRoleStore = defineStore("role", {
    state: () => ({
        roles: [],
        role: null,
        permissions: {},
        loading: false,
        success: null,
        error: null,
    }),

    actions: {
        async fetchRoles(params = {}) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/roles", { params });
                this.roles = response.data.data || [];
                return this.roles;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchRole(id) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get(`/roles/${id}`);
                this.role = response.data.data;
                return this.role;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchPermissions() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axiosInstance.get("/permissions");
                this.permissions = response.data.data || {};
                return this.permissions;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createRole(data) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await axiosInstance.post("/roles", data);
                this.success = response.data.message || "Role berhasil dibuat";
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateRole(id, data) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await axiosInstance.put(`/roles/${id}`, data);
                this.success = response.data.message || "Role berhasil diperbarui";
                return response.data.data;
            } catch (error) {
                this.error = handleError(error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteRole(id) {
            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await axiosInstance.delete(`/roles/${id}`);
                this.success = response.data.message || "Role berhasil dihapus";
                // Remove from local state
                this.roles = this.roles.filter((r) => r.id !== id);
                return true;
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
