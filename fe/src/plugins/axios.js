import axios from 'axios'
import Cookies from 'js-cookie'
import router from '@/router'
import { useToast } from 'vue-toastification'

const token = Cookies.get('token')

axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

// Request interceptor
axios.interceptors.request.use(
    config => {
        const token = Cookies.get('token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
)

// Response interceptor for error handling
axios.interceptors.response.use(
    response => response,
    error => {
        const toast = useToast()
        const status = error.response?.status

        // Handle different HTTP error codes
        switch (status) {
            case 401:
                // Unauthorized - clear token and redirect to login
                Cookies.remove('token')
                toast.error('Sesi Anda telah berakhir. Silakan login kembali.')
                router.push({ name: 'login' })
                break

            case 403:
                // Forbidden - redirect to 403 page
                toast.error('Anda tidak memiliki akses ke halaman ini.')
                router.push({ name: 'error.forbidden' })
                break

            case 404:
                // Not Found - show toast (don't redirect, let component handle)
                // toast.error('Data tidak ditemukan.')
                break

            case 422:
                // Validation error - let component handle it
                break

            case 500:
            case 502:
            case 503:
                // Server error - show toast
                toast.error('Terjadi kesalahan pada server. Silakan coba lagi nanti.')
                break

            default:
                // Other errors
                if (!error.response) {
                    toast.error('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.')
                }
                break
        }

        return Promise.reject(error)
    }
)

export const axiosInstance = axios

// Helper to redirect to error pages programmatically
export const redirectToError = (code) => {
    switch (code) {
        case 401:
            router.push({ name: 'error.unauthorized' })
            break
        case 403:
            router.push({ name: 'error.forbidden' })
            break
        case 404:
            router.push({ name: 'error.notfound' })
            break
        case 500:
            router.push({ name: 'error.server' })
            break
        default:
            router.push({ name: 'error.notfound' })
    }
}

