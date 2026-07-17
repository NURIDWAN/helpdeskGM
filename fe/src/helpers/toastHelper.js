// Toast helper for use in stores and components
import { useToastStore } from '@/stores/toast'

const getToast = () => useToastStore()

export const showSuccessToast = (message) => {
    getToast().success(message)
}

export const showErrorToast = (message) => {
    getToast().error(message)
}

export const showWarningToast = (message) => {
    getToast().warning(message)
}

export const showInfoToast = (message) => {
    getToast().info(message)
}

export default {
    success: showSuccessToast,
    error: showErrorToast,
    warning: showWarningToast,
    info: showInfoToast,
}
