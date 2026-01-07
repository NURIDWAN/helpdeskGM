import { describe, it, expect, vi } from 'vitest'
import { handleError } from '@/helpers/errorHelper'

describe('errorHelper', () => {
    describe('handleError', () => {
        it('should return validation errors for 422 status', () => {
            const error = {
                response: {
                    status: 422,
                    data: {
                        errors: {
                            email: ['Email is required'],
                            password: ['Password must be at least 8 characters'],
                        },
                    },
                },
            }

            const result = handleError(error)

            expect(result).toEqual({
                email: ['Email is required'],
                password: ['Password must be at least 8 characters'],
            })
        })

        it('should return message for 401 status', () => {
            const error = {
                response: {
                    status: 401,
                    data: {
                        message: 'Unauthorized',
                    },
                },
            }

            const result = handleError(error)

            expect(result).toBe('Unauthorized')
        })

        it('should return message for 400 status', () => {
            const error = {
                response: {
                    status: 400,
                    data: {
                        message: 'Bad Request',
                    },
                },
            }

            const result = handleError(error)

            expect(result).toBe('Bad Request')
        })

        it('should return message for 500 status', () => {
            const error = {
                response: {
                    status: 500,
                    data: {
                        message: 'Internal Server Error',
                    },
                },
            }

            const result = handleError(error)

            expect(result).toBe('Internal Server Error')
        })

        it('should log error for unknown status', () => {
            const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => { })
            const error = {
                response: {
                    status: 418,
                    data: {},
                },
            }

            handleError(error)

            expect(consoleSpy).toHaveBeenCalledWith(error)
            consoleSpy.mockRestore()
        })

        it('should log error when no response', () => {
            const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => { })
            const error = new Error('Network error')

            handleError(error)

            expect(consoleSpy).toHaveBeenCalled()
            consoleSpy.mockRestore()
        })
    })
})
