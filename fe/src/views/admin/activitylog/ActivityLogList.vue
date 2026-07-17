<script setup>
import { ref, onMounted, watch } from "vue";
import { useActivityLogStore } from "@/stores/activityLog";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import DataTable from "@/components/common/DataTable.vue";
import {
  ScrollText,
  LogIn,
  LogOut,
  Plus,
  Pencil,
  Trash2,
  AlertTriangle,
  Activity,
  Monitor,
  X,
  ChevronDown,
  ChevronUp,
} from "lucide-vue-next";

const store = useActivityLogStore();
const { logs, meta, statistics, loading, error } = storeToRefs(store);

// Filters
const filters = ref({
  search: "",
  action: "",
  module: "",
  user_id: "",
  date_from: "",
  date_to: "",
  per_page: 20,
  page: 1,
});

// Detail modal
const showDetail = ref(false);
const detailLog = ref(null);
const detailLoading = ref(false);

// Action options
const actionOptions = [
  { value: "", label: "Semua Aksi" },
  { value: "created", label: "Create" },
  { value: "updated", label: "Update" },
  { value: "deleted", label: "Delete" },
  { value: "login", label: "Login" },
  { value: "logout", label: "Logout" },
  { value: "login_failed", label: "Login Gagal" },
];

// Module options
const moduleOptions = [
  { value: "", label: "Semua Modul" },
  { value: "auth", label: "Auth" },
  { value: "user", label: "User" },
  { value: "branch", label: "Branch" },
  { value: "ticket", label: "Ticket" },
  { value: "ticket-category", label: "Ticket Category" },
  { value: "ticket-reply", label: "Ticket Reply" },
  { value: "ticket-attachment", label: "Ticket Attachment" },
  { value: "work-order", label: "Work Order" },
  { value: "work-report", label: "Work Report" },
  { value: "work-report-attachment", label: "Work Report Attachment" },
  { value: "daily-record", label: "Daily Record" },
  { value: "utility-reading", label: "Utility Reading" },
  { value: "job-template", label: "Job Template" },
  { value: "electricity-meter", label: "Electricity Meter" },
  { value: "electricity-reading", label: "Electricity Reading" },
];

// Table columns
const tableColumns = [
  { key: "created_at", label: "Waktu", nowrap: true },
  { key: "user.name", label: "User" },
  { key: "action", label: "Aksi", nowrap: true },
  { key: "module", label: "Modul", nowrap: true },
  { key: "description", label: "Deskripsi" },
  { key: "ip_address", label: "IP Address", nowrap: true },
];

// Methods
const loadData = async () => {
  const params = { ...filters.value };
  // Remove empty params
  Object.keys(params).forEach((key) => {
    if (params[key] === "" || params[key] === null) delete params[key];
  });

  await store.fetchLogs(params);
};

const loadStatistics = async () => {
  await store.fetchStatistics();
};

const handleFilter = () => {
  filters.value.page = 1;
  loadData();
};

const clearFilters = () => {
  filters.value = {
    search: "",
    action: "",
    module: "",
    user_id: "",
    date_from: "",
    date_to: "",
    per_page: 20,
    page: 1,
  };
  loadData();
};

const handlePageChange = (page) => {
  filters.value.page = page;
  loadData();
};

const handlePerPageChange = (perPage) => {
  filters.value.per_page = perPage;
  filters.value.page = 1;
  loadData();
};

const openDetail = async (log) => {
  detailLog.value = log;
  showDetail.value = true;

  // Fetch full detail if needed (to get old/new values)
  if (!log.old_values && !log.new_values) {
    detailLoading.value = true;
    try {
      const full = await store.fetchLog(log.id);
      detailLog.value = full;
    } catch (e) {
      // silent
    } finally {
      detailLoading.value = false;
    }
  }
};

const closeDetail = () => {
  showDetail.value = false;
  detailLog.value = null;
};

const formatDate = (dateStr) => {
  if (!dateStr) return "-";
  const date = new Date(dateStr);
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
};

