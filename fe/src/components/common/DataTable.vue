<template>
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
      ></div>
      <p class="mt-2 text-gray-600">Memuat data...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="items.length === 0" class="p-8 text-center">
      <component
        :is="emptyIcon"
        :size="48"
        class="mx-auto text-gray-400 mb-4"
      />
      <p class="text-gray-600">{{ emptyMessage }}</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider',
                column.align === 'right' ? 'text-right' : 'text-left',
              ]"
            >
              {{ column.label }}
            </th>
            <th
              v-if="showActions"
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(item, index) in items"
            :key="getItemKey(item, index)"
            class="hover:bg-gray-50"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              :class="[
                'px-6 py-4',
                column.align === 'right' ? 'text-right' : 'text-left',
                column.nowrap ? 'whitespace-nowrap' : '',
              ]"
            >
              <slot
                :name="`cell-${column.key}`"
                :item="item"
                :value="getNestedValue(item, column.key)"
                :index="index"
              >
                <div
                  :class="[
                    column.bold ? 'font-medium text-gray-900' : 'text-gray-900',
                  ]"
                >
                  {{ getNestedValue(item, column.key) }}
                </div>
              </slot>
            </td>
            <td
              v-if="showActions"
              class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
            >
              <slot name="actions" :item="item" :index="index">
                <!-- Default actions slot -->
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="showPagination && meta && meta.last_page > 1"
      class="border-t border-gray-200"
    >
      <div class="px-4 py-3 sm:px-6">
        <div class="flex items-center justify-between">
          <!-- Per Page Selector -->
          <div class="flex items-center space-x-2">
            <label class="text-sm text-gray-700">Tampilkan:</label>
            <select
              :value="meta.per_page"
              @change="handlePerPageChange"
              class="px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <span class="text-sm text-gray-700">per halaman</span>
          </div>

          <!-- Pagination Info -->
          <div class="text-sm text-gray-700">
            Menampilkan
            <span class="font-medium">{{
              (meta.current_page - 1) * meta.per_page + 1
            }}</span>
            sampai
            <span class="font-medium">{{
              Math.min(meta.current_page * meta.per_page, meta.total)
            }}</span>
            dari
            <span class="font-medium">{{ meta.total }}</span>
            hasil
          </div>
        </div>

        <!-- Pagination Controls -->
        <div class="flex items-center justify-center mt-4">
          <nav
            class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
          >
            <!-- Previous Button -->
            <button
              @click="goToPage(meta.current_page - 1)"
              :disabled="meta.current_page === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeft :size="20" />
            </button>

            <!-- Page Numbers -->
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                page === meta.current_page
                  ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
              ]"
            >
              {{ page }}
            </button>

            <!-- Next Button -->
            <button
              @click="goToPage(meta.current_page + 1)"
              :disabled="meta.current_page === meta.last_page"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRight :size="20" />
            </button>
          </nav>
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
  items: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  showActions: {
    type: Boolean,
    default: true,
  },
  showPagination: {
    type: Boolean,
    default: true,
  },
  emptyMessage: {
    type: String,
    default: "Belum ada data",
  },
  emptyIcon: {
    type: [String, Object, Function],
    default: "div",
  },
  itemKey: {
    type: String,
    default: "id",
  },
  meta: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    }),
  },
});

// Emits
const emit = defineEmits(["page-change", "per-page-change"]);

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

// Methods
const getNestedValue = (obj, path) => {
  return path.split(".").reduce((current, key) => {
    return current && current[key] !== undefined ? current[key] : "";
  }, obj);
};

const getItemKey = (item, index) => {
  return getNestedValue(item, props.itemKey) || index;
};

const goToPage = (page) => {
  if (page >= 1 && page <= props.meta.last_page) {
    emit("page-change", page);
  }
};

const handlePerPageChange = (event) => {
  const newPerPage = parseInt(event.target.value);
  emit("per-page-change", newPerPage);
};
</script>
