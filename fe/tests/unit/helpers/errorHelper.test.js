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

        it('should return error message for unknown status', () => {
            const error = {
                response: {
                    status: 418,
                    data: {},
                },
            }

            const result = handleError(error)

            expect(result).toBe('An unexpected error occurred')
        })

        it('should return error message when no response', () => {
            const error = new Error('Network error')

            const result = handleError(error)

            expect(result).toBe('Network error')
        })
    })
})
