<script setup>
import { ref, onMounted, computed } from "vue";
import { useWorkReportStore } from "@/stores/workReport";
import { useBranchStore } from "@/stores/branch";
import { useUserStore } from "@/stores/user";
import { useJobTemplateStore } from "@/stores/jobTemplate";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import {
  Plus,
  Edit,
  Trash2,
  FileText,
  Eye,
  Filter,
  X,
  Download,
  FileSpreadsheet,
} from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can } from "@/helpers/permissionHelper";
import { useAuthStore } from "@/stores/auth";
import { axiosInstance } from "@/plugins/axios";

const workReportStore = useWorkReportStore();
const branchStore = useBranchStore();
const userStore = useUserStore();
const jobTemplateStore = useJobTemplateStore();

const { workReports, meta, loading, error, success } = storeToRefs(workReportStore);
const { fetchWorkReportsPaginated, deleteWorkReport } = workReportStore;
const { fetchBranches } = branchStore;
const { fetchUsers } = userStore;
const { fetchJobTemplates } = jobTemplateStore;

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);

// Table columns configuration
const baseColumns = [
  { key: "user.name", label: "User", nowrap: true },
  { key: "job_info", label: "Detail Pekerjaan", nowrap: false },
  { key: "status", label: "Status", nowrap: true },
  { key: "created_at", label: "Dibuat", nowrap: true },
];

const branchColumn = { key: "branch.name", label: "Cabang", nowrap: true };

const tableColumns = computed(() => {
  if (user.value?.roles?.includes("admin")) {
    return [
      baseColumns[0], // User
      branchColumn, // Branch (admin only)
      ...baseColumns.slice(1), // Rest of columns
    ];
  }
  return baseColumns;
});

// Filter options
const statusOptions = [
  { value: "progress", label: "In Progress" },
  { value: "failed", label: "Failed" },
  { value: "completed", label: "Completed" },
];

// Reactive data
const searchQuery = ref("");
const showDeleteModal = ref(false);
const workReportToDelete = ref(null);
const showFilters = ref(false);

