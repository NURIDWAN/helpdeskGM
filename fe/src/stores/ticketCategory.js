import { defineStore } from "pinia";
import { axiosInstance as api } from "@/plugins/axios";

export const useTicketCategoryStore = defineStore("ticketCategory", {
    state: () => ({
        categories: [],
        category: null,
        loading: false,
        error: null,
        meta: null,
    }),

    actions: {
        async fetchCategories(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get("/ticket-categories", { params });
                this.categories = response.data.data;
                this.meta = response.data.meta || null;
                return this.categories;
            } catch (error) {
                this.error = error.response?.data?.message || "Failed to fetch categories";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchCategory(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get(`/ticket-categories/${id}`);
                this.category = response.data.data;
                return this.category;
            } catch (error) {
                this.error = error.response?.data?.message || "Failed to fetch category";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createCategory(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post("/ticket-categories", data);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.errors || error.response?.data?.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateCategory(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.put(`/ticket-categories/${id}`, data);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.errors || error.response?.data?.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteCategory(id) {
            this.loading = true;
            this.error = null;
            try {
                await api.delete(`/ticket-categories/${id}`);
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || "Failed to delete category";
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
