<template>
  <div class="bg-white rounded-lg shadow overflow-visible">
    <!-- Toolbar -->
    <div v-if="enableColumnFilter" class="px-4 py-2 border-b border-gray-200 flex justify-end">
       <div class="relative">
          <button 
            @click="showColumnMenu = !showColumnMenu"
            class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-md hover:bg-gray-100 transition-colors border border-transparent hover:border-gray-200"
          >
            <Settings :size="16" />
            <span>Tampilan Kolom</span>
          </button>
          
          <!-- Dropdown Menu -->
          <div v-if="showColumnMenu" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-100 z-50 p-2">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-2 py-2 mb-1 border-b">
              Pilih Kolom
            </div>
            <div class="max-h-64 overflow-y-auto space-y-1">
              <label 
                v-for="col in columns" 
                :key="col.key"
                class="flex items-center gap-2 px-2 py-1.5 hover:bg-gray-50 rounded cursor-pointer"
              >
                <input 
                  type="checkbox" 
                  :checked="visibleColumnKeys.includes(col.key)"
                  @change="toggleColumn(col.key)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4"
                />
                <span class="text-sm text-gray-700">{{ col.label }}</span>
              </label>
            </div>
          </div>
          
          <!-- Backdrop for closing -->
          <div v-if="showColumnMenu" @click="showColumnMenu = false" class="fixed inset-0 z-40" style="background: transparent;"></div>
       </div>
    </div>
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
              v-for="column in displayedColumns"
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
              v-for="column in displayedColumns"
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
import { computed, ref, onMounted, watch } from "vue";
import { ChevronLeft, ChevronRight, Settings, Check } from "lucide-vue-next";

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
  storageKey: {
    type: String,
    default: null,
  },
  enableColumnFilter: {
    type: Boolean,
    default: true,
  },
});

// Emits
const emit = defineEmits(["page-change", "per-page-change"]);

// State
const showColumnMenu = ref(false);
const visibleColumnKeys = ref([]); // Store keys of visible columns

// Initialize visible columns
onMounted(() => {
  // Initialize with all columns checking storage first
  if (props.storageKey) {
    const stored = localStorage.getItem(`datatable_prefs_${props.storageKey}`);
    if (stored) {
      try {
        const parsed = JSON.parse(stored);
        // Validate stored keys still exist in current columns (handle schema updates)
        const validKeys = parsed.filter(key => props.columns.some(col => col.key === key));
        if (validKeys.length > 0) {
          visibleColumnKeys.value = validKeys;
          return;
        }
      } catch (e) {
        console.error('Error parsing stored column prefs', e);
      }
    }
  }
  
  // Default: all columns are visible
  visibleColumnKeys.value = props.columns.map(col => col.key);
});

// Watch for column changes or storage updates
watch(visibleColumnKeys, (newKeys) => {
  if (props.storageKey && newKeys.length > 0) {
    localStorage.setItem(`datatable_prefs_${props.storageKey}`, JSON.stringify(newKeys));
  }
}, { deep: true });

// Also watch props.columns in case they change dynamically (ensure new columns appear or handled)
watch(() => props.columns, (newCols) => {
  // Logic: Add new columns to visible by default? Or keep strictly what's in keys?
  // Let's ensure new columns are added if they're not in the list but we haven't stored prefs for them specifically?
  // Simpler: Just ensure we don't have stale keys.
  // Ideally if user has customized, we respect it. New columns might need manual enabling unless we track "hidden" instead of "visible".
  // Let's stick to "visible keys".
  if (visibleColumnKeys.value.length === 0) {
     visibleColumnKeys.value = newCols.map(c => c.key);
  }
});

const displayedColumns = computed(() => {
  if (!props.enableColumnFilter) return props.columns;
  return props.columns.filter(col => visibleColumnKeys.value.includes(col.key));
});

const toggleColumn = (key) => {
  const index = visibleColumnKeys.value.indexOf(key);
  if (index === -1) {
    visibleColumnKeys.value.push(key);
  } else {
    // Prevent hiding all columns?
    if (visibleColumnKeys.value.length > 1) {
      visibleColumnKeys.value.splice(index, 1);
    }
  }
};

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
