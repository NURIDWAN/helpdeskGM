<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDailyRecordStore } from "@/stores/dailyRecord";
import { useBranchStore } from "@/stores/branch";
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
  ChevronRight,
  ArrowLeft,
} from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can } from "@/helpers/permissionHelper";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";
import { axiosInstance } from "@/plugins/axios";

const route = useRoute();
const router = useRouter();
const toast = useToastStore();

const checkingDuplicate = ref(false);

const handleCreateClick = async () => {
    // If user has a branch, check for existing record for today
    if (currentUser.value?.branch?.id) {
        checkingDuplicate.value = true;
        try {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const today = `${year}-${month}-${day}`;
            const response = await axiosInstance.get('/daily-records', {
                 params: {
                   branch_id: currentUser.value.branch.id,
                   start_date: today,
                   end_date: today,
                   row_per_page: 1
                 }
            });

            if (response.data.data && response.data.data.length > 0) {
                 toast.error("Tidak dapat membuat laporan baru: Laporan harian untuk cabang Anda dan tanggal hari ini SUDAH ADA. Harap edit laporan yang sudah ada.");
                 return;
            }
        } catch (error) {
            console.error("Failed to check duplicate", error);
            // Optionally warn but let them proceed if check fails? 
            // Better to let them proceed if API check fails to avoid blocking due to network error
        } finally {
            checkingDuplicate.value = false;
        }
    }
    
    // Proceed to create page
    router.push({ name: `${routePrefix.value}.daily-record.create` });
};

const authStore = useAuthStore();
const { user: currentUser } = storeToRefs(authStore);

const dailyRecordStore = useDailyRecordStore();
const branchStore = useBranchStore();

const { dailyRecords, meta, loading, success, error } =
  storeToRefs(dailyRecordStore);
const { fetchDailyRecordsPaginated, deleteDailyRecord } = dailyRecordStore;
const { fetchBranches } = branchStore;

// Reactive data
const searchQuery = ref("");
const showDeleteModal = ref(false);
const dailyRecordToDelete = ref(null);
const showFilters = ref(false);

// Filter state
const filters = ref({
  branchId: null,
  startDate: null,
  endDate: null,
});

// Computed properties
const routePrefix = computed(() => {
  return route.name?.startsWith("app.") ? "app" : "admin";
});

// Check if current user has role "user"
const isUser = computed(() => {
  return (currentUser.value?.roles || []).includes("user");
});

const branchOptions = computed(() =>
  branchStore.branches.map((branch) => ({
    value: branch.id,
    label: branch.name,
  }))
);

const getUserBranchId = () => {
  const branchId = currentUser.value?.branch?.id;
  return branchId ? String(branchId) : null;
};

const ensureUserBranch = () => {
  if (!isUser.value) return false;
  const userBranchId = getUserBranchId();
  if (!userBranchId) return false;
  if (filters.value.branchId !== userBranchId) {
    filters.value.branchId = userBranchId;
  }
  return true;
};

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
  { key: "id", label: "ID", bold: true, nowrap: true },
  { key: "branch.name", label: "Cabang", nowrap: true },
  { key: "user.name", label: "User (PIC)", nowrap: true },
  { key: "total_customers", label: "Total Pelanggan", nowrap: true },
  { key: "created_at", label: "Dibuat", nowrap: true },
];

// Methods
const fetchDailyRecords = () => {
  if (isUser.value) {
    const hasBranch = ensureUserBranch();
    if (!hasBranch) {
      error.value = "Cabang belum tersedia untuk user ini";
      dailyRecords.value = [];
      return;
    }
  }
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
    branch_id: filters.value.branchId,
    start_date: filters.value.startDate,
    end_date: filters.value.endDate,
  };

  // Add 1 day to end_date to include the full day
  if (params.end_date) {
    const endDate = new Date(params.end_date);
    endDate.setDate(endDate.getDate() + 1);
    params.end_date = endDate.toISOString().split("T")[0];
  }

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

  fetchDailyRecordsPaginated(params);
};

const handleSearch = () => {
  meta.value.current_page = 1;
  fetchDailyRecords();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchDailyRecords();
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchDailyRecords();
};