// Filter state
const filters = ref({
  status: null,
  branchId: null,
  userId: null,
  jobTemplateId: null,
  startDate: null,
  endDate: null,
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

const jobTemplateOptions = computed(() =>
  jobTemplateStore.jobTemplates.map((template) => ({
    value: template.id,
    label: template.name,
  }))
);

// Default date range (last week)
const defaultStartDate = computed(() => {
  const date = new Date();
  date.setDate(date.getDate() - 7);
  return date.toISOString().split("T")[0];
});

const defaultEndDate = computed(() => {
  return new Date().toISOString().split("T")[0];
});

// Methods
const fetchWorkReports = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
    status: filters.value.status,
    branch_id: filters.value.branchId,
    user_id: filters.value.userId,
    job_template_id: filters.value.jobTemplateId,
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

  fetchWorkReportsPaginated(params);
};

const handleSearch = () => {
  meta.value.current_page = 1;
  fetchWorkReports();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchWorkReports();
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchWorkReports();
};

const confirmDelete = (workReport) => {
  workReportToDelete.value = workReport;
  showDeleteModal.value = true;
};

const handleDeleteWorkReport = async () => {
  if (workReportToDelete.value) {
    await deleteWorkReport(workReportToDelete.value.id);
    if (!error.value) {
      showDeleteModal.value = false;
      workReportToDelete.value = null;
      fetchWorkReports();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  workReportToDelete.value = null;
};

const handleFilterChange = () => {
  meta.value.current_page = 1;
  fetchWorkReports();
};

const clearFilters = () => {
  filters.value = {
    status: null,
    branchId: null,
    userId: null,
    jobTemplateId: null,
    startDate: defaultStartDate.value,
    endDate: defaultEndDate.value,
  };
  meta.value.current_page = 1;
  fetchWorkReports();
};

const exportToPdf = async () => {
  try {
    const params = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (filters.value.status !== null) params.status = filters.value.status;
    if (filters.value.branchId) params.branch_id = filters.value.branchId;
    if (filters.value.userId) params.user_id = filters.value.userId;
    if (filters.value.jobTemplateId)
      params.job_template_id = filters.value.jobTemplateId;
    if (filters.value.startDate) params.start_date = filters.value.startDate;
    if (filters.value.endDate) params.end_date = filters.value.endDate;

    const response = await axiosInstance.get("/work-reports/export/pdf", {
      params,
      responseType: "blob",
    });

    // Create blob link and download
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `laporan-kerja-${new Date()
      .toISOString()
      .slice(0, 19)
      .replace(/:/g, "-")}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Error exporting PDF:", error);
  }
};

const exportToExcel = async () => {
  try {
    const params = {};

    if (searchQuery.value) params.search = searchQuery.value;
    if (filters.value.status !== null) params.status = filters.value.status;
    if (filters.value.branchId) params.branch_id = filters.value.branchId;
    if (filters.value.userId) params.user_id = filters.value.userId;
    if (filters.value.jobTemplateId)
      params.job_template_id = filters.value.jobTemplateId;
    if (filters.value.startDate) params.start_date = filters.value.startDate;
    if (filters.value.endDate) params.end_date = filters.value.endDate;

    const response = await axiosInstance.get("/work-reports/export/excel", {
      params,
      responseType: "blob",
    });

    // Create blob link and download
    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `laporan-kerja-${new Date()
      .toISOString()
      .slice(0, 19)
      .replace(/:/g, "-")}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Error exporting Excel:", error);
  }
};

// Lifecycle
onMounted(async () => {
  // Set default date range
  filters.value.startDate = defaultStartDate.value;
  filters.value.endDate = defaultEndDate.value;

  await Promise.all([fetchBranches(), fetchUsers(), fetchJobTemplates()]);
  fetchWorkReports();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Laporan Kerja</h1>
        <p class="text-gray-600 text-sm sm:text-base">Kelola data laporan kerja</p>
      </div>
      <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
        <button
          v-if="can('work-report-list')"
          @click="exportToPdf"
          class="inline-flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm sm:text-base"
          title="Export ke PDF"
        >
          <Download :size="20" class="sm:mr-2" />
          <span class="ml-2 sm:ml-0">Export PDF</span>
        </button>
        <button
          v-if="can('work-report-list')"
          @click="exportToExcel"
          class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm sm:text-base"
          title="Export ke Excel"
        >
          <FileSpreadsheet :size="20" class="sm:mr-2" />
          <span class="ml-2 sm:ml-0">Export Excel</span>
        </button>
        <RouterLink
          :to="{ name: 'admin.workreport.create' }"
          class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base"
        >
          <Plus :size="20" class="sm:mr-2" />
          <span class="ml-2 sm:ml-0">Buat Laporan Kerja</span>
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
            placeholder="Cari laporan kerja..."
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
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4"
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

          <!-- Branch Filter -->
          <div v-if="user?.roles?.includes('admin')">
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

          <!-- User Filter -->
          <div v-if="user?.roles?.includes('admin')">
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >User</label
            >
            <select
              v-model="filters.userId"
              @change="handleFilterChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua User</option>
              <option
                v-for="option in userOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Job Template Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Jenis Pekerjaan</label
            >
            <select
              v-model="filters.jobTemplateId"
              @change="handleFilterChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Jenis</option>
              <option
                v-for="option in jobTemplateOptions"
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
      :items="workReports"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data laporan kerja"
      :empty-icon="FileText"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template
        v-if="user?.roles?.includes('admin')"
        #cell-branch.name="{ value }"
      >
        <div class="text-sm">
          <span
            v-if="value"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
          >
            {{ value }}
          </span>
          <span v-else class="text-gray-400">-</span>
        </div>
      </template>

      <template #cell-job_info="{ item }">
        <div class="space-y-1">
          <!-- SPK Info -->
          <div v-if="item.work_order" class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
              SPK
            </span>
            <span class="text-sm font-medium text-gray-900">{{ item.work_order.number }}</span>
          </div>

          <!-- Job Template Info -->
          <div v-if="item.job_template" class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
              Template
            </span>
            <span class="text-sm text-gray-700">{{ item.job_template.name }}</span>
          </div>

          <!-- Custom Job / Laporan Lainnya -->
          <div v-if="!item.job_template && item.custom_job" class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
              Lainnya
            </span>
            <span class="text-sm text-gray-700">{{ item.custom_job }}</span>
          </div>
          
           <!-- Description Preview if needed, or if totally empty -->
           <div v-if="!item.work_order && !item.job_template && !item.custom_job" class="text-xs text-gray-500 italic">
             -
           </div>
        </div>
      </template>

      <template #cell-status="{ value }">
        <span
          :class="
            value === 'completed'
              ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'
              : value === 'failed'
              ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800'
              : 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800'
          "
        >
          {{
            value === "completed"
              ? "Completed"
              : value === "failed"
              ? "Failed"
              : "In Progress"
          }}
        </span>
      </template>

      <template #cell-created_at="{ value }">
        <div class="text-sm text-gray-500">
          {{ formatToClientTimezone(value) }}
        </div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <RouterLink
            v-if="can('work-report-list')"
            :to="{ name: 'admin.workreport.detail', params: { id: item.id } }"
            class="text-green-600 hover:text-green-900 p-1"
            title="View Detail"
          >
            <Eye :size="16" />
          </RouterLink>
          <RouterLink
            v-if="can('work-report-edit')"
            :to="{ name: 'admin.workreport.edit', params: { id: item.id } }"
            class="text-blue-600 hover:text-blue-900 p-1"
            title="Edit"
          >
            <Edit :size="16" />
          </RouterLink>
          <button
            v-if="can('work-report-delete')"
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
      :message="`Apakah Anda yakin ingin menghapus laporan kerja dengan ID **${workReportToDelete?.id}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteWorkReport"
    />
  </div>
</template>
