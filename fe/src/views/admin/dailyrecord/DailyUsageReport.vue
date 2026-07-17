<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { useRoute } from "vue-router";
import { useBranchStore } from "@/stores/branch";
import { useUserStore } from "@/stores/user";
import { useAuthStore } from "@/stores/auth";
import SearchInput from "@/components/common/SearchInput.vue";
import {
  FileSpreadsheet,
  Download,
  Filter,
  X,
  Calendar,
  Image as ImageIcon,
  FileText,
  ChevronRight,
  ArrowLeft,
  RotateCcw,
  Flame,
  Droplets,
  Zap,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { axiosInstance } from "@/plugins/axios";
import AttachmentViewDialog from "@/components/common/AttachmentViewDialog.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

const route = useRoute();
const branchStore = useBranchStore();
const userStore = useUserStore();
const authStore = useAuthStore();

const { branches } = storeToRefs(branchStore);
const { users } = storeToRefs(userStore);
const { user: currentUser } = storeToRefs(authStore);
const { fetchBranches } = branchStore;
const { fetchUsers } = userStore;

// Check route prefix
const routePrefix = computed(() => {
  return route.name?.startsWith("app.") ? "app" : "admin";
});

// Check if current user has role "user"
const isUser = computed(() => {
  return (currentUser.value?.roles || []).includes("user");
});

const isSuperAdmin = computed(() => {
  return (currentUser.value?.roles || []).includes("superadmin");
});

const resetCategory = ref("");

// Meter selector state for electricity reset
const meterList = ref([]);
const selectedMeterIds = ref([]);
const meterLoading = ref(false);
const meterError = ref(null);

// Computed: true iff all meters are selected
const allMetersSelected = computed(() =>
  meterList.value.length > 0 && selectedMeterIds.value.length === meterList.value.length
);

// Computed: reset button disabled when electricity selected with no meters chosen, or loading/error
const resetButtonDisabled = computed(() => {
  if (resetCategory.value === 'electricity') {
    return selectedMeterIds.value.length === 0 || meterLoading.value || !!meterError.value;
  }
  return false;
});

// Toggle all meters on/off
const toggleSelectAll = (checked) => {
  if (checked) {
    selectedMeterIds.value = meterList.value.map(m => m.id);
  } else {
    selectedMeterIds.value = [];
  }
};

// Toggle individual meter selection
const toggleMeter = (meterId) => {
  const idx = selectedMeterIds.value.indexOf(meterId);
  if (idx > -1) {
    selectedMeterIds.value.splice(idx, 1);
  } else {
    selectedMeterIds.value.push(meterId);
  }
};

const resetCategoryLabel = computed(() => {
  const labels = {
    gas: "Gas",
    water: "Air",
    electricity: "Listrik",
  };

  return resetCategory.value
    ? labels[resetCategory.value]
    : "Gas, Air, dan Listrik";
});

const reportData = ref([]);
const loading = ref(false);
const resetLoading = ref(false);
const error = ref(null);
const success = ref(null);
const showFilters = ref(false);
const showPhotoDialog = ref(false);
const showResetModal = ref(false);
const selectedPhoto = ref(null);

const getMonthValue = (date = new Date()) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  return `${year}-${month}`;
};

const getMonthDateRange = (monthValue) => {
  if (!monthValue) {
    return { startDate: "", endDate: "" };
  }

  const [year, month] = monthValue.split("-").map(Number);
  const lastDay = new Date(year, month, 0).getDate();

  return {
    startDate: `${year}-${String(month).padStart(2, "0")}-01`,
    endDate: `${year}-${String(month).padStart(2, "0")}-${String(lastDay).padStart(2, "0")}`,
  };
};

const currentMonth = getMonthValue();
const currentMonthRange = getMonthDateRange(currentMonth);

const filters = ref({
  user_id: "",
  branch_id: "",
  month: currentMonth,
  start_date: currentMonthRange.startDate,
  end_date: currentMonthRange.endDate,
  category: "", // Filter berdasarkan category
});

const selectedMonthLabel = computed(() => {
  if (!filters.value.month) {
    return "Semua tanggal";
  }

  const [year, month] = filters.value.month.split("-").map(Number);
  return new Intl.DateTimeFormat("id-ID", {
    month: "long",
    year: "numeric",
  }).format(new Date(year, month - 1, 1));
});