const handleFilterChange = () => {
  meta.value.current_page = 1;
  fetchDailyRecords();
};

const clearFilters = () => {
  filters.value = {
    branchId: isUser.value ? getUserBranchId() : null,
    startDate: null,
    endDate: null,
  };
  meta.value.current_page = 1;
  fetchDailyRecords();
};

const loadFilterData = async () => {
  try {
    if (!isUser.value) {
      await fetchBranches({ limit: 100 });
    }
  } catch (error) {
    console.error("Error loading filter data:", error);
  }
};

const confirmDelete = (dailyRecord) => {
  dailyRecordToDelete.value = dailyRecord;
  showDeleteModal.value = true;
};

const handleDeleteDailyRecord = async () => {
  if (dailyRecordToDelete.value) {
    await deleteDailyRecord(dailyRecordToDelete.value.id);
    if (!error.value) {
      showDeleteModal.value = false;
      dailyRecordToDelete.value = null;
      fetchDailyRecords();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  dailyRecordToDelete.value = null;
};

// Lifecycle
onMounted(async () => {
  // Set default date range
  filters.value.startDate = defaultStartDate.value;
  filters.value.endDate = defaultEndDate.value;

  // Load filter data and daily records
  await loadFilterData();
  if (isUser.value) {
    ensureUserBranch();
  }
  fetchDailyRecords();
});

watch(
  () => currentUser.value,
  () => {
    if (isUser.value && ensureUserBranch()) {
      meta.value.current_page = 1;
      fetchDailyRecords();
    }
  }
);
</script>

<template>
  <div class="space-y-6">
    <!-- Breadcrumb - Only for app (user) route -->
    <nav
      v-if="routePrefix === 'app'"
      class="flex items-center space-x-2 text-sm text-gray-500"
    >
      <RouterLink
        :to="{ name: `${routePrefix}.dashboard` }"
        class="hover:text-gray-700"
      >
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        Data Laporan Harian Cabang
      </span>
    </nav>

    <!-- Header -->
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
    >
      <div class="flex items-center gap-4">
        <!-- Back button - Only for app (user) route -->
        <RouterLink
          v-if="routePrefix === 'app'"
          :to="{ name: `${routePrefix}.dashboard` }"
          class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
        >
          <ArrowLeft :size="20" />
        </RouterLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            Data Laporan Harian Cabang
          </h1>
          <p class="text-gray-600">Kelola data laporan harian cabang</p>
        </div>
      </div>
      <button
        @click="handleCreateClick"
        :disabled="checkingDuplicate"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <div
          v-if="checkingDuplicate"
          class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
        ></div>
        <Plus v-else :size="20" class="mr-2" />
        Buat Laporan Harian Cabang
      </button>
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
            placeholder="Cari laporan harian cabang..."
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
          :class="[
            'grid gap-4',
            isUser
              ? 'grid-cols-1 md:grid-cols-2'
              : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
          ]"
        >
          <!-- Branch Filter - Show for admin, fixed for user -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Cabang</label
            >
            <template v-if="isUser">
              <div
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700"
              >
                {{ currentUser?.branch?.name ?? "-" }}
              </div>
            </template>
            <template v-else>
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
            </template>
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
      :items="dailyRecords"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data laporan harian cabang"
      :empty-icon="FileText"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-created_at="{ value }">
        <div class="text-sm text-gray-500">
          {{ formatToClientTimezone(value) }}
        </div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <RouterLink
            :to="{
              name: `${routePrefix}.daily-record.detail`,
              params: { id: item.id },
            }"
            class="text-green-600 hover:text-green-900 p-1"
            title="View Detail"
          >
            <Eye :size="16" />
          </RouterLink>
          <RouterLink
            v-if="can('daily-record-edit')"
            :to="{
              name: `${routePrefix}.daily-record.edit`,
              params: { id: item.id },
            }"
            class="text-blue-600 hover:text-blue-900 p-1"
            title="Edit"
          >
            <Edit :size="16" />
          </RouterLink>
          <button
            v-if="can('daily-record-delete') && !isUser"
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
      :message="`Apakah Anda yakin ingin menghapus laporan harian cabang ini?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteDailyRecord"
    />
  </div>
</template>
