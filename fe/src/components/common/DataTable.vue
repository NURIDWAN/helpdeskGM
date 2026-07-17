<template>
  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div
      v-if="enableColumnFilter"
      class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3"
    >
      <div>
        <p class="text-sm font-medium text-slate-900">Data</p>
        <p class="text-xs text-slate-500">
          {{ displayedColumns.length }} dari {{ columns.length }} kolom ditampilkan
        </p>
      </div>

      <div class="relative">
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="gap-2"
          @click="showColumnMenu = !showColumnMenu"
        >
          <Settings :size="16" />
          Kolom
        </Button>

        <div
          v-if="showColumnMenu"
          class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-md border border-slate-200 bg-white p-1 shadow-lg"
        >
          <div class="border-b border-slate-100 px-3 py-2">
            <p class="text-sm font-semibold text-slate-900">Tampilan kolom</p>
            <p class="text-xs text-slate-500">Pilih kolom yang ingin ditampilkan.</p>
          </div>
          <div class="max-h-64 overflow-y-auto py-1">
            <label
              v-for="col in columns"
              :key="col.key"
              class="flex cursor-pointer items-center gap-2 rounded-sm px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
            >
              <input
                type="checkbox"
                :checked="visibleColumnKeys.includes(col.key)"
                class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                @change="toggleColumn(col.key)"
              />
              <span class="min-w-0 flex-1 truncate">{{ col.label }}</span>
              <Check
                v-if="visibleColumnKeys.includes(col.key)"
                :size="14"
                class="text-blue-600"
              />
            </label>
          </div>
        </div>

        <div
          v-if="showColumnMenu"
          class="fixed inset-0 z-40"
          @click="showColumnMenu = false"
        ></div>
      </div>
    </div>

    <div v-if="loading" class="space-y-3 p-4">
      <div class="h-10 rounded-md bg-slate-100"></div>
      <div
        v-for="row in 5"
        :key="row"
        class="grid gap-3 rounded-md border border-slate-100 p-3 sm:grid-cols-4"
      >
        <div class="h-4 rounded bg-slate-100"></div>
        <div class="h-4 rounded bg-slate-100"></div>
        <div class="h-4 rounded bg-slate-100"></div>
        <div class="h-4 rounded bg-slate-100"></div>
      </div>
    </div>

    <div v-else-if="items.length === 0" class="px-6 py-12 text-center">
      <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-slate-100">
        <component :is="emptyIcon" :size="30" class="text-slate-400" />
      </div>
      <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ emptyMessage }}</h3>
      <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
        Data belum tersedia atau filter yang dipilih tidak memiliki hasil.
      </p>
      <div v-if="$slots.emptyAction" class="mt-5">
        <slot name="emptyAction" />
      </div>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th
              v-for="column in displayedColumns"
              :key="column.key"
              :class="[
                'whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500',
                column.align === 'right' ? 'text-right' : 'text-left',
              ]"
            >
              {{ column.label }}
            </th>
            <th
              v-if="showActions"
              class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr
            v-for="(item, index) in items"
            :key="getItemKey(item, index)"
            class="transition-colors hover:bg-slate-50/80"
          >
            <td
              v-for="column in displayedColumns"
              :key="column.key"
              :class="[
                'px-4 py-3 align-middle text-sm',
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
                    column.bold ? 'font-medium text-slate-950' : 'text-slate-700',
                  ]"
                >
                  {{ getNestedValue(item, column.key) }}
                </div>
              </slot>
            </td>
            <td
              v-if="showActions"
              class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium"
            >
              <slot name="actions" :item="item" :index="index"></slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="showPagination && meta && meta.last_page > 1"
      class="border-t border-slate-200 px-4 py-3"
    >
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
          <span>Tampilkan</span>
          <select
            :value="meta.per_page"
            class="h-9 rounded-md border border-slate-200 bg-white px-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            @change="handlePerPageChange"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span>per halaman</span>
          <span class="hidden text-slate-300 sm:inline">|</span>
          <span>
            {{ paginationStart }}-{{ paginationEnd }} dari
            <span class="font-medium text-slate-900">{{ meta.total }}</span>
          </span>
        </div>

        <nav class="flex items-center justify-end gap-1">
          <Button
            type="button"
            variant="outline"
            size="icon"
            class="size-9"
            :disabled="meta.current_page === 1"
            @click="goToPage(meta.current_page - 1)"
          >
            <ChevronLeft :size="18" />
          </Button>

          <Button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            :variant="page === meta.current_page ? 'default' : 'outline'"
            size="sm"
            class="size-9 px-0"
            @click="goToPage(page)"
          >
            {{ page }}
          </Button>

          <Button
            type="button"
            variant="outline"
            size="icon"
            class="size-9"
            :disabled="meta.current_page === meta.last_page"
            @click="goToPage(meta.current_page + 1)"
          >
            <ChevronRight :size="18" />
          </Button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from "vue";
import { Check, ChevronLeft, ChevronRight, Settings } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

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

const emit = defineEmits(["page-change", "per-page-change"]);

const showColumnMenu = ref(false);
const visibleColumnKeys = ref([]);

onMounted(() => {
  if (props.storageKey) {
    const stored = localStorage.getItem(`datatable_prefs_${props.storageKey}`);
    if (stored) {
      try {
        const parsed = JSON.parse(stored);
        const validKeys = parsed.filter((key) =>
          props.columns.some((col) => col.key === key)
        );
        if (validKeys.length > 0) {
          visibleColumnKeys.value = validKeys;
          return;
        }
      } catch (e) {
        console.error("Error parsing stored column prefs", e);
      }
    }
  }

  visibleColumnKeys.value = props.columns.map((col) => col.key);
});

watch(
  visibleColumnKeys,
  (newKeys) => {
    if (props.storageKey && newKeys.length > 0) {
      localStorage.setItem(
        `datatable_prefs_${props.storageKey}`,
        JSON.stringify(newKeys)
      );
    }
  },
  { deep: true }
);

watch(
  () => props.columns,
  (newCols) => {
    if (visibleColumnKeys.value.length === 0) {
      visibleColumnKeys.value = newCols.map((col) => col.key);
    }
  }
);

const displayedColumns = computed(() => {
  if (!props.enableColumnFilter) return props.columns;
  return props.columns.filter((col) => visibleColumnKeys.value.includes(col.key));
});

const paginationStart = computed(() => {
  if (!props.meta?.total) return 0;
  return (props.meta.current_page - 1) * props.meta.per_page + 1;
});

const paginationEnd = computed(() => {
  if (!props.meta?.total) return 0;
  return Math.min(props.meta.current_page * props.meta.per_page, props.meta.total);
});

const toggleColumn = (key) => {
  const index = visibleColumnKeys.value.indexOf(key);
  if (index === -1) {
    visibleColumnKeys.value.push(key);
    return;
  }

  if (visibleColumnKeys.value.length > 1) {
    visibleColumnKeys.value.splice(index, 1);
  }
};

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

  return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});

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
  emit("per-page-change", parseInt(event.target.value));
};
</script>
