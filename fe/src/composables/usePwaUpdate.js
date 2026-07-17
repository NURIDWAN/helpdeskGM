import { ref, watch } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'
import { useToastStore } from '@/stores/toast'

const RELOAD_TIMEOUT_MS = 5000
const AUTO_HIDE_MS = 5000
const DISMISS_STORAGE_KEY = 'pwa-update-dismissed-date'

export function usePwaUpdate() {
  const toast = useToastStore()

  const { needRefresh, offlineReady, updateServiceWorker } = useRegisterSW()

  const updateDismissed = ref(false)
  let autoHideTimer = null

  // Check if user dismissed today already
  const isDismissedToday = () => {
    const dismissedDate = localStorage.getItem(DISMISS_STORAGE_KEY)
    if (!dismissedDate) return false
    const today = new Date().toISOString().split('T')[0]
    return dismissedDate === today
  }

  // Initialize dismissed state from localStorage
  if (isDismissedToday()) {
    updateDismissed.value = true
  }

  // Auto-hide after 5 seconds when prompt becomes visible
  watch(
    () => needRefresh.value && !updateDismissed.value,
    (visible) => {
      if (visible) {
        clearTimeout(autoHideTimer)
        autoHideTimer = setTimeout(() => {
          updateDismissed.value = true
        }, AUTO_HIDE_MS)
      } else {
        clearTimeout(autoHideTimer)
      }
    },
    { immediate: true }
  )

  async function updateApp() {
    clearTimeout(autoHideTimer)
    const reloadTimeout = setTimeout(() => {
      toast.error('Pembaruan gagal. Silakan reload halaman secara manual')
    }, RELOAD_TIMEOUT_MS)

    try {
      await updateServiceWorker(true)
      clearTimeout(reloadTimeout)
      window.location.reload()
    } catch {
      clearTimeout(reloadTimeout)
      toast.error('Pembaruan gagal. Silakan reload halaman secara manual')
    }
  }

  function dismissUpdate() {
    clearTimeout(autoHideTimer)
    updateDismissed.value = true
    // Save today's date so it won't show again until tomorrow
    const today = new Date().toISOString().split('T')[0]
    localStorage.setItem(DISMISS_STORAGE_KEY, today)
  }

  return {
    needRefresh,
    offlineReady,
    updateApp,
    dismissUpdate,
    updateDismissed,
  }
}
