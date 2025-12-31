<script setup>
import { ref, onMounted, computed } from "vue";
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
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { axiosInstance } from "@/plugins/axios";
import AttachmentViewDialog from "@/components/common/AttachmentViewDialog.vue";

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

const reportData = ref([]);
const loading = ref(false);
const error = ref(null);
const showFilters = ref(false);
const showPhotoDialog = ref(false);
const selectedPhoto = ref(null);

const filters = ref({
  user_id: "",
  branch_id: "",
  start_date: "",
  end_date: "",
  category: "", // Filter berdasarkan category
});

const loadReportData = async () => {
  // Validasi: branch_id harus dipilih (kecuali untuk user role yang sudah auto-set)
  if (!filters.value.branch_id && !isUser.value) {
    error.value =
      "Silakan pilih cabang terlebih dahulu untuk menampilkan laporan";
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
    // Debug: log data untuk melihat struktur
    console.log("Report Data:", reportData.value);
    if (reportData.value.length > 0) {
      reportData.value.forEach((row, idx) => {
        if (row.electricity) {
          console.log(
            `Row ${idx} Electricity:`,
            JSON.parse(JSON.stringify(row.electricity))
          );
          console.log(`Row ${idx} Electricity Length:`, row.electricity.length);
          row.electricity.forEach((elec, elecIdx) => {
            console.log(
              `Row ${idx} Electricity[${elecIdx}]:`,
              JSON.parse(JSON.stringify(elec))
            );
          });
        }
      });
    }
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

const clearFilters = () => {
  // For user role, preserve branch_id and user_id
  if (isUser.value && currentUser.value) {
    filters.value = {
      user_id: currentUser.value?.id ? String(currentUser.value.id) : "",
      branch_id: currentUser.value?.branch?.id
        ? String(currentUser.value.branch.id)
        : "",
      start_date: "",
      end_date: "",
      category: "",
    };
  } else {
    filters.value = {
      user_id: "",
      branch_id: "",
      start_date: "",
      end_date: "",
      category: "",
    };
  }
  reportData.value = [];
  error.value = null;
};

const handleExport = async () => {
  // Validasi: branch_id harus dipilih (kecuali untuk user role yang sudah auto-set)
  if (!filters.value.branch_id && !isUser.value) {
    error.value = "Silakan pilih cabang terlebih dahulu untuk export laporan";
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
      `laporan-daily-usage-${new Date().toISOString().split("T")[0]}.xlsx`
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
  // Validasi: branch_id dan category harus dipilih (kecuali untuk user role yang sudah auto-set)
  if (!filters.value.branch_id && !isUser.value) {
    error.value = "Silakan pilih cabang terlebih dahulu untuk export PDF";
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
      `laporan-daily-usage-${filters.value.category}-${
        new Date().toISOString().split("T")[0]
      }.pdf`
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
  let cols = 5; // Timestamp, Tanggal, Nama, Outlet, Total Customer
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
const getElectricityTotalWbpUsage = (electricityArray) => {
  if (!electricityArray || !Array.isArray(electricityArray)) return null;
  const values = electricityArray.filter(e => e && e.wbp_usage !== null && e.wbp_usage !== undefined);
  if (values.length === 0) return null;
  return values.reduce((sum, e) => sum + parseFloat(e.wbp_usage || 0), 0);
};

const getElectricityTotalLwbpUsage = (electricityArray) => {
  if (!electricityArray || !Array.isArray(electricityArray)) return null;
  const values = electricityArray.filter(e => e && e.lwbp_usage !== null && e.lwbp_usage !== undefined);
  if (values.length === 0) return null;
  return values.reduce((sum, e) => sum + parseFloat(e.lwbp_usage || 0), 0);
};

const getElectricityGrandTotal = (electricityArray) => {
  if (!electricityArray || !Array.isArray(electricityArray)) return null;
  const values = electricityArray.filter(e => e && e.total_usage !== null && e.total_usage !== undefined);
  if (values.length === 0) return null;
  return values.reduce((sum, e) => sum + parseFloat(e.total_usage || 0), 0);
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
  // Auto-set branch_id and user_id for user role
  if (isUser.value && currentUser.value) {
    if (currentUser.value?.branch?.id) {
      filters.value.branch_id = String(currentUser.value.branch.id);
    }
    if (currentUser.value?.id) {
      filters.value.user_id = String(currentUser.value.id);
    }
    // Auto-load data if branch is set
    if (filters.value.branch_id) {
      loadReportData();
    }
  } else {
    // Only fetch branches and users if not user role
    fetchBranches();
    fetchUsers();
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
        <!-- User Filter - Hide for user role -->
        <div v-if="!isUser">
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

        <!-- Branch Filter - Hide for user role -->
        <div v-if="!isUser">
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Cabang <span class="text-red-500">*</span></label
          >
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
          <p class="mt-1 text-xs text-gray-500">
            Cabang wajib dipilih untuk menghitung opening/closing yang akurat
          </p>
        </div>

        <!-- Start Date Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Tanggal Mulai</label
          >
          <input
            v-model="filters.start_date"
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
            v-model="filters.end_date"
            @change="handleFilterChange"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
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

    <!-- Empty State: Belum Pilih Branch - Only show for non-user role -->
    <div
      v-if="!filters.branch_id && !isUser"
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
              >
                Timestamp
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
              >
                Tanggal
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
              >
                Nama
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
              >
                Outlet
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border"
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
                  colspan="11"
                >
                  LAPORAN LISTRIK
                </th>
              </template>
            </tr>
            <tr>
              <th colspan="5"></th>
              <!-- LAPORAN GAS -->
              <template v-if="!filters.category || filters.category === 'gas'">
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Jenis Kompor
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Jenis Gas
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Closing
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
                  Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Closing
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
                  WBP Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  LWBP Opening
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  WBP Closing
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  LWBP Closing
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Pemakaian WBP
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Pemakaian LWBP
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Total Pemakaian
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Foto WBP
                </th>
                <th class="px-2 py-2 text-xs font-medium text-gray-500 border">
                  Foto LWBP
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
                      {{ row.timestamp }}
                    </td>
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
                        {{ formatNumber(row.gas?.opening) }}
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
                        {{ formatNumber(row.water?.[0]?.opening) }}
                      </td>
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
                        {{ formatNumber(row.electricity[0].wbp_opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].lwbp_opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].wbp_closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].lwbp_closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].wbp_usage) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(row.electricity[0].lwbp_usage) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right font-semibold text-blue-600"
                      >
                        {{ formatNumber(row.electricity[0].total_usage) }}
                      </td>
                      <td class="px-4 py-3 text-sm border text-center">
                        <button
                          v-if="row.electricity[0].photo_wbp"
                          @click="openPhotoDialog(row.electricity[0].photo_wbp)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ImageIcon :size="18" />
                        </button>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td class="px-4 py-3 text-sm border text-center">
                        <button
                          v-if="row.electricity[0].photo_lwbp"
                          @click="openPhotoDialog(row.electricity[0].photo_lwbp)"
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
                        colspan="10"
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
                          {{ formatNumber(elec.wbp_opening) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.lwbp_opening) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.wbp_closing) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.lwbp_closing) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.wbp_usage) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right"
                        >
                          {{ formatNumber(elec.lwbp_usage) }}
                        </td>
                        <td
                          class="px-4 py-3 text-sm text-gray-900 border text-right font-semibold text-blue-600"
                        >
                          {{ formatNumber(elec.total_usage) }}
                        </td>
                        <td class="px-4 py-3 text-sm border text-center">
                          <button
                            v-if="elec.photo_wbp"
                            @click="openPhotoDialog(elec.photo_wbp)"
                            class="text-blue-600 hover:text-blue-800"
                          >
                            <ImageIcon :size="18" />
                          </button>
                          <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-4 py-3 text-sm border text-center">
                          <button
                            v-if="elec.photo_lwbp"
                            @click="openPhotoDialog(elec.photo_lwbp)"
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
                    <td class="px-4 py-3 text-sm text-gray-500 border text-center">-</td>
                    <td class="px-4 py-3 text-sm text-gray-500 border text-center">-</td>
                    <td
                      class="px-4 py-3 text-sm text-gray-900 border text-right font-bold"
                    >
                      {{ formatNumber(getElectricityTotalWbpUsage(row.electricity)) }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-900 border text-right font-bold"
                    >
                      {{ formatNumber(getElectricityTotalLwbpUsage(row.electricity)) }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-green-700 border text-right font-bold text-lg"
                    >
                      {{ formatNumber(getElectricityGrandTotal(row.electricity)) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 border text-center">-</td>
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
                          {{ row.timestamp }}
                        </td>
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
                        {{ formatNumber(elec.wbp_opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.lwbp_opening) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.wbp_closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.lwbp_closing) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.wbp_usage) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right"
                      >
                        {{ formatNumber(elec.lwbp_usage) }}
                      </td>
                      <td
                        class="px-4 py-3 text-sm text-gray-900 border text-right font-semibold text-blue-600"
                      >
                        {{ formatNumber(elec.total_usage) }}
                      </td>
                      <td class="px-4 py-3 text-sm border text-center">
                        <button
                          v-if="elec.photo_wbp"
                          @click="openPhotoDialog(elec.photo_wbp)"
                          class="text-blue-600 hover:text-blue-800"
                        >
                          <ImageIcon :size="18" />
                        </button>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td class="px-4 py-3 text-sm border text-center">
                        <button
                          v-if="elec.photo_lwbp"
                          @click="openPhotoDialog(elec.photo_lwbp)"
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
                      class="px-4 py-3 text-sm text-gray-900 border text-right font-bold"
                    >
                      {{ formatNumber(getElectricityTotalWbpUsage(row.electricity)) }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-gray-900 border text-right font-bold"
                    >
                      {{ formatNumber(getElectricityTotalLwbpUsage(row.electricity)) }}
                    </td>
                    <td
                      class="px-4 py-3 text-sm text-green-700 border text-right font-bold text-lg"
                    >
                      {{ formatNumber(getElectricityGrandTotal(row.electricity)) }}
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
                      {{ row.timestamp }}
                    </td>
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
                      colspan="10"
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
  </div>
</template>
