<template>
  <div
    class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
  >
    <!-- Form Header -->
    <div
      class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200"
    >
      <div class="flex items-center gap-3">
        <div
          class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
        >
          <component :is="icon" :size="20" class="text-blue-600" />
        </div>
        <div>
          <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
          <p class="text-sm text-gray-600">{{ subtitle }}</p>
        </div>
      </div>
    </div>

    <!-- Form Content -->
    <div class="p-6">
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
