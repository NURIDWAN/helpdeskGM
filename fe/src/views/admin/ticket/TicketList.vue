<script setup>
import { ref, onMounted, computed } from "vue";
import { useTicketStore } from "@/stores/ticket";
import { useBranchStore } from "@/stores/branch";
import { useUserStore } from "@/stores/user";
import { useTicketCategoryStore } from "@/stores/ticketCategory";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import MultiSelect from "@/components/common/MultiSelect.vue";
import {
  Plus,
  Edit,
  Trash2,
  Ticket as TicketIcon,
  Eye,
  Filter,
  X,
  FileText,
  FileSpreadsheet,
  Download,
  CheckCircle2,
  Tag,
  XCircle,
  User,
  Users,
  Minus,
} from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can, hasRole } from "@/helpers/permissionHelper";
import { useToast } from "vue-toastification";

const toast = useToast();

const ticketStore = useTicketStore();
const branchStore = useBranchStore();
const userStore = useUserStore();
const categoryStore = useTicketCategoryStore();

const { tickets, meta, loading, success, error } = storeToRefs(ticketStore);
const { fetchTicketsPaginated, deleteTicket, updateTicket } = ticketStore;
const { fetchBranches } = branchStore;
const { fetchUsers } = userStore;

// Filter options
const statusOptions = [
  { value: "open", label: "Open" },
  { value: "in_progress", label: "In Progress" },
  { value: "resolved", label: "Resolved" },
  { value: "closed", label: "Closed" },
];

const priorityOptions = [
  { value: "low", label: "Low" },
  { value: "medium", label: "Medium" },
  { value: "high", label: "High" },
  { value: "urgent", label: "Urgent" },
];

const durationOptions = [
  { value: "green", label: "< 2 Hari (Hijau)" },
  { value: "yellow", label: "2-7 Hari (Kuning)" },
  { value: "red", label: "> 7 Hari (Merah)" },
];

// Reactive data
const searchQuery = ref("");
const showDeleteModal = ref(false);
const ticketToDelete = ref(null);
const showFilters = ref(false);
const showExportMenu = ref(false);

// Filter state
const filters = ref({
  status: null,
  priority: null,
  branchId: null,
  categoryId: null,
  startDate: null,
  endDate: null,
  duration: null,
});

// Computed properties
const branchOptions = computed(() =>
  branchStore.branches.map((branch) => ({
    value: branch.id,
    label: branch.name,
  }))
);

const userOptions = computed(() =>
  userStore.users.map((user) => ({
    value: user.id,
    label: user.name,
  }))
);

const categoryOptions = computed(() =>
  categoryStore.categories.map((cat) => ({
    value: cat.id,
    label: cat.name,
    color: cat.color,
  }))
);

// Default date range (last month)
const defaultStartDate = computed(() => {
  const date = new Date();
  date.setMonth(date.getMonth() - 1);
  return date.toISOString().split("T")[0];
});

const defaultEndDate = computed(() => {
  return new Date().toISOString().split("T")[0];
});

// Table columns configuration
const tableColumns = [
  { key: "code", label: "Kode", bold: true, nowrap: true },
  { key: "category", label: "Kategori", nowrap: true },
  { key: "duration", label: "Durasi", nowrap: true },
  { key: "user.name", label: "Pelapor", nowrap: true },
  { key: "branch.name", label: "Cabang", nowrap: true },
  { key: "assigned_staff", label: "Staff Assigned", nowrap: true },
  { key: "status", label: "Status", nowrap: true },
  { key: "priority", label: "Prioritas", nowrap: true },
  { key: "notif_status", label: "Notifikasi", nowrap: true },
  { key: "created_at", label: "Dibuat", nowrap: true },
];

// Methods
const fetchTickets = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
    status: filters.value.status,
    priority: filters.value.priority,
    branch_id: filters.value.branchId,
    category_id: filters.value.categoryId,
    start_date: filters.value.startDate,
    end_date: filters.value.endDate,
  };

  // Remove null/undefined values
  Object.keys(params).forEach((key) => {
    if (
      params[key] === null ||
      params[key] === undefined ||
      params[key] === ""
    ) {
      delete params[key];
    }
  });

  fetchTicketsPaginated(params);
};

