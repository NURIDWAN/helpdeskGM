<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { debounce } from "lodash";
import { DateTime } from "luxon";
import { useTicketStore } from "@/stores/ticket";
import { useTicketCategoryStore } from "@/stores/ticketCategory";
import { useFilterStorage } from "@/composables/useFilterStorage";
import Alert from "@/components/common/Alert.vue";
import Pagination from "@/components/common/Pagination.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  ChevronRight,
  Filter,
  Inbox,
  Plus,
  RotateCcw,
  Search,
  Ticket as TicketIcon,
} from "lucide-vue-next";

const ticketStore = useTicketStore();
const categoryStore = useTicketCategoryStore();
const { tickets, meta, loading, success, error } = storeToRefs(ticketStore);
const { fetchTicketsPaginated } = ticketStore;
const { categories } = storeToRefs(categoryStore);

const defaultFilters = {
  search: "",
  status: "",
  priority: "",
  categoryId: "",
  date: "",
};

const defaultPagination = {
  current_page: 1,
  per_page: 10,
};

const { saveState, loadState, clearState } = useFilterStorage(
  "ticket_filter_app_list",
  defaultFilters,
  defaultPagination
);

const filters = ref({ ...defaultFilters });
const pagination = ref({ ...defaultPagination });

const statusOptions = [
  { value: "", label: "Semua Status" },
  { value: "open", label: "Menunggu" },
  { value: "in_progress", label: "Diproses" },
  { value: "resolved", label: "Selesai" },
  { value: "closed", label: "Ditutup" },
];

const priorityOptions = [
  { value: "", label: "Semua Prioritas" },
  { value: "low", label: "Rendah" },
  { value: "medium", label: "Sedang" },
  { value: "high", label: "Tinggi" },
  { value: "urgent", label: "Urgent" },
];

const dateOptions = [
  { value: "", label: "Semua Tanggal" },
  { value: "today", label: "Hari Ini" },
  { value: "week", label: "Minggu Ini" },
  { value: "month", label: "Bulan Ini" },
];

const categoryOptions = computed(() => [
  { value: "", label: "Semua Kategori" },
  ...categories.value.map((category) => ({
    value: category.id,
    label: category.name,
  })),
]);

const fetchTickets = async () => {
  const params = {
    search: filters.value.search,
    status: filters.value.status,
    priority: filters.value.priority,
    category_id: filters.value.categoryId,
    row_per_page: pagination.value.per_page,
    page: pagination.value.current_page,
  };

  if (filters.value.date) {
    const now = DateTime.now();

    if (filters.value.date === "today") {
      params.start_date = now.toISODate();
      params.end_date = now.toISODate();
    }

    if (filters.value.date === "week") {
      params.start_date = now.startOf("week").toISODate();
      params.end_date = now.endOf("week").toISODate();
    }

    if (filters.value.date === "month") {
      params.start_date = now.startOf("month").toISODate();
      params.end_date = now.endOf("month").toISODate();
    }
  }

  Object.keys(params).forEach((key) => {
    if (params[key] === "" || params[key] === null || params[key] === undefined) {
      delete params[key];
    }
  });

  await fetchTicketsPaginated(params);
};

const handleFilterChange = () => {
  pagination.value.current_page = 1;
  fetchTickets();
};

const handlePageChange = (page) => {
  pagination.value.current_page = page;
  fetchTickets();
};

const handlePerPageChange = (newPerPage) => {
  pagination.value.per_page = newPerPage;
  pagination.value.current_page = 1;
  fetchTickets();
};

const clearFilters = () => {
  filters.value = { ...defaultFilters };
  pagination.value.current_page = 1;
  clearState();
  fetchTickets();
};

const ticketTitle = (ticket) =>
  ticket.category?.name || ticket.title || ticket.subject || "Tiket";

const statusLabel = (status) =>
  statusOptions.find((option) => option.value === status)?.label || "-";

const priorityLabel = (priority) =>
  priorityOptions.find((option) => option.value === priority)?.label || "-";

