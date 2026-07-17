<template>
  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-md bg-blue-50 text-blue-600">
          <component :is="icon" :size="20" />
        </div>
        <div class="min-w-0">
          <h2 class="truncate text-base font-semibold text-slate-950">{{ title }}</h2>
          <p class="mt-0.5 text-sm text-slate-500">{{ subtitle }}</p>
        </div>
      </div>
    </div>

    <div class="p-5 sm:p-6">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    required: true,
  },
  icon: {
    type: [String, Object, Function],
    required: true,
  },
});
</script>