const handleSearch = () => {
  meta.value.current_page = 1;
  fetchTickets();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchTickets();
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchTickets();
};

const handleFilterChange = () => {
  meta.value.current_page = 1;
  fetchTickets();
};

const clearFilters = () => {
  filters.value = {
    status: null,
    priority: null,
    branchId: null,
    categoryId: null,
    startDate: null,
    endDate: null,
    duration: null,
  };
  meta.value.current_page = 1;
  fetchTickets();
};

// Helper to get duration category
const getDurationCategory = (ticket) => {
  if (!ticket || !ticket.created_at) return null;
  const start = new Date(ticket.created_at);
  const end = ticket.status === "closed" && ticket.completed_at
    ? new Date(ticket.completed_at)
    : new Date();
  const diffDays = Math.abs(end - start) / (1000 * 60 * 60 * 24);
  if (diffDays < 2) return "green";
  if (diffDays < 7) return "yellow";
  return "red";
};

// Computed filtered tickets (client-side duration filter)
const filteredTickets = computed(() => {
  if (!filters.value.duration) return tickets.value;
  return tickets.value.filter(ticket => getDurationCategory(ticket) === filters.value.duration);
});

const loadFilterData = async () => {
  try {
    await Promise.all([
      fetchBranches({ limit: 100 }),
      fetchUsers({ limit: 100 }),
      categoryStore.fetchCategories({ is_active: true, row_per_page: 'all' }),
    ]);
  } catch (error) {
    console.error("Error loading filter data:", error);
  }
};

const confirmDelete = (ticket) => {
  ticketToDelete.value = ticket;
  showDeleteModal.value = true;
};

