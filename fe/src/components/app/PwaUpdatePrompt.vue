<script setup>
import { usePwaUpdate } from '@/composables/usePwaUpdate'

const { needRefresh, updateDismissed, updateApp, dismissUpdate } = usePwaUpdate()
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-4 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-4 opacity-0"
  >
    <div
      v-if="needRefresh && !updateDismissed"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
      class="fixed bottom-4 right-4 z-50 flex max-w-sm items-center gap-3 rounded-lg border border-blue-200 bg-white px-4 py-3 shadow-lg"
    >
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-slate-900">
          Versi baru tersedia
        </p>
        <p class="mt-0.5 text-xs text-slate-500">
          Perbarui untuk mendapatkan fitur dan perbaikan terbaru.
        </p>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <button
          type="button"
          class="rounded-md px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300"
          aria-label="Tunda pembaruan"
          @click="dismissUpdate"
        >
          Nanti
        </button>
        <button
          type="button"
          class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          aria-label="Perbarui aplikasi sekarang"
          @click="updateApp"
        >
          Perbarui
        </button>
      </div>
    </div>
  </Transition>
</template>
