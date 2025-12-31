import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";
import router from "@/router";

export const useBranchStore = defineStore("branch", {
    state: () => ({
        branches: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0
        },
        loading: false,
        error: null,
        success: null,
    }),

    actions: {
        async fetchBranches(params) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`branches`, { params })

                this.branches = response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchBranchesPaginated(params) {
            this.loading = true;

            try {
                const response = await axiosInstance.get('/branches/all/paginated', { params });

                this.branches = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchBranch(id) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`/branches/${id}`)

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createBranch(payload) {
            this.loading = true

            try {
                const config = {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                };

                const response = await axiosInstance.post("/branches", payload, config)

                this.success = response.data.message

                router.push({ name: 'admin.branches' })
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateBranch(id, payload) {
            this.loading = true

            try {
                const config = {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                };

                // Add _method for Laravel method spoofing
                if (payload instanceof FormData) {
                    payload.append('_method', 'PUT');
                } else {
                    payload._method = 'PUT';
                }

                const response = await axiosInstance.post(`/branches/${id}`, payload, config)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteBranch(id) {
            this.loading = true

            try {
                const response = await axiosInstance.delete(`/branches/${id}`)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },
    }
})