const handleDeleteTicket = async () => {
  if (ticketToDelete.value) {
    await deleteTicket(ticketToDelete.value.id);
    if (!error.value) {
      showDeleteModal.value = false;
      ticketToDelete.value = null;
      fetchTickets();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  ticketToDelete.value = null;
};

// Update Ticket Status (Staff Workflow)
const updatingId = ref(null);
const handleUpdateStatus = async (ticket, newStatus) => {
  updatingId.value = ticket.id;
  try {
    await updateTicket(ticket.id, { status: newStatus });
    toast.success(`Status ticket berhasil diperbarui ke ${newStatus.replace('_', ' ').toUpperCase()}`);
    fetchTickets();
  } catch (e) {
    toast.error('Gagal memperbarui status ticket');
  } finally {
    updatingId.value = null;
  }
};

// Lifecycle
onMounted(async () => {
  // Set default date range
  filters.value.startDate = defaultStartDate.value;
  filters.value.endDate = defaultEndDate.value;

  // Load filter data and tickets
  await loadFilterData();
  fetchTickets();
});
const getDurationBadge = (ticket) => {
  if (!ticket || !ticket.created_at)
    return { color: "bg-gray-100 text-gray-800", label: "-" };
  const start = new Date(ticket.created_at);
  const end =
    ticket.status === "closed" && ticket.completed_at
      ? new Date(ticket.completed_at)
      : new Date();
  const diffTime = Math.abs(end - start);
  const diffDays = diffTime / (1000 * 60 * 60 * 24);

  if (diffDays < 2)
    return { color: "bg-green-100 text-green-800", label: "< 2 Hari" };
  if (diffDays < 7)
    return {
      color: "bg-yellow-100 text-yellow-800",
      label: Math.floor(diffDays) + " Hari",
    };
  return { color: "bg-red-100 text-red-800", label: "> 7 Hari" };
};
// Export methods
const buildExportParams = () => {
  const params = {};
  
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.priority) params.priority = filters.value.priority;
  if (filters.value.branchId) params.branch_id = filters.value.branchId;
  if (filters.value.categoryId) params.category_id = filters.value.categoryId;
  if (filters.value.startDate) params.start_date = filters.value.startDate;
  if (filters.value.endDate) params.end_date = filters.value.endDate;
  if (filters.value.duration) params.duration = filters.value.duration;
  if (searchQuery.value) params.search = searchQuery.value;
  
  return params;
};

const exportLoading = ref(false);

const handleExportPdf = async () => {
  showExportMenu.value = false;
  exportLoading.value = true;
  
  try {
    const { axiosInstance } = await import('@/plugins/axios');
    const response = await axiosInstance.get('/tickets/export/pdf', {
      params: buildExportParams(),
      responseType: 'blob',
    });
    
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `tickets_${new Date().toISOString().slice(0, 10)}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Export PDF error:', err);
    ticketStore.error = 'Gagal export PDF';
  } finally {
    exportLoading.value = false;
  }
};

const handleExportExcel = async () => {
  showExportMenu.value = false;
  exportLoading.value = true;
  
  try {
    const { axiosInstance } = await import('@/plugins/axios');
    const response = await axiosInstance.get('/tickets/export/excel', {
      params: buildExportParams(),
      responseType: 'blob',
    });
    
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `tickets_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    console.error('Export Excel error:', err);
    ticketStore.error = 'Gagal export Excel';
  } finally {
    exportLoading.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Ticket</h1>
        <p class="text-gray-600">Kelola data ticket</p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Export Buttons -->
        <div class="relative" v-if="can('ticket-list')">
          <button
            @click="showExportMenu = !showExportMenu"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <Download :size="18" class="mr-2" />
            Export
          </button>
          <div
            v-if="showExportMenu"
            class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border z-10"
          >
            <button
              @click="handleExportPdf"
              class="w-full flex items-center px-4 py-2 text-left hover:bg-gray-50 text-gray-700"
            >
              <FileText :size="18" class="mr-2 text-red-500" />
              Export PDF
            </button>
            <button
              @click="handleExportExcel"
              class="w-full flex items-center px-4 py-2 text-left hover:bg-gray-50 text-gray-700"
            >
              <FileSpreadsheet :size="18" class="mr-2 text-green-500" />
              Export Excel
            </button>
          </div>
        </div>
        <RouterLink
          v-if="can('ticket-create')"
          :to="{ name: 'admin.ticket.create' }"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <Plus :size="20" class="mr-2" />
          Buat Ticket
        </RouterLink>
      </div>
    </div>

    <!-- Alert -->
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

    <!-- Search and Filter -->
    <div class="bg-white p-4 rounded-lg shadow">
      <div class="flex gap-4 items-center">
        <div class="flex-1">
          <SearchInput
            v-model="searchQuery"
            placeholder="Cari ticket..."
            :debounce="500"
            @update:modelValue="handleSearch"
          />
        </div>
        <button
          @click="showFilters = !showFilters"
          class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors"
          :class="{ 'bg-blue-50 text-blue-600': showFilters }"
        >
          <Filter :size="20" />
          Filter
        </button>
      </div>

      <!-- Advanced Filters -->
      <div v-show="showFilters" class="mt-4 pt-4 border-t border-gray-200">
        <div
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4"
        >
          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Status</label
            >
            <select
              v-model="filters.status"
              @change="handleFilterChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Status</option>
              <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Priority Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Prioritas</label
            >
            <select
              v-model="filters.priority"
              @change="handleFilterChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Prioritas</option>
              <option
                v-for="option in priorityOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Branch Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Cabang</label
            >
            <select
              v-model="filters.branchId"
              @change="handleFilterChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Cabang</option>
              <option
                v-for="option in branchOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Category Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Kategori</label
            >
            <select
              v-model="filters.categoryId"
              @change="handleFilterChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Kategori</option>
              <option
                v-for="option in categoryOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Start Date Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Tanggal Mulai</label
            >
            <input
              v-model="filters.startDate"
              @change="handleFilterChange"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- End Date Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Tanggal Akhir</label
            >
            <input
              v-model="filters.endDate"
              @change="handleFilterChange"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- Duration Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Durasi</label
            >
            <select
              v-model="filters.duration"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Durasi</option>
              <option
                v-for="option in durationOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="mt-4 flex justify-end gap-2">
          <button
            @click="clearFilters"
            class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors"
          >
            <X :size="16" />
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :items="filteredTickets"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data ticket"
      :empty-icon="TicketIcon"
      storage-key="ticket-list-table"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-created_at="{ value }">
        <div class="text-sm text-gray-500">
          {{ formatToClientTimezone(value) }}
        </div>
      </template>

      <template #cell-duration="{ item }">
        <span
          :class="getDurationBadge(item).color"
          class="px-3 py-1 rounded-full text-xs font-medium"
        >
          {{ getDurationBadge(item).label }}
        </span>
      </template>

      <template #cell-status="{ value }">
        <span 
          class="px-2 py-0.5 text-xs rounded font-medium"
          :class="{
            'bg-gray-100 text-gray-800': value === 'open',
            'bg-yellow-100 text-yellow-800': value === 'pending',
            'bg-blue-100 text-blue-800': value === 'in_progress',
            'bg-purple-100 text-purple-800': value === 'resolved',
            'bg-green-100 text-green-800': value === 'closed'
          }"
        >
          {{ value ? value.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-' }}
        </span>
      </template>

      <template #cell-category="{ item }">
        <div v-if="item.category" class="flex items-center gap-1">
          <Tag :size="14" :style="{ color: item.category.color || '#6B7280' }" />
          <span class="text-sm">{{ item.category.name }}</span>
        </div>
        <span v-else class="text-gray-400 text-sm">-</span>
      </template>

      <template #cell-priority="{ value }">
        <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">{{
          value
        }}</span>
      </template>

      <template #cell-assigned_staff="{ value }">
        <div v-if="value && value.length > 0" class="flex flex-wrap gap-1">
          <span
            v-for="staff in value"
            :key="staff.id"
            class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800"
          >
            {{ staff.name }}
          </span>
        </div>
        <span v-else class="text-gray-400 text-sm">Belum di-assign</span>
      </template>

      <template #cell-notif_status="{ item }">
        <div class="flex items-center gap-2">
          <!-- Staff Notification -->
          <div class="flex items-center gap-1" :title="item.notif_staff_sent === true ? 'Staff: Terkirim' : item.notif_staff_sent === false ? 'Staff: Gagal' : 'Staff: -'">
            <User :size="14" class="text-gray-500" />
            <CheckCircle2 v-if="item.notif_staff_sent === true" :size="14" class="text-green-500" />
            <XCircle v-else-if="item.notif_staff_sent === false" :size="14" class="text-red-500" />
            <Minus v-else :size="14" class="text-gray-400" />
          </div>
          <!-- Group Notification -->
          <div class="flex items-center gap-1" :title="item.notif_group_sent === true ? 'Grup: Terkirim' : item.notif_group_sent === false ? 'Grup: Gagal' : 'Grup: -'">
            <Users :size="14" class="text-gray-500" />
            <CheckCircle2 v-if="item.notif_group_sent === true" :size="14" class="text-green-500" />
            <XCircle v-else-if="item.notif_group_sent === false" :size="14" class="text-red-500" />
            <Minus v-else :size="14" class="text-gray-400" />
          </div>
        </div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <!-- Staff Workflow Buttons -->
          <button
            v-if="item.status === 'open' && can('ticket-update-status') && hasRole('staff')"
            @click="handleUpdateStatus(item, 'in_progress')"
            :disabled="updatingId === item.id"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
            title="Konfirmasi & Proses"
          >
            <CheckCircle2 :size="14" />
            Konfirmasi
          </button>

          <button
            v-if="item.status === 'in_progress' && can('ticket-update-status') && hasRole('staff')"
            @click="handleUpdateStatus(item, 'resolved')"
            :disabled="updatingId === item.id"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50"
            title="Tandai Resolved"
          >
            <CheckCircle2 :size="14" />
            Resolve
          </button>

          <RouterLink
            v-if="can('ticket-list')"
            :to="{ name: 'admin.ticket.detail', params: { id: item.id } }"
            class="text-green-600 hover:text-green-900 p-1"
            title="View Detail"
          >
            <Eye :size="16" />
          </RouterLink>
          <RouterLink
            v-if="can('ticket-edit')"
            :to="{ name: 'admin.ticket.edit', params: { id: item.id } }"
            class="text-blue-600 hover:text-blue-900 p-1"
            title="Edit"
          >
            <Edit :size="16" />
          </RouterLink>
          <button
            v-if="can('ticket-delete')"
            @click="confirmDelete(item)"
            class="text-red-600 hover:text-red-900 p-1"
            title="Delete"
          >
            <Trash2 :size="16" />
          </button>
        </div>
      </template>
    </DataTable>

    <ConfirmationModal
      :show="showDeleteModal"
      title="Konfirmasi Hapus"
      :message="`Apakah Anda yakin ingin menghapus ticket **${ticketToDelete?.code}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteTicket"
    />
  </div>
</template>
