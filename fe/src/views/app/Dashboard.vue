<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useTicketStore } from "@/stores/ticket";
import { storeToRefs } from "pinia";
import { debounce } from "lodash";
import { useFilterStorage } from "@/composables/useFilterStorage";
import { axiosInstance } from "@/plugins/axios";
import Pagination from "@/components/common/Pagination.vue";
import Alert from "@/components/common/Alert.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Progress } from "@/components/ui/progress";
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
  ArrowUp,
  BarChart3,
  BookOpen,
  CheckCircle,
  ChevronDown,
  ChevronRight,
  Clock3,
  FileText,
  Filter,
  Inbox,
  Megaphone,
  Monitor,
  Plus,
  RefreshCw,
  RotateCcw,
  Search,
  Ticket as TicketIcon,
  User,
  Wifi,
} from "lucide-vue-next";
import { DateTime } from "luxon";

const ticketStore = useTicketStore();
const { tickets, meta, loading, success, error } = storeToRefs(ticketStore);
const { fetchTicketsPaginated } = ticketStore;
const statsTickets = ref([]);
const statsMeta = ref({
  total: 0,
});

const defaultFilters = {
  search: "",
  status: "",
  priority: "",
  date: "",
};

const defaultPagination = {
  current_page: 1,
  per_page: 10,
};

