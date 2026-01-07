import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTicketStore } from '@/stores/ticket'

// Mock axios
vi.mock('@/plugins/axios', () => ({
    axiosInstance: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    },
}))

// Mock toast
vi.mock('vue-toastification', () => ({
    useToast: () => ({
        success: vi.fn(),
        error: vi.fn(),
    }),
}))

import { axiosInstance } from '@/plugins/axios'

describe('Ticket Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    describe('initial state', () => {
        it('should have correct initial state', () => {
            const store = useTicketStore()

            expect(store.tickets).toEqual([])
            expect(store.loading).toBe(false)
            expect(store.error).toBeNull()
            expect(store.success).toBeNull()
            expect(store.meta).toEqual({
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
            })
        })
    })

    describe('fetchTickets action', () => {
        it('should fetch tickets successfully', async () => {
            const store = useTicketStore()

            const mockTickets = [
                { id: 1, code: 'TKT-001', title: 'Ticket 1' },
                { id: 2, code: 'TKT-002', title: 'Ticket 2' },
            ]

            axiosInstance.get.mockResolvedValue({
                data: { data: mockTickets },
            })

            await store.fetchTickets({})

            expect(axiosInstance.get).toHaveBeenCalledWith('/tickets', { params: {} })
            expect(store.tickets).toEqual(mockTickets)
            expect(store.loading).toBe(false)
        })

        it('should handle fetch error', async () => {
            const store = useTicketStore()

            axiosInstance.get.mockRejectedValue({
                response: {
                    status: 500,
                    data: { message: 'Server error' },
                },
            })

            await store.fetchTickets({})

            expect(store.error).toBe('Server error')
            expect(store.loading).toBe(false)
        })
    })

    describe('fetchTicketsPaginated action', () => {
        it('should fetch paginated tickets', async () => {
            const store = useTicketStore()

            axiosInstance.get.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, code: 'TKT-001' },
                            { id: 2, code: 'TKT-002' },
                        ],
                        meta: {
                            current_page: 1,
                            last_page: 5,
                            per_page: 10,
                            total: 50,
                        },
                    },
                },
            })

            await store.fetchTicketsPaginated({ page: 1, per_page: 10 })

            expect(axiosInstance.get).toHaveBeenCalledWith('/tickets/all/paginated', {
                params: { page: 1, per_page: 10 },
            })
            expect(store.tickets).toHaveLength(2)
            expect(store.meta.total).toBe(50)
        })
    })

    describe('fetchTicket action', () => {
        it('should fetch single ticket by id', async () => {
            const store = useTicketStore()

            const mockTicket = { id: 1, code: 'TKT-001', title: 'Test Ticket' }

            axiosInstance.get.mockResolvedValue({
                data: { data: mockTicket },
            })

            const result = await store.fetchTicket(1)

            expect(axiosInstance.get).toHaveBeenCalledWith('/tickets/1')
            expect(result).toEqual(mockTicket)
        })
    })

    describe('fetchTicketByCode action', () => {
        it('should fetch ticket by code', async () => {
            const store = useTicketStore()

            const mockTicket = { id: 1, code: 'TKT-001', title: 'Test Ticket' }

            axiosInstance.get.mockResolvedValue({
                data: { data: mockTicket },
            })

            const result = await store.fetchTicketByCode('TKT-001')

            expect(axiosInstance.get).toHaveBeenCalledWith('/tickets/code/TKT-001')
            expect(result).toEqual(mockTicket)
        })
    })

    describe('createTicket action', () => {
        it('should create ticket successfully', async () => {
            const store = useTicketStore()

            const newTicket = { id: 1, code: 'TKT-001', title: 'New Ticket' }

            axiosInstance.post.mockResolvedValue({
                data: {
                    data: newTicket,
                    message: 'Tiket berhasil dibuat',
                },
            })

            const result = await store.createTicket({
                title: 'New Ticket',
                description: 'Description',
            })

            expect(axiosInstance.post).toHaveBeenCalledWith('/tickets', {
                title: 'New Ticket',
                description: 'Description',
            })
            expect(result).toEqual(newTicket)
            expect(store.success).toBe('Tiket berhasil dibuat')
        })

        it('should handle create error', async () => {
            const store = useTicketStore()

            axiosInstance.post.mockRejectedValue({
                response: {
                    status: 422,
                    data: {
                        errors: {
                            title: ['Title is required'],
                        },
                    },
                },
            })

            await store.createTicket({})

            expect(store.error).toEqual({
                title: ['Title is required'],
            })
        })
    })

    describe('updateTicket action', () => {
        it('should update ticket successfully', async () => {
            const store = useTicketStore()

            axiosInstance.post.mockResolvedValue({
                data: {
                    message: 'Tiket berhasil diupdate',
                },
            })

            await store.updateTicket(1, { title: 'Updated Title' })

            expect(axiosInstance.post).toHaveBeenCalledWith('/tickets/1', {
                _method: 'PUT',
                title: 'Updated Title',
            })
            expect(store.success).toBe('Tiket berhasil diupdate')
        })
    })

    describe('deleteTicket action', () => {
        it('should delete ticket successfully', async () => {
            const store = useTicketStore()

            axiosInstance.delete.mockResolvedValue({
                data: {
                    message: 'Ticket deleted',
                },
            })

            await store.deleteTicket(1)

            expect(axiosInstance.delete).toHaveBeenCalledWith('/tickets/1')
            expect(store.success).toBe('Ticket deleted')
        })
    })

    describe('closeTicket action', () => {
        it('should close ticket successfully', async () => {
            const store = useTicketStore()

            axiosInstance.put.mockResolvedValue({
                data: {
                    message: 'Tiket berhasil ditutup',
                },
            })

            const result = await store.closeTicket(1)

            expect(axiosInstance.put).toHaveBeenCalledWith('/tickets/1/close')
            expect(result).toBe(true)
            expect(store.success).toBe('Tiket berhasil ditutup')
        })

        it('should return false on error', async () => {
            const store = useTicketStore()

            axiosInstance.put.mockRejectedValue({
                response: {
                    status: 400,
                    data: { message: 'Cannot close ticket' },
                },
            })

            const result = await store.closeTicket(1)

            expect(result).toBe(false)
            expect(store.error).toBe('Cannot close ticket')
        })
    })

    describe('createTicketReply action', () => {
        it('should create reply successfully', async () => {
            const store = useTicketStore()

            const mockReply = { id: 1, content: 'Reply content' }

            axiosInstance.post.mockResolvedValue({
                data: {
                    data: mockReply,
                    message: 'Reply created',
                },
            })

            const result = await store.createTicketReply(1, { content: 'Reply content' })

            expect(axiosInstance.post).toHaveBeenCalledWith('/tickets/1/replies', {
                content: 'Reply content',
            })
            expect(result).toEqual(mockReply)
        })
    })
})
