<template>
  <div v-if="meta.last_page > 1" class="bg-white border-t border-gray-200">
    <!-- Mobile Pagination -->
    <div class="sm:hidden px-4 py-3">
      <div class="flex items-center justify-between">
        <button
          @click="goToPage(meta.current_page - 1)"
          :disabled="meta.current_page === 1"
          class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <ChevronLeft :size="16" class="mr-1" />
          Sebelumnya
        </button>

        <span class="text-sm text-gray-500">
          {{ meta.current_page }} dari {{ meta.last_page }}
        </span>

        <button
          @click="goToPage(meta.current_page + 1)"
          :disabled="meta.current_page === meta.last_page"
          class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Selanjutnya
          <ChevronRight :size="16" class="ml-1" />
        </button>
      </div>
    </div>

    <!-- Desktop Pagination -->
    <div class="hidden sm:block px-6 py-4">
      <div class="flex items-center justify-between">
        <!-- Info Text -->
        <div class="flex items-center space-x-2">
          <p class="text-sm text-gray-700">
            Menampilkan
            <span class="font-semibold text-gray-900">
              {{ (meta.current_page - 1) * meta.per_page + 1 }}
            </span>
            -
            <span class="font-semibold text-gray-900">
              {{ Math.min(meta.current_page * meta.per_page, meta.total) }}
            </span>
            dari
            <span class="font-semibold text-gray-900">{{ meta.total }}</span>
            hasil
          </p>
        </div>

        <!-- Pagination Controls -->
        <div class="flex items-center space-x-1">
          <!-- Previous Button -->
          <button
            @click="goToPage(meta.current_page - 1)"
            :disabled="meta.current_page === 1"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronLeft :size="16" class="mr-1" />
            Sebelumnya
          </button>

          <!-- Page Numbers -->
          <div class="flex items-center space-x-1">
            <!-- First page -->
            <button
              v-if="showFirstPage"
              @click="goToPage(1)"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
            >
              1
            </button>

            <!-- Ellipsis -->
            <span
              v-if="showFirstEllipsis"
              class="px-3 py-2 text-sm text-gray-500"
            >
              ...
            </span>

            <!-- Visible pages -->
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                page === meta.current_page
                  ? 'bg-blue-600 text-white border border-blue-600'
                  : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-700',
              ]"
            >
              {{ page }}
            </button>

            <!-- Ellipsis -->
            <span
              v-if="showLastEllipsis"
              class="px-3 py-2 text-sm text-gray-500"
            >
              ...
            </span>

            <!-- Last page -->
            <button
              v-if="showLastPage"
              @click="goToPage(meta.last_page)"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
            >
              {{ meta.last_page }}
            </button>
          </div>

          <!-- Next Button -->
          <button
            @click="goToPage(meta.current_page + 1)"
            :disabled="meta.current_page === meta.last_page"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Selanjutnya
            <ChevronRight :size="16" class="ml-1" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

// Props
const props = defineProps({
  meta: {
    type: Object,
    required: true,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    }),
  },
});

// Emits
const emit = defineEmits(["page-change"]);

// Computed
const visiblePages = computed(() => {
  const current = props.meta.current_page;
  const last = props.meta.last_page;
  const delta = 2;

  let start = Math.max(1, current - delta);
  let end = Math.min(last, current + delta);

  if (end - start < 2 * delta) {
    if (start === 1) {
      end = Math.min(last, start + 2 * delta);
    } else {
      start = Math.max(1, end - 2 * delta);
    }
  }

  return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const showFirstPage = computed(() => {
  return props.meta.current_page > 3 && props.meta.last_page > 5;
});

const showLastPage = computed(() => {
  return (
    props.meta.current_page < props.meta.last_page - 2 &&
    props.meta.last_page > 5
  );
});

const showFirstEllipsis = computed(() => {
  return props.meta.current_page > 4 && props.meta.last_page > 5;
});

const showLastEllipsis = computed(() => {
  return (
    props.meta.current_page < props.meta.last_page - 3 &&
    props.meta.last_page > 5
  );
});

// Methods
const goToPage = (page) => {
  if (page >= 1 && page <= props.meta.last_page) {
    emit("page-change", page);
  }
};
</script>