const getActionBadge = (action) => {
  const badges = {
    created: {
      class: "bg-green-100 text-green-800",
      label: "Create",
      icon: Plus,
    },
    updated: {
      class: "bg-blue-100 text-blue-800",
      label: "Update",
      icon: Pencil,
    },
    deleted: {
      class: "bg-red-100 text-red-800",
      label: "Delete",
      icon: Trash2,
    },
    login: {
      class: "bg-indigo-100 text-indigo-800",
      label: "Login",
      icon: LogIn,
    },
    logout: {
      class: "bg-gray-100 text-gray-800",
      label: "Logout",
      icon: LogOut,
    },
    login_failed: {
      class: "bg-orange-100 text-orange-800",
      label: "Login Gagal",
      icon: AlertTriangle,
    },
  };
  return (
    badges[action] || {
      class: "bg-gray-100 text-gray-800",
      label: action,
      icon: Activity,
    }
  );
};

const getModuleBadge = (module) => {
  const colors = {
    auth: "bg-purple-100 text-purple-800",
    user: "bg-blue-100 text-blue-800",
    branch: "bg-teal-100 text-teal-800",
    ticket: "bg-yellow-100 text-yellow-800",
    "ticket-category": "bg-yellow-50 text-yellow-700",
    "ticket-reply": "bg-yellow-50 text-yellow-700",
    "ticket-attachment": "bg-yellow-50 text-yellow-700",
    "work-order": "bg-orange-100 text-orange-800",
    "work-report": "bg-pink-100 text-pink-800",
    "work-report-attachment": "bg-pink-50 text-pink-700",
    "daily-record": "bg-green-100 text-green-800",
    "utility-reading": "bg-cyan-100 text-cyan-800",
    "job-template": "bg-indigo-100 text-indigo-800",
    "electricity-meter": "bg-amber-100 text-amber-800",
    "electricity-reading": "bg-amber-50 text-amber-700",
  };
  return colors[module] || "bg-gray-100 text-gray-800";
};

const formatModuleName = (module) => {
  return module
    .split("-")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
};

const formatJsonValue = (value) => {
  if (value === null || value === undefined) return "-";
  if (typeof value === "object") return JSON.stringify(value, null, 2);
  return String(value);
};

const parseUserAgent = (ua) => {
  if (!ua) return { browser: "Tidak diketahui", os: "Tidak diketahui" };

  let browser = "Lainnya";
  let os = "Lainnya";

  // Detect browser
  if (ua.includes("Firefox")) browser = "Firefox";
  else if (ua.includes("Edg/")) browser = "Edge";
  else if (ua.includes("Chrome")) browser = "Chrome";
  else if (ua.includes("Safari")) browser = "Safari";
  else if (ua.includes("Opera") || ua.includes("OPR")) browser = "Opera";

  // Detect OS
  if (ua.includes("Windows")) os = "Windows";
  else if (ua.includes("Mac OS")) os = "macOS";
  else if (ua.includes("Linux")) os = "Linux";
  else if (ua.includes("Android")) os = "Android";
  else if (ua.includes("iPhone") || ua.includes("iPad")) os = "iOS";

  return { browser, os };
};

