import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

// We need to override the mock in setup.js for permission tests
vi.mock('@/stores/auth', () => ({
    useAuthStore: vi.fn(),
}))

// Import the helpers after mocking
import { can, canOneOf, hasRole } from '@/helpers/permissionHelper'

describe('permissionHelper', () => {
    beforeEach(() => {
        // Reset the mock before each test
        vi.resetAllMocks()
    })

    describe('can', () => {
        it('should return true when user has permission', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: ['read-tickets', 'create-tickets', 'update-tickets'],
                },
            })

            expect(can('read-tickets')).toBe(true)
            expect(can('create-tickets')).toBe(true)
        })

        it('should return false when user does not have permission', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: ['read-tickets'],
                },
            })

            expect(can('delete-tickets')).toBe(false)
        })

        it('should return false when user has no permissions', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: [],
                },
            })

            expect(can('read-tickets')).toBe(false)
        })

        it('should return false when user is null', () => {
            useAuthStore.mockReturnValue({
                user: null,
            })

            expect(can('read-tickets')).toBe(false)
        })
    })

    describe('canOneOf', () => {
        it('should return true when user has at least one permission', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: ['read-tickets', 'create-tickets'],
                },
            })

            expect(canOneOf(['read-tickets', 'delete-tickets'])).toBe(true)
            expect(canOneOf(['create-tickets', 'update-tickets'])).toBe(true)
        })

        it('should return false when user has none of the permissions', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: ['read-tickets'],
                },
            })

            expect(canOneOf(['create-tickets', 'delete-tickets'])).toBe(false)
        })

        it('should return false for empty permissions array', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: ['read-tickets'],
                },
            })

            expect(canOneOf([])).toBe(false)
        })

        it('should return false for null permissions', () => {
            useAuthStore.mockReturnValue({
                user: {
                    permissions: ['read-tickets'],
                },
            })

            expect(canOneOf(null)).toBe(false)
        })
    })

    describe('hasRole', () => {
        it('should return true when user has role', () => {
            useAuthStore.mockReturnValue({
                user: {
                    roles: ['admin', 'staff'],
                },
            })

            expect(hasRole('admin')).toBe(true)
            expect(hasRole('staff')).toBe(true)
        })

        it('should return false when user does not have role', () => {
            useAuthStore.mockReturnValue({
                user: {
                    roles: ['staff'],
                },
            })

            expect(hasRole('admin')).toBe(false)
            expect(hasRole('superadmin')).toBe(false)
        })

        it('should return false when user has no roles', () => {
            useAuthStore.mockReturnValue({
                user: {
                    roles: [],
                },
            })

            expect(hasRole('admin')).toBe(false)
        })

        it('should return false when user is null', () => {
            useAuthStore.mockReturnValue({
                user: null,
            })

            expect(hasRole('admin')).toBe(false)
        })
    })
})