const statusBadgeVariant = (status) => {
  if (status === "open") return "warning";
  if (status === "in_progress") return "info";
  if (status === "resolved") return "success";
  return "muted";
};

const priorityBadgeVariant = (priority) => {
  if (priority === "urgent" || priority === "high") return "destructive";
  if (priority === "medium") return "warning";
  return "success";
};

const formatDate = (date) => {
  if (!date) return "-";
  return DateTime.fromISO(date).setLocale("id").toFormat("dd LLL yyyy");
};

const formatTime = (date) => {
  if (!date) return "-";
  return DateTime.fromISO(date).toFormat("HH:mm");
};

const formatStaff = (ticket) => {
  if (!ticket.assigned_staff?.length) return "Belum ditugaskan";
  return ticket.assigned_staff.map((staff) => staff.name).join(", ");
};

const stripHtml = (html) => {
  const element = document.createElement("div");
  element.innerHTML = html || "";
  return element.textContent?.trim() || "";
};

watch(
  filters,
  debounce(() => {
    handleFilterChange();
  }, 300),
  { deep: true }
);

watch(
  [filters, pagination],
  () => {
    saveState(filters.value, pagination.value);
  },
  { deep: true }
);

onMounted(async () => {
  const savedState = loadState();
  if (savedState.hasStoredState) {
    filters.value = { ...defaultFilters, ...savedState.filters };
    pagination.value = { ...defaultPagination, ...savedState.pagination };
  }

  await Promise.all([
    categoryStore.fetchCategories({ is_active: true, row_per_page: "all" }),
    fetchTickets(),
  ]);
});
</script>

