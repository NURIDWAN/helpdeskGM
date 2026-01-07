import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

// Mock axios
vi.mock('@/plugins/axios', () => ({
    axiosInstance: {
        post: vi.fn(),
        get: vi.fn(),
        put: vi.fn(),
    },
}))

// Import router mock - it's already mocked in setup.js
import router from '@/router'
import { axiosInstance } from '@/plugins/axios'
import Cookies from 'js-cookie'

describe('Auth Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    describe('initial state', () => {
        it('should have correct initial state', () => {
            const store = useAuthStore()

            expect(store.user).toBeNull()
            expect(store.loading).toBe(false)
            expect(store.error).toBeNull()
            expect(store.success).toBeNull()
        })
    })

    describe('getters', () => {
        it('isAuthenticated should return false when user is null', () => {
            const store = useAuthStore()

            expect(store.isAuthenticated).toBe(false)
        })

        it('isAuthenticated should return true when user exists', () => {
            const store = useAuthStore()
            store.user = { id: 1, name: 'Test User' }

            expect(store.isAuthenticated).toBe(true)
        })
    })

    describe('login action', () => {
        it('should login successfully and redirect admin', async () => {
            const store = useAuthStore()

            axiosInstance.post.mockResolvedValue({
                data: {
                    data: {
                        token: 'test-token',
                        roles: ['admin'],
                    },
                    message: 'Login successful',
                },
            })

            await store.login({ email: 'admin@example.com', password: 'password' })

            expect(axiosInstance.post).toHaveBeenCalledWith('/auth/login', {
                email: 'admin@example.com',
                password: 'password',
            })
            expect(Cookies.set).toHaveBeenCalledWith('token', 'test-token')
            expect(store.success).toBe('Login successful')
            expect(router.push).toHaveBeenCalledWith({ name: 'admin.dashboard' })
        })

        it('should login successfully and redirect regular user', async () => {
            const store = useAuthStore()

            axiosInstance.post.mockResolvedValue({
                data: {
                    data: {
                        token: 'test-token',
                        roles: ['user'],
                    },
                    message: 'Login successful',
                },
            })

            await store.login({ email: 'user@example.com', password: 'password' })

            expect(router.push).toHaveBeenCalledWith({ name: 'app.dashboard' })
        })

        it('should handle login error', async () => {
            const store = useAuthStore()

            axiosInstance.post.mockRejectedValue({
                response: {
                    status: 401,
                    data: { message: 'Invalid credentials' },
                },
            })

            await store.login({ email: 'wrong@example.com', password: 'wrong' })

            expect(store.error).toBe('Invalid credentials')
            expect(store.loading).toBe(false)
        })
    })

    describe('checkAuth action', () => {
        it('should set user when authenticated', async () => {
            const store = useAuthStore()

            axiosInstance.get.mockResolvedValue({
                data: {
                    data: {
                        id: 1,
                        name: 'Test User',
                        email: 'test@example.com',
                    },
                },
            })

            const user = await store.checkAuth()

            expect(axiosInstance.get).toHaveBeenCalledWith('/auth/me')
            expect(store.user).toEqual({
                id: 1,
                name: 'Test User',
                email: 'test@example.com',
            })
            expect(user).toEqual(store.user)
        })

        it('should throw error and remove token on 401', async () => {
            const store = useAuthStore()

            axiosInstance.get.mockRejectedValue({
                response: {
                    status: 401,
                },
            })

            await expect(store.checkAuth()).rejects.toThrow('Unauthorized')
            expect(Cookies.remove).toHaveBeenCalledWith('token')
        })
    })

    describe('logout action', () => {
        it('should logout and redirect to login', async () => {
            const store = useAuthStore()
            store.user = { id: 1, name: 'Test User' }

            axiosInstance.post.mockResolvedValue({})

            await store.logout()

            expect(axiosInstance.post).toHaveBeenCalledWith('/auth/logout')
            expect(Cookies.remove).toHaveBeenCalledWith('token')
            expect(store.user).toBeNull()
            expect(router.push).toHaveBeenCalledWith({ name: 'login' })
        })
    })

    describe('updateProfile action', () => {
        it('should update profile successfully', async () => {
            const store = useAuthStore()

            axiosInstance.put.mockResolvedValue({
                data: {
                    data: { id: 1, name: 'Updated Name' },
                    message: 'Profile updated',
                },
            })

            axiosInstance.get.mockResolvedValue({
                data: {
                    data: { id: 1, name: 'Updated Name', email: 'test@example.com' },
                },
            })

            const result = await store.updateProfile({ name: 'Updated Name' })

            expect(axiosInstance.put).toHaveBeenCalledWith('/auth/me', { name: 'Updated Name' })
            expect(store.success).toBe('Profile updated')
        })
    })
})