onMounted(() => {
  loadData();
  loadStatistics();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
      <p class="text-gray-600">
        Riwayat semua aktivitas CRUD, login, dan logout dalam sistem
      </p>
    </div>

    <!-- Alert -->
    <Alert
      v-if="error"
      type="error"
      :message="typeof error === 'string' ? error : 'Terjadi kesalahan'"
      :auto-close="true"
      :duration="5000"
    />

    <!-- Statistics Cards -->
    <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <ScrollText :size="20" class="text-blue-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Total Log</p>
            <p class="text-lg font-bold text-gray-900">
              {{ statistics.total_logs?.toLocaleString("id-ID") }}
            </p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-purple-100 rounded-lg">
            <Activity :size="20" class="text-purple-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Hari Ini</p>
            <p class="text-lg font-bold text-purple-600">
              {{ statistics.today_total || 0 }}
            </p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-indigo-100 rounded-lg">
            <LogIn :size="20" class="text-indigo-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Login Hari Ini</p>
            <p class="text-lg font-bold text-indigo-600">
              {{ statistics.today_by_action?.login || 0 }}
            </p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <Plus :size="20" class="text-green-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Create Hari Ini</p>
            <p class="text-lg font-bold text-green-600">
              {{ statistics.today_by_action?.created || 0 }}
            </p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-amber-100 rounded-lg">
            <Pencil :size="20" class="text-amber-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Update Hari Ini</p>
            <p class="text-lg font-bold text-amber-600">
              {{ statistics.today_by_action?.updated || 0 }}
            </p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-red-100 rounded-lg">
            <Trash2 :size="20" class="text-red-600" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Delete Hari Ini</p>
            <p class="text-lg font-bold text-red-600">
              {{ statistics.today_by_action?.deleted || 0 }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Cari</label
          >
          <input
            v-model="filters.search"
            type="text"
            placeholder="Deskripsi, IP, user..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            @keyup.enter="handleFilter"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Aksi</label
          >
          <select
            v-model="filters.action"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            @change="handleFilter"
          >
            <option
              v-for="opt in actionOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Modul</label
          >
          <select
            v-model="filters.module"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            @change="handleFilter"
          >
            <option
              v-for="opt in moduleOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Dari Tanggal</label
          >
          <input
            v-model="filters.date_from"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            @change="handleFilter"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Sampai Tanggal</label
          >
          <input
            v-model="filters.date_to"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
            @change="handleFilter"
          />
        </div>
        <div class="flex items-end gap-2">
          <button
            @click="handleFilter"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
          >
            Filter
          </button>
          <button
            @click="clearFilters"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <DataTable
      :items="logs"
      :columns="tableColumns"
      :loading="loading"
      :show-actions="false"
      :show-pagination="true"
      :meta="meta"
      empty-message="Tidak ada activity log"
      :empty-icon="ScrollText"
      storage-key="activity-logs"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-created_at="{ value }">
        <span class="text-xs text-gray-600">{{ formatDate(value) }}</span>
      </template>

      <template #cell-user.name="{ item }">
        <div v-if="item.user">
          <p class="text-sm font-medium text-gray-900">{{ item.user.name }}</p>
          <p class="text-xs text-gray-500">{{ item.user.email }}</p>
        </div>
        <span v-else class="text-xs text-gray-400 italic">System</span>
      </template>

      <template #cell-action="{ value }">
        <span
          :class="[
            'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full',
            getActionBadge(value).class,
          ]"
        >
          <component :is="getActionBadge(value).icon" :size="12" />
          {{ getActionBadge(value).label }}
        </span>
      </template>

      <template #cell-module="{ value }">
        <span
          :class="[
            'inline-block px-2 py-0.5 text-xs font-medium rounded-full',
            getModuleBadge(value),
          ]"
        >
          {{ formatModuleName(value) }}
        </span>
      </template>

      <template #cell-description="{ item }">
        <div class="max-w-xs">
          <p class="text-sm text-gray-700 truncate">{{ item.description }}</p>
          <button
            v-if="item.old_values || item.new_values || item.action !== 'login' && item.action !== 'logout' && item.action !== 'login_failed'"
            @click="openDetail(item)"
            class="text-xs text-blue-600 hover:text-blue-800 mt-0.5"
          >
            Lihat Detail
          </button>
        </div>
      </template>

      <template #cell-ip_address="{ item }">
        <div>
          <p class="text-xs font-mono text-gray-700">
            {{ item.ip_address || "-" }}
          </p>
          <p
            v-if="item.user_agent"
            class="text-xs text-gray-400"
            :title="item.user_agent"
          >
            {{ parseUserAgent(item.user_agent).browser }} /
            {{ parseUserAgent(item.user_agent).os }}
          </p>
        </div>
      </template>
    </DataTable>

    <!-- Detail Modal -->
    <Teleport to="body">
      <div
        v-if="showDetail"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black bg-opacity-50"
          @click="closeDetail"
        ></div>

        <!-- Modal Content -->
        <div
          class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden"
        >
          <!-- Modal Header -->
          <div
            class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50"
          >
            <div class="flex items-center gap-3">
              <div class="p-2 bg-blue-100 rounded-lg">
                <ScrollText :size="20" class="text-blue-600" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">
                  Detail Activity Log
                </h3>
                <p class="text-sm text-gray-500">
                  {{ formatDate(detailLog?.created_at) }}
                </p>
              </div>
            </div>
            <button
              @click="closeDetail"
              class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-lg transition-colors"
            >
              <X :size="20" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="overflow-y-auto p-6 space-y-5" style="max-height: 65vh">
            <div v-if="detailLoading" class="text-center py-8">
              <div
                class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
              ></div>
              <p class="mt-2 text-gray-600">Memuat detail...</p>
            </div>

            <template v-else-if="detailLog">
              <!-- Info Grid -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-xs font-medium text-gray-500 uppercase">
                    User
                  </p>
                  <p class="text-sm text-gray-900 mt-1">
                    {{ detailLog.user?.name || "System" }}
                  </p>
                  <p
                    v-if="detailLog.user?.email"
                    class="text-xs text-gray-500"
                  >
                    {{ detailLog.user.email }}
                  </p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500 uppercase">
                    Aksi
                  </p>
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full mt-1',
                      getActionBadge(detailLog.action).class,
                    ]"
                  >
                    <component
                      :is="getActionBadge(detailLog.action).icon"
                      :size="12"
                    />
                    {{ getActionBadge(detailLog.action).label }}
                  </span>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500 uppercase">
                    Modul
                  </p>
                  <span
                    :class="[
                      'inline-block px-2 py-0.5 text-xs font-medium rounded-full mt-1',
                      getModuleBadge(detailLog.module),
                    ]"
                  >
                    {{ formatModuleName(detailLog.module) }}
                  </span>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500 uppercase">
                    IP Address
                  </p>
                  <p class="text-sm font-mono text-gray-900 mt-1">
                    {{ detailLog.ip_address || "-" }}
                  </p>
                </div>
              </div>

              <!-- Description -->
              <div>
                <p class="text-xs font-medium text-gray-500 uppercase">
                  Deskripsi
                </p>
                <p class="text-sm text-gray-900 mt-1">
                  {{ detailLog.description }}
                </p>
              </div>

              <!-- User Agent -->
              <div v-if="detailLog.user_agent">
                <p class="text-xs font-medium text-gray-500 uppercase">
                  Perangkat
                </p>
                <div class="mt-1 flex items-center gap-2">
                  <Monitor :size="14" class="text-gray-400" />
                  <span class="text-sm text-gray-700">
                    {{ parseUserAgent(detailLog.user_agent).browser }} di
                    {{ parseUserAgent(detailLog.user_agent).os }}
                  </span>
                </div>
                <p
                  class="text-xs text-gray-400 mt-1 break-all font-mono bg-gray-50 p-2 rounded"
                >
                  {{ detailLog.user_agent }}
                </p>
              </div>

              <!-- Old Values -->
              <div
                v-if="
                  detailLog.old_values &&
                  Object.keys(detailLog.old_values).length > 0
                "
              >
                <p
                  class="text-xs font-medium text-gray-500 uppercase flex items-center gap-1"
                >
                  <span
                    class="inline-block w-3 h-3 bg-red-200 rounded-full"
                  ></span>
                  Data Sebelum (Old Values)
                </p>
                <div
                  class="mt-2 bg-red-50 border border-red-200 rounded-lg overflow-hidden"
                >
                  <table class="w-full text-sm">
                    <tbody>
                      <tr
                        v-for="(val, key) in detailLog.old_values"
                        :key="key"
                        class="border-b border-red-100 last:border-0"
                      >
                        <td
                          class="px-3 py-2 font-medium text-gray-700 bg-red-100/50 w-1/3"
                        >
                          {{ key }}
                        </td>
                        <td class="px-3 py-2 text-gray-900 font-mono text-xs break-all">
                          {{ formatJsonValue(val) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- New Values -->
              <div
                v-if="
                  detailLog.new_values &&
                  Object.keys(detailLog.new_values).length > 0
                "
              >
                <p
                  class="text-xs font-medium text-gray-500 uppercase flex items-center gap-1"
                >
                  <span
                    class="inline-block w-3 h-3 bg-green-200 rounded-full"
                  ></span>
                  Data Sesudah (New Values)
                </p>
                <div
                  class="mt-2 bg-green-50 border border-green-200 rounded-lg overflow-hidden"
                >
                  <table class="w-full text-sm">
                    <tbody>
                      <tr
                        v-for="(val, key) in detailLog.new_values"
                        :key="key"
                        class="border-b border-green-100 last:border-0"
                      >
                        <td
                          class="px-3 py-2 font-medium text-gray-700 bg-green-100/50 w-1/3"
                        >
                          {{ key }}
                        </td>
                        <td class="px-3 py-2 text-gray-900 font-mono text-xs break-all">
                          {{ formatJsonValue(val) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- No changes data -->
              <div
                v-if="
                  !detailLog.old_values &&
                  !detailLog.new_values &&
                  (detailLog.action === 'login' ||
                    detailLog.action === 'logout' ||
                    detailLog.action === 'login_failed')
                "
              >
                <div
                  class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center"
                >
                  <p class="text-sm text-gray-500">
                    Tidak ada data perubahan untuk aksi ini
                  </p>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