const loadReportData = async () => {
  // Validasi: branch_id harus dipilih
  if (!filters.value.branch_id) {
    error.value =
      "Silakan pilih cabang terlebih dahulu untuk menampilkan laporan";
    reportData.value = [];
    return;
  }

  if (!filters.value.month) {
    error.value = "Silakan pilih bulan terlebih dahulu untuk menampilkan laporan";
    reportData.value = [];
    return;
  }

  loading.value = true;
  error.value = null;
  try {
    const params = {
      branch_id: filters.value.branch_id, // Required
    };
    if (filters.value.user_id) params.user_id = filters.value.user_id;
    if (filters.value.start_date) params.start_date = filters.value.start_date;
    if (filters.value.end_date) params.end_date = filters.value.end_date;
    if (filters.value.category) params.category = filters.value.category;

    const response = await axiosInstance.get(
      "/daily-records/report/daily-usage",
      { params }
    );
    reportData.value = response.data.data;
  } catch (err) {
    error.value = err.response?.data?.message || "Terjadi kesalahan";
    console.error("Error loading report:", err);
    reportData.value = [];
  } finally {
    loading.value = false;
  }
};

const handleFilterChange = () => {
  loadReportData();
};

const categoryOptions = [
  { value: '', label: 'Semua Kategori', icon: null },
  { value: 'gas', label: 'Gas', icon: Flame },
  { value: 'water', label: 'Air', icon: Droplets },
  { value: 'electricity', label: 'Listrik', icon: Zap },
];

const selectCategory = (categoryValue) => {
  filters.value.category = categoryValue;
  handleFilterChange();
};

const handleMonthChange = () => {
  const { startDate, endDate } = getMonthDateRange(filters.value.month);
  filters.value.start_date = startDate;
  filters.value.end_date = endDate;
  handleFilterChange();
};

const clearFilters = () => {
  const defaultMonth = getMonthValue();
  const { startDate, endDate } = getMonthDateRange(defaultMonth);

  filters.value = {
    user_id: "",
    branch_id: isUser.value && currentUser.value?.branch?.id
      ? String(currentUser.value.branch.id)
      : "",
    month: defaultMonth,
    start_date: startDate,
    end_date: endDate,
    category: "",
  };

  // Auto-reload if branch is set (user role)
  if (filters.value.branch_id) {
    loadReportData();
  }
};

const handleExport = async () => {
  // Validasi: branch_id harus dipilih
  if (!filters.value.branch_id) {
    error.value = "Silakan pilih cabang terlebih dahulu untuk export laporan";
    return;
  }

  if (!filters.value.month) {
    error.value = "Silakan pilih bulan terlebih dahulu untuk export laporan";
    return;
  }

  try {
    const params = {
      branch_id: filters.value.branch_id, // Required
    };
    if (filters.value.user_id) params.user_id = filters.value.user_id;
    if (filters.value.start_date) params.start_date = filters.value.start_date;
    if (filters.value.end_date) params.end_date = filters.value.end_date;
    // Pass 'all' when no category selected to export all categories
    params.category = filters.value.category || 'all';

    const response = await axiosInstance.get(
      "/daily-records/report/daily-usage/export",
      {
        params,
        responseType: "blob",
      }
    );

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `laporan-daily-usage-${filters.value.month || new Date().toISOString().split("T")[0]}.xlsx`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    error.value =
      err.response?.data?.message || "Terjadi kesalahan saat export";
    console.error("Error exporting:", err);
  }
};

const handleExportPdf = async () => {
  // Validasi: branch_id dan category harus dipilih
  if (!filters.value.branch_id) {
    error.value = "Silakan pilih cabang terlebih dahulu untuk export PDF";
    return;
  }

  if (!filters.value.month) {
    error.value = "Silakan pilih bulan terlebih dahulu untuk export PDF";
    return;
  }

  try {
    const params = {
      branch_id: filters.value.branch_id, // Required
      // Pass 'all' when no category selected to export all categories
      category: filters.value.category || 'all',
    };
    if (filters.value.user_id) params.user_id = filters.value.user_id;
    if (filters.value.start_date) params.start_date = filters.value.start_date;
    if (filters.value.end_date) params.end_date = filters.value.end_date;

    const response = await axiosInstance.get(
      "/daily-records/report/daily-usage/export/pdf",
      {
        params,
        responseType: "blob",
      }
    );

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `laporan-daily-usage-${filters.value.category || "all"}-${filters.value.month || new Date().toISOString().split("T")[0]}.pdf`
    );
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    error.value =
      err.response?.data?.message || "Terjadi kesalahan saat export PDF";
    console.error("Error exporting PDF:", err);
  }
};

