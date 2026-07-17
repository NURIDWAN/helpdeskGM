import { handleError } from "@/helpers/errorHelper";
import { axiosInstance } from "@/plugins/axios";
import Cookies from "js-cookie";
import { defineStore } from "pinia";
import router from "@/router";

export const useAuthStore = defineStore("auth", {
    state: () => ({
        user: null,
        loading: false,
        error: null,
        success: null,
    }),
    getters: {
        token: () => Cookies.get('token'),
        isAuthenticated: () => !!Cookies.get('token'),
    },
    actions: {
        async login(credentials) {
            this.loading = true
            this.error = null

            try {
                const response = await axiosInstance.post('/auth/login', credentials)

                const token = response.data.data.token

                Cookies.set('token', token, { expires: 7, sameSite: 'Lax' })

                this.success = response.data.message

                // Redirect based on admin panel access permission
                if (response.data.data.permissions.includes('system-admin-panel-access')) {
                    await router.push({ name: 'admin.dashboard' })
                } else {
                    await router.push({ name: 'app.dashboard' })
                }
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async checkAuth() {
            this.loading = true;
            try {
                const response = await axiosInstance.get('/auth/me');
                this.user = response.data.data;
                return this.user;
            } catch (error) {
                if (error.response && error.response.status === 401) {
                    Cookies.remove('token');
                    throw new Error("Unauthorized");
                }
                this.error = handleError(error);
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true

            try {
                await axiosInstance.post('/auth/logout')

                Cookies.remove('token')

                this.user = null
                this.error = null

                await router.push({ name: 'login' })
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async updateProfile(payload) {
            this.loading = true
            this.error = null
            this.success = null

            try {
                const response = await axiosInstance.put('/auth/me', payload)
                this.success = response.data.message || 'Profil berhasil diperbarui'
                // refresh user
                await this.checkAuth()
                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async uploadProfilePhoto(file) {
            this.loading = true
            this.error = null
            this.success = null

            try {
                const formData = new FormData()
                formData.append('photo', file)

                const response = await axiosInstance.post('/auth/me/photo', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
                this.success = response.data.message || 'Foto profil berhasil diperbarui'
                await this.checkAuth()
                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        },

        async deleteProfilePhoto() {
            this.loading = true
            this.error = null
            this.success = null

            try {
                const response = await axiosInstance.delete('/auth/me/photo')
                this.success = response.data.message || 'Foto profil berhasil dihapus'
                await this.checkAuth()
                return response.data.data
            } catch (error) {
                this.error = handleError(error)
            } finally {
                this.loading = false
            }
        }
    }
})