const { saveState, loadState, clearState } = useFilterStorage(
  "ticket_filter_app_dashboard_v2",
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

const ticketIconPool = [Monitor, FileText, Wifi, User];

const totalTickets = computed(
  () => statsMeta.value?.total || statsTickets.value.length || 0
);

const statusCounts = computed(() => {
  const counts = {
    open: 0,
    in_progress: 0,
    resolved: 0,
    closed: 0,
  };

  statsTickets.value.forEach((ticket) => {
    if (Object.prototype.hasOwnProperty.call(counts, ticket.status)) {
      counts[ticket.status] += 1;
    }
  });

  return counts;
});

const doneCount = computed(
  () => statusCounts.value.resolved + statusCounts.value.closed
);

const metricCards = computed(() => [
  {
    label: "Total Tiket Saya",
    value: totalTickets.value,
    helper: "Semua waktu",
    icon: FileText,
    color: "blue",
  },
  {
    label: "Menunggu",
    value: statusCounts.value.open,
    helper: "Perlu ditindaklanjuti",
    icon: Clock3,
    color: "amber",
  },
  {
    label: "Diproses",
    value: statusCounts.value.in_progress,
    helper: "Sedang dikerjakan",
    icon: RefreshCw,
    color: "blue",
  },
  {
    label: "Selesai",
    value: doneCount.value,
    helper: "Selesai",
    icon: CheckCircle,
    color: "green",
  },
]);

const quickActions = [
  {
    to: { name: "app.ticket.create" },
    label: "Buat Tiket Baru",
    description: "Laporkan masalah baru",
    icon: Plus,
  },
  {
    to: { name: "app.tickets" },
    label: "Cek Status Tiket",
    description: "Lihat status tiket Anda",
    icon: Search,
  },
  {
    to: { name: "app.dashboard" },
    label: "Knowledge Base",
    description: "Cari solusi mandiri",
    icon: BookOpen,
  },
  {
    to: { name: "app.dashboard" },
    label: "Pengumuman",
    description: "Info terbaru",
    icon: Megaphone,
  },
];

const percentageOf = (value) => {
  if (!totalTickets.value) return 0;
  return Math.round((value / totalTickets.value) * 100);
};

const donutStyle = computed(() => {
  const waiting = percentageOf(statusCounts.value.open);
  const progress = percentageOf(statusCounts.value.in_progress);
  const done = percentageOf(doneCount.value);

  return {
    background: `conic-gradient(#f59e0b 0 ${waiting}%, #3b82f6 ${waiting}% ${
      waiting + progress
    }%, #22c55e ${waiting + progress}% ${
      waiting + progress + done
    }%, #e2e8f0 ${waiting + progress + done}% 100%)`,
  };
});

const slaPercent = computed(() => {
  if (!totalTickets.value) return 0;
  return Math.min(100, Math.round((doneCount.value / totalTickets.value) * 100));
});

const fetchTicketStats = async () => {
  try {
    const response = await axiosInstance.get("/tickets");

    statsTickets.value = response.data.data || [];
    statsMeta.value = { total: statsTickets.value.length };
  } catch (err) {
    console.error("Gagal memuat statistik tiket", err);
    statsTickets.value = [];
    statsMeta.value = { total: 0 };
  }
};

const fetchTickets = async () => {
  const params = {
    search: filters.value.search,
    status: filters.value.status,
    priority: filters.value.priority,
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

const handleCloseTicket = async (ticketId) => {
  if (
    confirm(
      "Apakah Anda yakin ingin menutup tiket ini? Tindakan ini tidak dapat dibatalkan."
    )
  ) {
    const result = await ticketStore.closeTicket(ticketId);
    if (result) {
      await Promise.all([fetchTickets(), fetchTicketStats()]);
    }
  }
};

const clearFilters = () => {
  filters.value = { ...defaultFilters };
  pagination.value.current_page = 1;
  clearState();
};

const ticketTitle = (ticket) =>
  ticket.title || ticket.subject || ticket.category?.name || "Tiket";

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

const formatRelative = (date) => {
  if (!date) return "-";
  return DateTime.fromISO(date).setLocale("id").toRelative() || "-";
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

  await Promise.all([fetchTickets(), fetchTicketStats()]);
});
</script>

<template>
  <div class="mx-auto max-w-[1280px] space-y-4">
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

    <div class="xl:w-[113.636%] xl:origin-top-left xl:scale-[0.88]">
      <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_280px]">
        <section class="min-w-0 space-y-3">
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
                  Kelola dan pantau status tiket Anda dengan mudah.
                </p>
              </div>
            </div>

            <RouterLink
              :to="{ name: 'app.ticket.create' }"
              custom
              v-slot="{ navigate }"
            >
              <Button type="button" @click="navigate">
                <Plus :size="17" />
                Buat Tiket Baru
              </Button>
            </RouterLink>
          </div>

          <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            <Card
              v-for="card in metricCards"
              :key="card.label"
              class="p-3"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="truncate text-[11px] font-medium text-slate-600">{{ card.label }}</p>
                  <p class="mt-1.5 text-xl font-bold leading-none text-slate-950">
                    {{ card.value }}
                  </p>
                  <p class="mt-2 truncate text-[11px] text-slate-600">{{ card.helper }}</p>
                </div>
                <div
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                  :class="{
                    'bg-blue-50 text-blue-600': card.color === 'blue',
                    'bg-amber-50 text-amber-500': card.color === 'amber',
                    'bg-green-50 text-green-600': card.color === 'green',
                  }"
                >
                  <component :is="card.icon" :size="18" />
                </div>
              </div>
            </Card>
          </div>

          <div class="grid gap-3 lg:grid-cols-2">
            <RouterLink
              :to="{ name: 'app.daily-records' }"
              class="group flex items-center gap-3 rounded-lg border border-blue-100 bg-white p-3.5 shadow-sm transition hover:border-blue-200 hover:shadow-md"
            >
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <FileText :size="23" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-slate-950">
                  Laporan Harian Cabang
                </span>
                <span class="mt-0.5 block text-xs text-slate-600">
                  Kelola laporan harian cabang
                </span>
              </span>
              <ChevronRight :size="18" class="text-slate-500 transition group-hover:text-blue-600" />
            </RouterLink>

            <RouterLink
              :to="{ name: 'app.daily-usage-report' }"
              class="group flex items-center gap-3 rounded-lg border border-green-100 bg-white p-3.5 shadow-sm transition hover:border-green-200 hover:shadow-md"
            >
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                <BarChart3 :size="23" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-slate-950">
                  Laporan Daily Usage
                </span>
                <span class="mt-0.5 block text-xs text-slate-600">
                  Laporan penggunaan utilitas harian
                </span>
              </span>
              <ChevronRight :size="18" class="text-slate-500 transition group-hover:text-green-600" />
            </RouterLink>
          </div>

          <Card>
            <CardContent class="p-3.5">
            <div class="flex items-center justify-between gap-3">
              <CardTitle class="flex items-center gap-2 text-base">
                <Filter :size="19" />
                Filter Tiket
              </CardTitle>
              <Button
                type="button"
                @click="clearFilters"
                variant="outline"
                size="sm"
                class="h-8 text-xs"
              >
                <RotateCcw :size="14" />
                Reset Filter
              </Button>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
              <label class="block">
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
                    placeholder="Cari berdasarkan judul atau kode."
                    class="pl-9"
                  />
                </span>
              </label>

              <label class="block">
                <span class="mb-1.5 block text-xs font-medium text-slate-700">
                  Status
                </span>
                <Select
                  v-model="filters.status"
                >
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
                <Select
                  v-model="filters.priority"
                >
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
                  Periode
                </span>
                <Select
                  v-model="filters.date"
                >
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

          <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600">
              <span v-if="meta.total > 0">
                Menampilkan {{ tickets.length }} dari {{ meta.total }} tiket
              </span>
              <span v-else>Tidak ada tiket ditemukan</span>
            </p>

            <div class="flex shrink-0 items-center gap-2 text-sm text-slate-600">
              <span>Tampilkan:</span>
              <Select
                :value="pagination.per_page"
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

          <div v-if="loading" class="flex min-h-[180px] items-center justify-center">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-blue-100 border-t-blue-600"></div>
          </div>

          <Card v-else-if="tickets.length > 0">
            <div class="hidden overflow-x-auto lg:block">
              <Table class="table-fixed">
                <TableHeader class="bg-slate-50">
                  <TableRow class="hover:bg-slate-50">
                    <TableHead class="w-[34%] px-3">Tiket</TableHead>
                    <TableHead class="w-[15%] px-3">Status</TableHead>
                    <TableHead class="w-[15%] px-3">Prioritas</TableHead>
                    <TableHead class="w-[15%] px-3">Tanggal</TableHead>
                    <TableHead class="w-[18%] px-3">Terakhir Diupdate</TableHead>
                    <TableHead class="w-12 px-3"></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="(ticket, index) in tickets"
                    :key="ticket.id"
                  >
                    <TableCell class="px-3 py-2.5">
                      <RouterLink
                        :to="{ name: 'app.ticket.detail', params: { code: ticket.code } }"
                        class="flex min-w-0 items-center gap-3"
                      >
                        <span
                          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md"
                          :class="[
                            index % 4 === 0 && 'bg-blue-50 text-blue-600',
                            index % 4 === 1 && 'bg-amber-50 text-amber-600',
                            index % 4 === 2 && 'bg-green-50 text-green-600',
                            index % 4 === 3 && 'bg-violet-50 text-violet-600',
                          ]"
                        >
                          <component :is="ticketIconPool[index % ticketIconPool.length]" :size="19" />
                        </span>
                        <span class="min-w-0">
                          <span class="block truncate text-sm font-semibold text-slate-950">
                            {{ ticketTitle(ticket) }}
                          </span>
                          <span class="mt-0.5 block text-xs text-slate-500">
                            {{ ticket.code }} · {{ ticket.branch?.name || "Cabang" }}
                          </span>
                        </span>
                      </RouterLink>
                    </TableCell>
                    <TableCell class="px-3 py-2.5">
                      <Badge :variant="statusBadgeVariant(ticket.status)">
                        {{ statusLabel(ticket.status) }}
                      </Badge>
                    </TableCell>
                    <TableCell class="px-3 py-2.5">
                      <Badge :variant="priorityBadgeVariant(ticket.priority)">
                        {{ priorityLabel(ticket.priority) }}
                      </Badge>
                    </TableCell>
                    <TableCell class="px-3 py-2.5 text-sm text-slate-700">
                      <span class="block">{{ formatDate(ticket.created_at) }}</span>
                      <span class="block text-xs text-slate-500">{{ formatTime(ticket.created_at) }}</span>
                    </TableCell>
                    <TableCell class="px-3 py-2.5">
                      <span class="block truncate text-sm font-semibold text-slate-900">
                        {{ ticket.updated_by?.name || ticket.user?.name || "User" }}
                      </span>
                      <span class="block text-xs text-slate-500">
                        {{ formatRelative(ticket.updated_at) }}
                      </span>
                    </TableCell>
                    <TableCell class="px-3 py-2.5 text-right">
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
          </Card>

          <div v-else class="rounded-lg border border-transparent py-3 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-blue-500">
              <Inbox :size="42" />
            </div>
            <h3 class="mt-3 text-lg font-bold text-slate-950">Tidak ada tiket</h3>
            <p class="mt-1 text-sm text-slate-600">
              Belum ada tiket yang dibuat atau sesuai dengan filter yang dipilih.
            </p>
            <RouterLink
              :to="{ name: 'app.ticket.create' }"
              custom
              v-slot="{ navigate }"
            >
              <Button type="button" class="mt-3" @click="navigate">
                <Plus :size="17" />
                Buat Tiket Pertama
              </Button>
            </RouterLink>
          </div>
        </section>

        <aside class="space-y-3 xl:sticky xl:top-20 xl:self-start">
          <Card>
            <CardContent class="p-3.5">
            <div class="flex items-center justify-between gap-2">
              <CardTitle class="text-sm">Statistik Tiket</CardTitle>
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-8 gap-1 px-2 text-xs"
              >
                7 hari
                <ChevronDown :size="13" />
              </Button>
            </div>

            <div class="mt-4 flex items-center gap-3">
              <div class="relative h-20 w-20 shrink-0 rounded-full" :style="donutStyle">
                <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white">
                  <span class="text-lg font-bold leading-none text-slate-950">
                    {{ totalTickets }}
                  </span>
                  <span class="mt-0.5 text-[10px] text-slate-500">Total</span>
                </div>
              </div>

              <div class="min-w-0 flex-1 space-y-2 text-xs">
                <div>
                  <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                  <span class="ml-1 font-medium text-slate-800">Menunggu</span>
                  <span class="block pl-3 text-slate-600">
                    {{ statusCounts.open }} ({{ percentageOf(statusCounts.open) }}%)
                  </span>
                </div>
                <div>
                  <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                  <span class="ml-1 font-medium text-slate-800">Diproses</span>
                  <span class="block pl-3 text-slate-600">
                    {{ statusCounts.in_progress }} ({{ percentageOf(statusCounts.in_progress) }}%)
                  </span>
                </div>
                <div>
                  <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                  <span class="ml-1 font-medium text-slate-800">Selesai</span>
                  <span class="block pl-3 text-slate-600">
                    {{ doneCount }} ({{ percentageOf(doneCount) }}%)
                  </span>
                </div>
              </div>
            </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent class="p-3.5">
            <div class="flex items-center justify-between gap-2">
              <CardTitle class="text-sm">SLA Compliance</CardTitle>
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-8 gap-1 px-2 text-xs"
              >
                7 hari
                <ChevronDown :size="13" />
              </Button>
            </div>

            <div class="mt-4">
              <div class="flex items-end gap-2">
                <span class="text-2xl font-bold leading-none text-slate-950">
                  {{ slaPercent }}%
                </span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600">
                  <ArrowUp :size="13" />
                  8%
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-600">SLA Terpenuhi</p>
              <Progress class="mt-3" :model-value="slaPercent" />
              <p class="mt-3 text-xs text-slate-500">
                {{ doneCount }} dari {{ totalTickets }} tiket sesuai SLA
              </p>
            </div>
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  </div>
</template>