const openResetModal = () => {
  if (!filters.value.branch_id) {
    error.value = "Silakan pilih cabang terlebih dahulu untuk reset daily usage";
    return;
  }

  if (!filters.value.month) {
    error.value = "Silakan pilih bulan terlebih dahulu untuk reset daily usage";
    return;
  }

  // Reset the modal category to empty (all) when opening
  resetCategory.value = "";
  showResetModal.value = true;
};

const handleResetDailyUsage = async () => {
  resetLoading.value = true;
  error.value = null;
  success.value = null;

  try {
    const payload = {
      branch_id: filters.value.branch_id,
    };

    if (filters.value.user_id) payload.user_id = filters.value.user_id;
    if (filters.value.start_date) payload.start_date = filters.value.start_date;
    if (filters.value.end_date) payload.end_date = filters.value.end_date;
    if (resetCategory.value) payload.category = resetCategory.value;

    // Include electricity_meter_ids when resetting electricity with specific meters selected
    if (resetCategory.value === 'electricity' && selectedMeterIds.value.length > 0) {
      payload.electricity_meter_ids = selectedMeterIds.value;
    }

    const response = await axiosInstance.post(
      "/daily-records/report/daily-usage/reset",
      payload
    );

    success.value = response.data.message || "Daily usage berhasil direset ke 0";
    showResetModal.value = false;
    await loadReportData();
  } catch (err) {
    error.value =
      err.response?.data?.message || "Terjadi kesalahan saat reset daily usage";
    console.error("Error resetting daily usage:", err);
  } finally {
    resetLoading.value = false;
  }
};

const openPhotoDialog = (photoUrl) => {
  if (photoUrl) {
    // Format attachment sesuai dengan yang diharapkan AttachmentViewDialog
    selectedPhoto.value = {
      file_path: photoUrl,
      file_type: "image/jpeg", // Default, karena kita tahu ini adalah foto
      created_at: new Date().toISOString(),
      id: null,
    };
    showPhotoDialog.value = true;
  }
};

const closePhotoDialog = () => {
  showPhotoDialog.value = false;
  selectedPhoto.value = null;
};

const getColspan = () => {
  let cols = 4; // Tanggal, Nama, Outlet, Total Customer
  if (!filters.value.category || filters.value.category === "gas") {
    cols += 7; // LAPORAN GAS
  }
  if (!filters.value.category || filters.value.category === "water") {
    cols += 5; // LAPORAN AIR
  }
  if (!filters.value.category || filters.value.category === "electricity") {
    cols += 11; // LAPORAN LISTRIK (added Nama column)
  }
  return cols;
};

// Helper function to format numbers
const formatNumber = (value) => {
  if (value === null || value === undefined || value === "") {
    return "-";
  }
  const num = parseFloat(value);
  if (isNaN(num)) {
    return "-";
  }
  // Tampilkan 0.00 jika nilai adalah 0, bukan '-'
  return num.toFixed(2);
};

// Helper functions to calculate electricity totals
const getElectricityTotalUsage = (electricityArray) => {
  if (!electricityArray || !Array.isArray(electricityArray)) return null;
  const values = electricityArray.filter(e => e && e.usage !== null && e.usage !== undefined);
  if (values.length === 0) return null;
  return values.reduce((sum, e) => sum + parseFloat(e.usage || 0), 0);
};

// Helper function to get electricity rowspan (for multi-meter display)
// Returns: number of meters + 1 (for TOTAL row) if more than 1 meter, otherwise 1
const getElectricityRowspan = (electricityArray) => {
  if (!electricityArray || !Array.isArray(electricityArray)) return 1;
  const validMeters = electricityArray.filter(e => e);
  if (validMeters.length <= 1) return 1;
  return validMeters.length + 1; // meters + TOTAL row
};

// Helper to check if electricity has multiple meters
const hasMultipleMeters = (electricityArray) => {
  if (!electricityArray || !Array.isArray(electricityArray)) return false;
  return electricityArray.filter(e => e).length > 1;
};

onMounted(() => {
  // Always fetch branches and users for everyone
  fetchBranches();
  
  // Also fetch users for everyone
  fetchUsers();

  // For user role, auto-set their branch and load report
  if (isUser.value && currentUser.value) {
    if (currentUser.value?.branch?.id) {
      filters.value.branch_id = String(currentUser.value.branch.id);
      loadReportData();
    }
  }
});

