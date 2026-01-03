import { useToast } from 'vue-toastification'

// Toast helper for use in stores and components
const toast = useToast()

export const showSuccessToast = (message) => {
    toast.success(message)
}

export const showErrorToast = (message) => {
    toast.error(message)
}

export const showWarningToast = (message) => {
    toast.warning(message)
}

export const showInfoToast = (message) => {
    toast.info(message)
}

export default {
    success: showSuccessToast,
    error: showErrorToast,
    warning: showWarningToast,
    info: showInfoToast,
}