<template>
  <div class="mx-auto max-w-[1280px] space-y-5">
    <Alert
      v-if="success"
      type="success"
      :message="success"
      :auto-close="true"
      :duration="3000"
      @close="success = null"
    />
    <Alert
      v-if="error"
      type="error"
      :message="error"
      :auto-close="true"
      :duration="5000"
      @close="error = null"
    />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600 shadow-sm">
          <TicketIcon :size="24" />
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-normal text-slate-950">
            Tiket Saya
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            Pantau daftar tiket dan status penanganannya.
          </p>
        </div>
      </div>

      <RouterLink :to="{ name: 'app.ticket.create' }" custom v-slot="{ navigate }">
        <Button type="button" @click="navigate">
          <Plus :size="17" />
          Buat Tiket
        </Button>
      </RouterLink>
    </div>

    <Card>
      <CardContent class="p-4">
        <div class="flex items-center justify-between gap-3">
          <h2 class="flex items-center gap-2 text-base font-semibold text-slate-950">
            <Filter :size="19" />
            Filter Tiket
          </h2>
          <Button
            type="button"
            @click="clearFilters"
            variant="outline"
            size="sm"
            class="h-8 text-xs"
          >
            <RotateCcw :size="14" />
            Reset
          </Button>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
          <label class="block xl:col-span-2">
            <span class="mb-1.5 block text-xs font-medium text-slate-700">
              Cari Tiket
            </span>
            <span class="relative block">
              <Search
                :size="16"
                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <Input
                v-model="filters.search"
                type="search"
                placeholder="Cari kode atau deskripsi tiket"
                class="pl-9"
              />
            </span>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-slate-700">
              Status
            </span>
            <Select v-model="filters.status">
              <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </Select>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-slate-700">
              Prioritas
            </span>
            <Select v-model="filters.priority">
              <option
                v-for="option in priorityOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </Select>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-slate-700">
              Kategori
            </span>
            <Select v-model="filters.categoryId">
              <option
                v-for="option in categoryOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </Select>
          </label>

          <label class="block">
            <span class="mb-1.5 block text-xs font-medium text-slate-700">
              Periode
            </span>
            <Select v-model="filters.date">
              <option
                v-for="option in dateOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </Select>
          </label>
        </div>
      </CardContent>
    </Card>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-slate-600">
        <span v-if="meta.total > 0">
          Menampilkan {{ tickets.length }} dari {{ meta.total }} tiket
        </span>
        <span v-else>Tidak ada tiket ditemukan</span>
      </p>

      <div class="flex shrink-0 items-center gap-2 text-sm text-slate-600">
        <span>Tampilkan:</span>
        <Select
          :model-value="pagination.per_page"
          @change="handlePerPageChange(parseInt($event.target.value))"
          class="h-9 w-20"
        >
          <option value="5">5</option>
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
        </Select>
        <span>per halaman</span>
      </div>
    </div>

    <div v-if="loading" class="flex min-h-[220px] items-center justify-center">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-100 border-t-blue-600"></div>
    </div>

    <Card v-else-if="tickets.length > 0" class="overflow-hidden">
      <div class="overflow-x-auto">
        <Table>
          <TableHeader class="bg-slate-50">
            <TableRow class="hover:bg-slate-50">
              <TableHead class="min-w-[150px]">Kode</TableHead>
              <TableHead class="min-w-[220px]">Kategori</TableHead>
              <TableHead class="min-w-[160px]">Cabang</TableHead>
              <TableHead class="min-w-[180px]">Staff Assigned</TableHead>
              <TableHead class="min-w-[130px]">Status</TableHead>
              <TableHead class="min-w-[130px]">Prioritas</TableHead>
              <TableHead class="min-w-[150px]">Dibuat</TableHead>
              <TableHead class="w-14"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="ticket in tickets" :key="ticket.id">
              <TableCell>
                <RouterLink
                  :to="{ name: 'app.ticket.detail', params: { code: ticket.code } }"
                  class="font-semibold text-blue-700 hover:text-blue-800"
                >
                  {{ ticket.code }}
                </RouterLink>
              </TableCell>
              <TableCell>
                <p class="font-medium text-slate-950">{{ ticketTitle(ticket) }}</p>
                <p class="line-clamp-1 text-xs text-slate-500">
                  {{ stripHtml(ticket.description) || "-" }}
                </p>
              </TableCell>
              <TableCell class="text-sm text-slate-700">
                {{ ticket.branch?.name || "-" }}
              </TableCell>
              <TableCell class="text-sm text-slate-700">
                {{ formatStaff(ticket) }}
              </TableCell>
              <TableCell>
                <Badge :variant="statusBadgeVariant(ticket.status)">
                  {{ statusLabel(ticket.status) }}
                </Badge>
              </TableCell>
              <TableCell>
                <Badge :variant="priorityBadgeVariant(ticket.priority)">
                  {{ priorityLabel(ticket.priority) }}
                </Badge>
              </TableCell>
              <TableCell class="text-sm text-slate-700">
                <span class="block">{{ formatDate(ticket.created_at) }}</span>
                <span class="block text-xs text-slate-500">{{ formatTime(ticket.created_at) }}</span>
              </TableCell>
              <TableCell class="text-right">
                <RouterLink
                  :to="{ name: 'app.ticket.detail', params: { code: ticket.code } }"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 hover:text-blue-600"
                  aria-label="Buka detail tiket"
                >
                  <ChevronRight :size="18" />
                </RouterLink>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <Pagination :meta="meta" @page-change="handlePageChange" />
    </Card>

    <div v-else class="rounded-lg border border-transparent py-10 text-center">
      <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-blue-500">
        <Inbox :size="42" />
      </div>
      <h3 class="mt-3 text-lg font-bold text-slate-950">Tidak ada tiket</h3>
      <p class="mt-1 text-sm text-slate-600">
        Belum ada tiket yang dibuat atau sesuai dengan filter yang dipilih.
      </p>
      <RouterLink :to="{ name: 'app.ticket.create' }" custom v-slot="{ navigate }">
        <Button type="button" class="mt-3" @click="navigate">
          <Plus :size="17" />
          Buat Tiket
        </Button>
      </RouterLink>
    </div>
  </div>
</template>
