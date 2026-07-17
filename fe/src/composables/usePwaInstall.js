import { ref, onMounted, onUnmounted } from 'vue'
import { useToastStore } from '@/stores/toast'

/** @type {import('vue').Ref<Event|null>} */
let deferredPrompt = null
const isInstallable = ref(false)
const isStandalone = ref(false)

let initialized = false

function handleBeforeInstallPrompt(e) {
  e.preventDefault()
  deferredPrompt = e
  isInstallable.value = true
}

function handleAppInstalled() {
  deferredPrompt = null
  isInstallable.value = false
}

function detectStandalone() {
  if (typeof window === 'undefined') return false
  const mq = window.matchMedia('(display-mode: standalone)')
  isStandalone.value = mq.matches
  mq.addEventListener('change', (e) => {
    isStandalone.value = e.matches
  })
}

export function usePwaInstall() {
  onMounted(() => {
    if (!initialized) {
      initialized = true
      detectStandalone()
      window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
      window.addEventListener('appinstalled', handleAppInstalled)
    }
  })

  onUnmounted(() => {
    // Cleanup is intentionally not removing listeners since the state is shared
    // across components. The listeners persist for the lifetime of the app.
  })

  async function promptInstall() {
    if (!deferredPrompt) {
      const toast = useToastStore()
      toast.error('Instalasi tidak dapat dilakukan saat ini')
      isInstallable.value = false
      return
    }

    try {
      await deferredPrompt.prompt()
      const { outcome } = await deferredPrompt.userChoice
      if (outcome === 'dismissed') {
        isInstallable.value = false
      }
    } catch {
      const toast = useToastStore()
      toast.error('Instalasi tidak dapat dilakukan saat ini')
      isInstallable.value = false
    } finally {
      deferredPrompt = null
    }
  }

  function dismissInstall() {
    isInstallable.value = false
  }

  return {
    isInstallable,
    isStandalone,
    promptInstall,
    dismissInstall,
  }
}
