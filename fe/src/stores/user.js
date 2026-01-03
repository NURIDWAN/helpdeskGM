import { defineStore } from "pinia";
import { axiosInstance } from '@/plugins/axios';
import { handleError } from "@/helpers/errorHelper";
import router from "@/router";

export const useUserStore = defineStore("user", {
    state: () => ({
        users: [],
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
        async fetchUsers(params) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`/users`, { params })

                this.users = response.data.data

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async fetchUsersPaginated(params) {
            this.loading = true;

            try {
                const response = await axiosInstance.get('/users/all/paginated', { params });

                this.users = response.data.data.data;
                this.meta = response.data.data.meta;
            } catch (error) {
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchUser(id) {
            this.loading = true

            try {
                const response = await axiosInstance.get(`/users/${id}`)

                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async createUser(payload) {
            this.loading = true

            try {
                const response = await axiosInstance.post("/users", payload)

                this.success = response.data.message

                router.push({ name: 'admin.users' })
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateUser(id, payload) {

            try {
                const response = await axiosInstance.post(`/users/${id}`, {
                    _method: 'PUT',
                    ...payload
                })

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteUser(id) {
            this.loading = true

            try {
                const response = await axiosInstance.delete(`/users/${id}`)

                this.success = response.data.message
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },
    }
})
