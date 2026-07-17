<script setup>
import { usePwaInstall } from '@/composables/usePwaInstall'
import { Download, X } from 'lucide-vue-next'

const { isInstallable, isStandalone, promptInstall, dismissInstall } = usePwaInstall()
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="isInstallable && !isStandalone"
      role="banner"
      aria-label="Install aplikasi"
      class="fixed inset-x-0 bottom-0 z-50 p-4"
    >
      <div
        class="mx-auto flex max-w-lg items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg"
      >
        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg overflow-hidden"
        >
          <img src="/logo.png" alt="GA Maintenance" class="h-9 w-9" />
        </span>

        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-slate-900">Install GA Maintenance</p>
          <p class="text-xs text-slate-500">Akses cepat dari home screen perangkat Anda</p>
        </div>

        <button
          type="button"
          aria-label="Install aplikasi"
          class="inline-flex h-8 items-center rounded-md bg-blue-600 px-3 text-xs font-medium text-white transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
          @click="promptInstall"
        >
          Install
        </button>

        <button
          type="button"
          aria-label="Tutup prompt install"
          class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2"
          @click="dismissInstall"
        >
          <X :size="16" />
        </button>
      </div>
    </div>
  </Transition>
</template>