// Watch resetCategory to fetch meters when "Listrik" is selected
watch(resetCategory, async (newVal) => {
  if (newVal === 'electricity') {
    // Fetch meters for the selected branch
    const branchId = filters.value.branch_id;
    if (!branchId) {
      meterError.value = 'Cabang belum dipilih';
      return;
    }

    meterLoading.value = true;
    meterError.value = null;
    selectedMeterIds.value = [];
    meterList.value = [];

    try {
      const response = await axiosInstance.get(`/branches/${branchId}/electricity-meters`);
      // Filter to only include active meters
      const allMeters = response.data.data || response.data;
      meterList.value = allMeters.filter(meter => meter.is_active === true);
    } catch (err) {
      meterError.value = err.response?.data?.message || 'Gagal memuat data meter listrik';
      console.error('Error fetching electricity meters:', err);
    } finally {
      meterLoading.value = false;
    }
  } else {
    // Clear meter state when category changes away from electricity
    meterList.value = [];
    selectedMeterIds.value = [];
    meterError.value = null;
  }
});
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
      <span class="text-gray-900 font-medium"> Laporan Daily Usage </span>
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
          <h1 class="text-3xl font-bold text-gray-900">Laporan Daily Usage</h1>
          <p class="text-gray-600 mt-1">
            Laporan penggunaan utilitas dengan opening, closing, dan pemakaian
          </p>
        </div>
      </div>
      <div class="flex gap-3">
        <button
          v-if="isSuperAdmin"
          @click="openResetModal"
          :disabled="loading || resetLoading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
        >
          <RotateCcw :size="18" />
          Reset Usage
        </button>
        <button
          @click="showFilters = !showFilters"
          class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
        >
          <Filter :size="18" />
          Filter
        </button>
        <button
          @click="handleExport"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
        >
          <FileSpreadsheet :size="18" />
          Export Excel
        </button>
        <button
          @click="handleExportPdf"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
        >
          <FileText :size="18" />
          Export PDF
        </button>
      </div>
    </div>

    <!-- Alert -->
    <Alert
      v-if="success"
      type="success"
      :message="success"
      @close="success = null"
    />
    <Alert v-if="error" type="danger" :message="error" @close="error = null" />

    <!-- Filters -->
    <div
      v-if="showFilters"
      class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
    >
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Filter Laporan</h3>
        <button
          @click="showFilters = false"
          class="text-gray-500 hover:text-gray-700"
        >
          <X :size="20" />
        </button>
      </div>

      <div
        :class="[
          'grid gap-4',
          isUser ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 md:grid-cols-2',
        ]"
      >
        <!-- User Filter - Show for ALL -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >User</label
          >
          <select
            v-model="filters.user_id"
            @change="handleFilterChange"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Semua User</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>

        <!-- Branch Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Cabang <span class="text-red-500">*</span></label
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
              v-model="filters.branch_id"
              @change="handleFilterChange"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Pilih Cabang</option>
              <option
                v-for="branch in branches"
                :key="branch.id"
                :value="branch.id"
              >
                {{ branch.name }}
              </option>
            </select>
          </template>
          <p class="mt-1 text-xs text-gray-500">
            Cabang wajib dipilih untuk menghitung opening/closing yang akurat
          </p>
        </div>

        <!-- Month Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Bulan</label
          >
          <input
            v-model="filters.month"
            @change="handleMonthChange"
            type="month"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p class="mt-1 text-xs text-gray-500">
            Menampilkan data untuk bulan {{ selectedMonthLabel }}.
          </p>
        </div>

        <!-- Date Range Summary -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Periode Tanggal</label
          >
          <div
            class="flex min-h-[42px] items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
          >
            {{ filters.start_date || "-" }} s/d {{ filters.end_date || "-" }}
          </div>
          <p class="mt-1 text-xs text-gray-500">
            Rentang tanggal otomatis mengikuti bulan yang dipilih.
          </p>
        </div>

        <!-- Category Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Kategori</label
          >
          <select
            v-model="filters.category"
            @change="handleFilterChange"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Semua Kategori</option>
            <option value="gas">Gas</option>
            <option value="water">Air</option>
            <option value="electricity">Listrik</option>
          </select>
          <p class="mt-1 text-xs text-gray-500">
            Pilih kategori untuk memfilter laporan. Kosongkan untuk melihat semua kategori.
          </p>
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

    <!-- Empty State: Belum Pilih Branch -->
    <div
      v-if="!filters.branch_id"
      class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
    >
      <div class="max-w-md mx-auto">
        <Filter :size="48" class="mx-auto text-gray-400 mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          Pilih Cabang Terlebih Dahulu
        </h3>
        <p class="text-gray-600 mb-4">
          Silakan pilih cabang di filter untuk menampilkan laporan daily usage.
          Opening dan closing akan dihitung berdasarkan cabang yang dipilih agar
          lebih akurat.
        </p>
        <button
          @click="showFilters = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          <Filter :size="18" />
          Buka Filter
        </button>
      </div>
    </div>

    <!-- Report Table -->
    <div
      v-else
      class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                rowspan="2"
              >
                Tanggal
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                rowspan="2"
              >
                Nama
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                rowspan="2"
              >
                Outlet
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                rowspan="2"
              >
                Total Customer
              </th>
              <template v-if="!filters.category || filters.category === 'gas'">
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                  colspan="7"
                >
                  LAPORAN GAS
                </th>
              </template>
              <template
                v-if="!filters.category || filters.category === 'water'"
              >
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                  colspan="5"
                >
                  LAPORAN AIR
                </th>
              </template>
              <template
                v-if="!filters.category || filters.category === 'electricity'"
              >
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
                  colspan="6"
                >
                  LAPORAN LISTRIK
                </th>
              </template>
            </tr>
            <tr>
              <!-- LAPORAN GAS -->
              <template v-if="!filters.category || filters.category === 'gas'">
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Jenis Kompor
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Jenis Gas
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Closing
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Total Pemakaian
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Foto
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Lokasi
                </th>
              </template>
              <!-- LAPORAN AIR -->
              <template
                v-if="!filters.category || filters.category === 'water'"
              >
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Closing
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Total Pemakaian
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Foto
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Lokasi
                </th>
              </template>
              <!-- LAPORAN LISTRIK -->
              <template
                v-if="!filters.category || filters.category === 'electricity'"
              >
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Nama
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Lokasi
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Closing
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Pemakaian
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Foto
                </th>
              </template>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading">
              <td
                :colspan="getColspan()"
                class="px-4 py-8 text-center text-gray-500"
              >
                Memuat data...
              </td>
            </tr>
            <tr v-else-if="reportData.length === 0">
              <td
                :colspan="getColspan()"
                class="px-4 py-8 text-center text-gray-500"
              >
                Tidak ada data
              </td>
            </tr>
            <template v-else>
              <template v-for="(row, index) in reportData" :key="index">
                <!-- LAPORAN GAS dan AIR: Single row per daily record (jika filter bukan electricity) -->
                <template v-if="filters.category !== 'electricity'">
                  <tr class="hover:bg-gray-50">
                    <!-- Common columns with rowspan for multi-meter -->
                    <td 
                      class="px-4 py-3 text-sm text-gray-900 border align-top"
                      :rowspan="hasMultipleMeters(row.electricity) && (!filters.category || filters.category === 'electricity') ? getElectricityRowspan(row.electricity) : 1"
                    >
                      {{ row.tanggal }}
                    </td>
                    <td 
                      class="px-4 py-3 text-sm text-gray-900 border align-top"
                      :rowspan="hasMultipleMeters(row.electricity) && (!filters.category || filters.category === 'electricity') ? getElectricityRowspan(row.electricity) : 1"
                    >
                      {{ row.nama }}
                    </td>
                    <td 
                      class="px-4 py-3 text-sm text-gray-900 border align-top"
                      :rowspan="hasMultipleMeters(row.electricity) && (!filters.category || filters.category === 'electricity') ? getElectricityRowspan(row.electricity) : 1"
                    >
                      {{ row.outlet }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-900 border text-center align-top"
                      :rowspan="hasMultipleMeters(row.electricity) && (!filters.category || filters.category === 'electricity') ? getElectricityRowspan(row.electricity) : 1"
                    >
                      {{ row.total_customer }}
                    </td>
                    <!-- LAPORAN GAS with rowspan -->
                    <template
                      v-if="!filters.category || filters.category === 'gas'"
                    >
                      <td 
                        class="px-4 py-3 text-sm text-gray-900 border align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ row.gas?.stove_type ?? "-" }}
                      </td>
                      <td 
                        class="px-4 py-3 text-sm text-gray-900 border align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ row.gas?.gas_type ?? "-" }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ formatNumber(row.gas?.closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ formatNumber(row.gas?.opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ formatNumber(row.gas?.usage) }}
                      </td>
                      <td 
                        class="px-4 py-3 text-sm border text-center align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        <button
                          v-if="row.gas?.photo"
                          @click="openPhotoDialog(row.gas.photo)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ImageIcon :size="18" />
                        </button>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td 
                        class="px-4 py-3 text-sm text-gray-900 border align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ row.gas?.location ?? "-" }}
                      </td>
                    </template>
                    <!-- LAPORAN AIR with rowspan -->
                    <template
                      v-if="!filters.category || filters.category === 'water'"
                    >
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ formatNumber(row.water?.[0]?.closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ formatNumber(row.water?.[0]?.opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ formatNumber(row.water?.[0]?.usage) }}
                      </td>
                      <td 
                        class="px-4 py-3 text-sm border text-center align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        <button
                          v-if="row.water?.[0]?.photo"
                          @click="openPhotoDialog(row.water[0].photo)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ImageIcon :size="18" />
                        </button>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td 
                        class="px-4 py-3 text-sm text-gray-900 border align-top"
                        :rowspan="hasMultipleMeters(row.electricity) && !filters.category ? getElectricityRowspan(row.electricity) : 1"
                      >
                        {{ row.water?.[0]?.location ?? "-" }}
                      </td>
                    </template>
                    <!-- LAPORAN LISTRIK: Tampilkan row pertama jika ada (untuk semua kategori) -->
                    <template
                      v-if="
                        (!filters.category ||
                          filters.category === 'electricity') &&
                        row.electricity &&
                        Array.isArray(row.electricity) &&
                        row.electricity.length > 0 &&
                        row.electricity[0]
                      "
                    >
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-left font-medium"
                      >
                        {{ row.electricity[0].meter_name ?? `Meter 1` }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-left"
                      >
                        {{ row.electricity[0].location ?? "-" }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right font-semibold text-blue-600"
                      >
                        {{ formatNumber(row.electricity[0].usage) }}
                      </td>
                      <td class="px-4 py-3 text-sm border text-center">
                        <button
                          v-if="row.electricity[0].photo"
                          @click="openPhotoDialog(row.electricity[0].photo)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ImageIcon :size="18" />
                        </button>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                    </template>
                    <!-- Jika tidak ada data listrik tapi filter semua kategori -->
                    <template
                      v-else-if="
                        !filters.category || filters.category === 'electricity'
                      "
                    >
                      <td
                        colspan="5"
                        class="px-4 py-3 text-sm text-gray-500 border text-center"
                      >
                        -
                      </td>
                    </template>
                  </tr>
                  <!-- LAPORAN LISTRIK: Multiple rows tambahan jika ada lebih dari 1 listrik (hanya jika filter semua kategori atau electricity) -->
                  <template
                    v-if="
                      (!filters.category ||
                        filters.category === 'electricity') &&
                      row.electricity &&
                      Array.isArray(row.electricity) &&
                      row.electricity.length > 1
                    "
                  >
                    <template
                      v-for="(elec, elecIndex) in row.electricity
                        .slice(1)
                        .filter((e) => e)"
                      :key="`${index}-${elecIndex + 1}`"
                    >
                      <tr class="hover:bg-gray-50 bg-yellow-50/30">
                        <!-- No empty columns needed - parent cells use rowspan -->
                        <!-- Electricity specific columns -->
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-left font-medium"
                        >
                          {{ elec.meter_name ?? `Meter ${elecIndex + 2}` }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-left"
                        >
                          {{ elec.location ?? "-" }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.closing) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.opening) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right font-semibold text-blue-600"
                        >
                          {{ formatNumber(elec.usage) }}
                        </td>
                        <td class="px-4 py-3 text-sm border text-center">
                          <button
                            v-if="elec.photo"
                            @click="openPhotoDialog(elec.photo)"
                            class="text-blue-600 hover:text-blue-800"
                          >
                            <ImageIcon :size="18" />
                          </button>
                          <span v-else class="text-gray-400">-</span>
                        </td>
                      </tr>
                    </template>
                  </template>
                  <!-- TOTAL Row for Multi-Meter Electricity (when viewing all categories) -->
                  <tr
                    v-if="
                      (!filters.category || filters.category === 'electricity') &&
                      row.electricity &&
                      Array.isArray(row.electricity) &&
                      row.electricity.length > 1
                    "
                    class="bg-yellow-100 font-semibold"
                  >
                    <!-- No empty columns needed - parent cells use rowspan -->
                    <!-- Electricity TOTAL columns -->
                    <td
                      class="px-4 py-3 text-sm text-gray-700 border text-left font-bold"
                      colspan="2"
                    >
                      TOTAL
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 border text-center">-</td>
                    <td class="px-4 py-3 text-sm text-gray-500 border text-center">-</td>
                    <td
                      class="px-4 py-3 text-sm text-green-700 border text-right font-bold text-lg"
                    >
                      {{ formatNumber(getElectricityTotalUsage(row.electricity)) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 border text-center">-</td>
                  </tr>
                </template>
                <!-- LAPORAN LISTRIK ONLY: Multiple rows per daily record (jika filter electricity saja) -->
                <template
                  v-else-if="
                    filters.category === 'electricity' &&
                    row.electricity &&
                    Array.isArray(row.electricity) &&
                    row.electricity.length > 0
                  "
                >
                  <template
                    v-for="(elec, elecIndex) in row.electricity.filter(
                      (e) => e
                    )"
                    :key="`${index}-${elecIndex}`"
                  >
                    <tr class="hover:bg-gray-50">
                      <!-- Common columns with rowspan for first electricity entry -->
                      <template v-if="elecIndex === 0">
                        <td
                          :rowspan="row.electricity.filter((e) => e).length"
                          class="px-4 py-3 text-sm text-gray-900 border"
                        >
                          {{ row.tanggal }}
                        </td>
                        <td
                          :rowspan="row.electricity.filter((e) => e).length"
                          class="px-4 py-3 text-sm text-gray-900 border"
                        >
                          {{ row.nama }}
                        </td>
                        <td
                          :rowspan="row.electricity.filter((e) => e).length"
                          class="px-4 py-3 text-sm text-gray-900 border"
                        >
                          {{ row.outlet }}
                        </td>
                        <td
                          :rowspan="row.electricity.filter((e) => e).length"
                          class="px-4 py-3 text-sm text-gray-900 border text-center"
                        >
                          {{ row.total_customer }}
                        </td>
                      </template>
                      <!-- Electricity specific columns -->
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-left"
                      >
                        {{ elec.location ?? "-" }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right font-semibold text-blue-600"
                      >
                        {{ formatNumber(elec.usage) }}
                      </td>
                      <td class="px-4 py-3 text-sm border text-center">
                        <button
                          v-if="elec.photo"
                          @click="openPhotoDialog(elec.photo)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ImageIcon :size="18" />
                        </button>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                    </tr>
                  </template>
                  <!-- TOTAL Row for Multi-Meter Electricity -->
                  <tr
                    v-if="row.electricity && row.electricity.filter((e) => e).length > 1"
                    class="bg-yellow-50 font-semibold"
                  >
                    <td
                      colspan="5"
                      class="px-4 py-3 text-sm text-gray-700 border text-right"
                    >
                      <!-- Empty for common columns -->
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-700 border text-left font-bold"
                      colspan="2"
                    >
                      TOTAL
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-500 border text-center"
                    >
                      -
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-500 border text-center"
                    >
                      -
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-green-700 border text-right font-bold text-lg"
                    >
                      {{ formatNumber(getElectricityTotalUsage(row.electricity)) }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-500 border text-center"
                    >
                      -
                    </td>
                  </tr>
                  <!-- If no electricity data -->
                  <tr
                    v-if="
                      row.electricity &&
                      row.electricity.filter((e) => e).length === 0
                    "
                    class="hover:bg-gray-50"
                  >
                    <td class="px-4 py-3 text-sm text-gray-900 border">
                      {{ row.tanggal }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 border">
                      {{ row.nama }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 border">
                      {{ row.outlet }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-900 border text-center"
                    >
                      {{ row.total_customer }}
                    </td>
                    <td
                      colspan="5"
                      class="px-4 py-3 text-sm text-gray-500 border text-center"
                    >
                      Tidak ada data listrik
                    </td>
                  </tr>
                </template>
              </template>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Photo Dialog -->
    <AttachmentViewDialog
      :show="showPhotoDialog"
      :attachment="selectedPhoto"
      @close="closePhotoDialog"
    />

    <ConfirmationModal
      :show="showResetModal"
      title="Reset Daily Usage"
      :message="`Reset ${resetCategoryLabel} pada cabang terpilih ke 0?`"
      subtitle="Nilai meter pada data yang sesuai filter akan diubah menjadi 0. Foto dan record laporan tidak akan dihapus."
      confirm-text="Ya, Reset ke 0"
      cancel-text="Batal"
      loading-text="Mereset..."
      type="warning"
      :loading="resetLoading"
      :disabled="resetButtonDisabled"
      @close="showResetModal = false"
      @confirm="handleResetDailyUsage"
    >
      <template #body>
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kategori yang akan di-reset</label>
          <div class="grid grid-cols-1 gap-2">
            <label
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
              :class="resetCategory === '' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
            >
              <input
                v-model="resetCategory"
                type="radio"
                name="resetCategory"
                value=""
                class="text-amber-600 focus:ring-amber-500"
              />
              <div class="flex items-center gap-2">
                <Flame :size="16" class="text-orange-500" />
                <Droplets :size="16" class="text-blue-500" />
                <Zap :size="16" class="text-yellow-500" />
              </div>
              <span class="text-sm font-medium text-gray-900">Semua (Gas, Air, Listrik)</span>
            </label>
            <label
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
              :class="resetCategory === 'gas' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
            >
              <input
                v-model="resetCategory"
                type="radio"
                name="resetCategory"
                value="gas"
                class="text-amber-600 focus:ring-amber-500"
              />
              <Flame :size="16" class="text-orange-500" />
              <span class="text-sm font-medium text-gray-900">Gas</span>
            </label>
            <label
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
              :class="resetCategory === 'water' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
            >
              <input
                v-model="resetCategory"
                type="radio"
                name="resetCategory"
                value="water"
                class="text-amber-600 focus:ring-amber-500"
              />
              <Droplets :size="16" class="text-blue-500" />
              <span class="text-sm font-medium text-gray-900">Air</span>
            </label>
            <label
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
              :class="resetCategory === 'electricity' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
            >
              <input
                v-model="resetCategory"
                type="radio"
                name="resetCategory"
                value="electricity"
                class="text-amber-600 focus:ring-amber-500"
              />
              <Zap :size="16" class="text-yellow-500" />
              <span class="text-sm font-medium text-gray-900">Listrik</span>
            </label>
          </div>
        </div>

        <!-- Meter Selector Panel (shown when electricity is selected) -->
        <div v-if="resetCategory === 'electricity'" class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Meter Listrik</label>

          <!-- Loading state -->
          <div v-if="meterLoading" class="flex items-center justify-center py-4">
            <span class="size-5 animate-spin rounded-full border-2 border-amber-600 border-t-transparent"></span>
            <span class="ml-2 text-sm text-gray-600">Memuat data meter...</span>
          </div>

          <!-- Error state -->
          <div v-else-if="meterError" class="p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700">{{ meterError }}</p>
          </div>

          <!-- No active meters -->
          <div v-else-if="meterList.length === 0" class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <p class="text-sm text-gray-600">Tidak ada meter listrik aktif untuk cabang ini.</p>
          </div>

          <!-- Meter list with checkboxes -->
          <div v-else class="space-y-2">
            <!-- Select All checkbox -->
            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors border-gray-300 bg-gray-50 hover:bg-gray-100">
              <input
                type="checkbox"
                :checked="allMetersSelected"
                @change="toggleSelectAll($event.target.checked)"
                class="text-amber-600 focus:ring-amber-500 rounded"
              />
              <span class="text-sm font-semibold text-gray-900">Pilih Semua</span>
            </label>

            <!-- Individual meter checkboxes -->
            <div class="max-h-48 overflow-y-auto space-y-1">
              <label
                v-for="meter in meterList"
                :key="meter.id"
                class="flex items-center gap-3 p-2 pl-3 border rounded-lg cursor-pointer transition-colors"
                :class="selectedMeterIds.includes(meter.id) ? 'border-amber-400 bg-amber-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
              >
                <input
                  type="checkbox"
                  :checked="selectedMeterIds.includes(meter.id)"
                  @change="toggleMeter(meter.id)"
                  class="text-amber-600 focus:ring-amber-500 rounded"
                />
                <span class="text-sm text-gray-900">{{ meter.meter_name }} ({{ meter.location }})</span>
              </label>
            </div>
          </div>
        </div>
      </template>
    </ConfirmationModal>
  </div>
</template>
