<script setup>
import { onMounted, ref, watch, computed } from "vue";
import { useTicketStore } from "@/stores/ticket";
import { storeToRefs } from "pinia";
import { debounce } from "lodash";
import { useRouter } from "vue-router";
import Pagination from "@/components/common/Pagination.vue";
import Alert from "@/components/common/Alert.vue";
import {
  Plus,
  CheckCircle,
  X,
  Search,
  MessageSquare,
  Clock,
  ChevronRight,
  Filter,
  Calendar,
  User,
  Building,
  Ticket as TicketIcon,
  FileText,
  BarChart3,
} from "lucide-vue-next";
import { DateTime } from "luxon";

const ticketStore = useTicketStore();
const { tickets, meta, loading, success, error } = storeToRefs(ticketStore);
const { fetchTicketsPaginated } = ticketStore;

// Filter state
const filters = ref({
  search: "",
  status: "",
  priority: "",
  date: "",
});

// Pagination state
const pagination = ref({
  current_page: 1,
  per_page: 10,
});

// Computed properties
const statusOptions = [
  { value: "", label: "Semua Status" },
  { value: "open", label: "Open" },
  { value: "in_progress", label: "In Progress" },
  { value: "resolved", label: "Resolved" },
  { value: "closed", label: "Closed" },
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

// Methods
const fetchTickets = async () => {
  const params = {
    search: filters.value.search,
    status: filters.value.status,
    priority: filters.value.priority,
    row_per_page: pagination.value.per_page,
    page: pagination.value.current_page,
  };

  // Add date filter logic
  if (filters.value.date) {
    const now = DateTime.now();
    switch (filters.value.date) {
      case "today":
        params.start_date = now.toISODate();
        params.end_date = now.toISODate();
        break;
      case "week":
        params.start_date = now.startOf("week").toISODate();
        params.end_date = now.endOf("week").toISODate();
        break;
      case "month":
        params.start_date = now.startOf("month").toISODate();
        params.end_date = now.endOf("month").toISODate();
        break;
    }
  }

  // Remove empty values
  Object.keys(params).forEach((key) => {
    if (
      params[key] === "" ||
      params[key] === null ||
      params[key] === undefined
    ) {
      delete params[key];
    }
  });

  await fetchTicketsPaginated(params);
};

const handleSearch = () => {
  pagination.value.current_page = 1;
  fetchTickets();
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
  filters.value = {
    search: "",
    status: "",
    priority: "",
    date: "",
  };
  pagination.value.current_page = 1;
  fetchTickets();
};

// Watch for filter changes with debounce
watch(
  filters,
  debounce(() => {
    handleFilterChange();
  }, 300),
  { deep: true }
);

// Lifecycle
onMounted(async () => {
  await fetchTickets();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-900 flex items-center">
          <TicketIcon :size="32" class="mr-3 text-blue-600" />
          Tiket Saya
        </h1>
        <p class="text-gray-600 mt-2">Kelola dan pantau status tiket Anda</p>
      </div>
      <RouterLink
        :to="{ name: 'app.ticket.create' }"
        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
      >
        <Plus :size="20" class="mr-2" />
        Buat Tiket Baru
      </RouterLink>
    </div>

    <!-- Alerts -->
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

    <!-- Quick Links / Menu Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <!-- Laporan Harian Cabang Card -->
      <RouterLink
        :to="{ name: 'app.daily-records' }"
        class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-blue-300 transition-all duration-200 p-6 group"
      >
        <div class="flex items-center gap-4">
          <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
            <FileText :size="24" class="text-blue-600" />
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
              Laporan Harian Cabang
            </h3>
            <p class="text-sm text-gray-500 mt-1">
              Kelola laporan harian cabang
            </p>
          </div>
          <ChevronRight :size="20" class="text-gray-400 group-hover:text-blue-600 transition-colors" />
        </div>
      </RouterLink>

      <!-- Laporan Daily Usage Card -->
      <RouterLink
        :to="{ name: 'app.daily-usage-report' }"
        class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-green-300 transition-all duration-200 p-6 group"
      >
        <div class="flex items-center gap-4">
          <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
            <BarChart3 :size="24" class="text-green-600" />
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-green-600 transition-colors">
              Laporan Daily Usage
            </h3>
            <p class="text-sm text-gray-500 mt-1">
              Laporan penggunaan utilitas harian
            </p>
          </div>
          <ChevronRight :size="20" class="text-gray-400 group-hover:text-green-600 transition-colors" />
        </div>
      </RouterLink>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <Filter :size="20" class="mr-2" />
            Filter Tiket
          </h3>
          <button
            @click="clearFilters"
            class="text-sm text-gray-500 hover:text-gray-700 flex items-center"
          >
            <X :size="16" class="mr-1" />
            Reset Filter
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Cari Tiket
            </label>
            <div class="relative">
              <input
                type="text"
                placeholder="Cari berdasarkan judul atau kode..."
                v-model="filters.search"
                @input="handleSearch"
                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <Search
                :size="18"
                class="text-gray-400 absolute left-3 top-3.5"
              />
            </div>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Status
            </label>
            <select
              v-model="filters.status"
              @change="handleFilterChange"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Priority -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Prioritas
            </label>
            <select
              v-model="filters.priority"
              @change="handleFilterChange"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option
                v-for="option in priorityOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Date -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Periode
            </label>
            <select
              v-model="filters.date"
              @change="handleFilterChange"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option
                v-for="option in dateOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Summary -->
    <div class="flex items-center justify-between">
      <div class="text-sm text-gray-600">
        <span v-if="meta.total > 0">
          Menampilkan {{ tickets.length }} dari {{ meta.total }} tiket
        </span>
        <span v-else> Tidak ada tiket ditemukan </span>
      </div>

      <!-- Per Page Selector -->
      <div class="flex items-center space-x-2">
        <label class="text-sm text-gray-600">Tampilkan:</label>
        <select
          :value="pagination.per_page"
          @change="handlePerPageChange(parseInt($event.target.value))"
          class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="5">5</option>
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
        </select>
        <span class="text-sm text-gray-600">per halaman</span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
      ></div>
    </div>

    <!-- Tickets List -->
    <div v-else-if="tickets.length > 0" class="space-y-4">
      <div
        class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 hover:border-blue-300"
        v-for="ticket in tickets"
        :key="ticket.id"
      >
        <RouterLink
          :to="{ name: 'app.ticket.detail', params: { code: ticket.code } }"
          class="block p-6"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <!-- Title and Status -->
              <div class="flex items-start gap-3 mb-3">
                <h3 class="text-lg font-semibold text-gray-900 truncate">
                  {{ ticket.title }}
                </h3>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <!-- Status Badge -->
                  <span
                    class="px-3 py-1 text-xs font-medium rounded-full"
                    :class="{
                      'text-blue-700 bg-blue-100': ticket.status === 'open',
                      'text-yellow-700 bg-yellow-100':
                        ticket.status === 'in_progress',
                      'text-green-700 bg-green-100':
                        ticket.status === 'resolved',
                      'text-red-700 bg-red-100': ticket.status === 'closed',
                    }"
                  >
                    {{
                      ticket.status
                        ?.replace(/_/g, " ")
                        .replace(/\b\w/g, (l) => l.toUpperCase()) || "-"
                    }}
                  </span>

                  <!-- Priority Badge -->
                  <span
                    class="px-3 py-1 text-xs font-medium rounded-full"
                    :class="{
                      'text-red-700 bg-red-100': ticket.priority === 'urgent',
                      'text-orange-700 bg-orange-100':
                        ticket.priority === 'high',
                      'text-yellow-700 bg-yellow-100':
                        ticket.priority === 'medium',
                      'text-green-700 bg-green-100': ticket.priority === 'low',
                    }"
                  >
                    {{
                      ticket.priority
                        ?.replace(/_/g, " ")
                        .replace(/\b\w/g, (l) => l.toUpperCase()) || "-"
                    }}
                  </span>
                </div>
              </div>

              <!-- Ticket Info -->
              <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                <div class="flex items-center">
                  <TicketIcon :size="16" class="mr-1" />
                  <span class="font-mono">#{{ ticket.code }}</span>
                </div>
                <div class="flex items-center">
                  <Calendar :size="16" class="mr-1" />
                  <span>{{
                    DateTime.fromISO(ticket.created_at).toFormat(
                      "dd MMM yyyy, HH:mm"
                    )
                  }}</span>
                </div>
                <div v-if="ticket.branch" class="flex items-center">
                  <Building :size="16" class="mr-1" />
                  <span>{{ ticket.branch.name }}</span>
                </div>
              </div>

              <!-- Description -->
              <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                {{ ticket.description }}
              </p>

              <!-- Footer Info -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <div class="flex items-center">
                    <MessageSquare :size="16" class="mr-1" />
                    <span>{{ ticket.replies_count || 0 }} balasan</span>
                  </div>
                  <div class="flex items-center">
                    <Clock :size="16" class="mr-1" />
                    <span
                      >Terakhir diupdate
                      {{
                        DateTime.fromISO(ticket.updated_at).toFormat(
                          "dd MMM yyyy"
                        )
                      }}</span
                    >
                  </div>
                </div>
                <ChevronRight :size="20" class="text-gray-400" />
              </div>
            </div>
          </div>
        </RouterLink>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <TicketIcon :size="64" class="mx-auto text-gray-300 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada tiket</h3>
      <p class="text-gray-500 mb-6">
        Belum ada tiket yang dibuat atau sesuai dengan filter yang dipilih.
      </p>
      <RouterLink
        :to="{ name: 'app.ticket.create' }"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Plus :size="20" class="mr-2" />
        Buat Tiket Pertama
      </RouterLink>
    </div>

    <!-- Pagination -->
    <Pagination
      v-if="meta.last_page > 1"
      :meta="meta"
      @page-change="handlePageChange"
    />
  </div>
</template>
