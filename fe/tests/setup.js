import { vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

// Mock js-cookie
vi.mock('js-cookie', () => ({
    default: {
        get: vi.fn(),
        set: vi.fn(),
        remove: vi.fn(),
    },
}))

// Mock vue-router with proper createRouter export
vi.mock('vue-router', () => ({
    createRouter: vi.fn(() => ({
        push: vi.fn(),
        replace: vi.fn(),
        go: vi.fn(),
        back: vi.fn(),
    })),
    createWebHistory: vi.fn(),
    useRouter: () => ({
        push: vi.fn(),
        replace: vi.fn(),
        go: vi.fn(),
        back: vi.fn(),
    }),
    useRoute: () => ({
        params: {},
        query: {},
        path: '/',
    }),
}))

// Mock the router module directly
vi.mock('@/router', () => ({
    default: {
        push: vi.fn(),
        replace: vi.fn(),
        go: vi.fn(),
        back: vi.fn(),
    },
}))

// Mock vue-toastification
vi.mock('vue-toastification', () => ({
    useToast: () => ({
        success: vi.fn(),
        error: vi.fn(),
        warning: vi.fn(),
        info: vi.fn(),
    }),
}))

// Setup Pinia before each test
beforeEach(() => {
    setActivePinia(createPinia())
})

// Global test utilities
global.createMockAxiosError = (status, data) => ({
    response: {
        status,
        data,
    },
})

global.createMockAxiosResponse = (data) => ({
    data,
})
