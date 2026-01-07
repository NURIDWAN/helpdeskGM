import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useBranchStore } from '@/stores/branch'

// Mock axios
vi.mock('@/plugins/axios', () => ({
    axiosInstance: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    },
}))

// Router is already mocked in setup.js

import { axiosInstance } from '@/plugins/axios'

describe('Branch Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    describe('initial state', () => {
        it('should have correct initial state', () => {
            const store = useBranchStore()

            expect(store.branches).toEqual([])
            expect(store.loading).toBe(false)
            expect(store.error).toBeNull()
            expect(store.success).toBeNull()
        })
    })

    describe('fetchBranches action', () => {
        it('should fetch branches successfully', async () => {
            const store = useBranchStore()

            const mockBranches = [
                { id: 1, name: 'Branch 1' },
                { id: 2, name: 'Branch 2' },
            ]

            axiosInstance.get.mockResolvedValue({
                data: { data: mockBranches },
            })

            await store.fetchBranches()

            expect(axiosInstance.get).toHaveBeenCalledWith('/branches', { params: undefined })
            expect(store.branches).toEqual(mockBranches)
            expect(store.loading).toBe(false)
        })

        it('should handle fetch error', async () => {
            const store = useBranchStore()

            axiosInstance.get.mockRejectedValue({
                response: {
                    status: 500,
                    data: { message: 'Server error' },
                },
            })

            await store.fetchBranches()

            expect(store.error).toBe('Server error')
        })
    })

    describe('fetchBranchesPaginated action', () => {
        it('should fetch paginated branches', async () => {
            const store = useBranchStore()

            axiosInstance.get.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, name: 'Branch 1' }],
                        meta: { current_page: 1, total: 10 },
                    },
                },
            })

            await store.fetchBranchesPaginated({ page: 1 })

            expect(axiosInstance.get).toHaveBeenCalledWith('/branches/all/paginated', {
                params: { page: 1 },
            })
            expect(store.branches).toHaveLength(1)
        })
    })

    describe('createBranch action', () => {
        it('should create branch successfully', async () => {
            const store = useBranchStore()

            axiosInstance.post.mockResolvedValue({
                data: {
                    data: { id: 1, name: 'New Branch' },
                    message: 'Branch created',
                },
            })

            await store.createBranch({ name: 'New Branch' })

            expect(axiosInstance.post).toHaveBeenCalled()
            expect(store.success).toBe('Branch created')
        })
    })

    describe('updateBranch action', () => {
        it('should update branch successfully', async () => {
            const store = useBranchStore()

            axiosInstance.post.mockResolvedValue({
                data: {
                    message: 'Branch updated',
                },
            })

            await store.updateBranch(1, { name: 'Updated Branch', _method: 'PUT' })

            expect(axiosInstance.post).toHaveBeenCalled()
            expect(store.success).toBe('Branch updated')
        })
    })

    describe('deleteBranch action', () => {
        it('should delete branch successfully', async () => {
            const store = useBranchStore()

            axiosInstance.delete.mockResolvedValue({
                data: {
                    message: 'Branch deleted',
                },
            })

            await store.deleteBranch(1)

            expect(axiosInstance.delete).toHaveBeenCalledWith('/branches/1')
            expect(store.success).toBe('Branch deleted')
        })

        it('should handle delete error', async () => {
            const store = useBranchStore()

            axiosInstance.delete.mockRejectedValue({
                response: {
                    status: 422,
                    data: {
                        errors: { branch: ['Cannot delete branch with tickets'] },
                    },
                },
            })

            await store.deleteBranch(1)

            expect(store.error).toEqual({
                branch: ['Cannot delete branch with tickets'],
            })
        })
    })
})
