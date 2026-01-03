<script setup>
import { ref, onMounted, computed } from "vue";
import { useUserActivityStore } from "@/stores/userActivity";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import DataTable from "@/components/common/DataTable.vue";
import { Users, Activity, Clock, AlertCircle, CheckCircle, XCircle } from "lucide-vue-next";

const store = useUserActivityStore();
const { users, statistics, loading, error } = storeToRefs(store);
const { fetchUsers, fetchStatistics } = store;

// Filters
const filters = ref({
  role: "",
  activity_status: "",
  search: "",
});

// Role options
const roleOptions = ref([
  { value: "", label: "Semua Role" },
  { value: "superadmin", label: "Super Admin" },
  { value: "admin", label: "Admin" },
  { value: "staff", label: "Staff" },
  { value: "user", label: "User" },
]);

// Activity status options
const activityOptions = [
  { value: "", label: "Semua Status" },
  { value: "active", label: "🟢 Aktif" },
  { value: "rarely", label: "🟡 Jarang Aktif" },
  { value: "inactive", label: "🔴 Tidak Aktif" },
  { value: "never", label: "⚫ Belum Pernah Login" },
];

// Table columns
const tableColumns = [
  { key: "name", label: "Nama", bold: true },
  { key: "email", label: "Email" },
  { key: "roles", label: "Role" },
  { key: "branch", label: "Cabang" },
  { key: "activity_status", label: "Status" },
  { key: "last_login_at", label: "Login Terakhir" },
];

// Methods
const loadData = async () => {
  await Promise.all([
    fetchUsers(filters.value),
    fetchStatistics(),
  ]);
};

const handleFilter = () => {
  fetchUsers(filters.value);
};

const clearFilters = () => {
  filters.value = { role: "", activity_status: "", search: "" };
  fetchUsers(filters.value);
};

const getStatusBadge = (status) => {
  const badges = {
    active: { class: "bg-green-100 text-green-800", label: "Aktif", icon: "🟢" },
    rarely: { class: "bg-yellow-100 text-yellow-800", label: "Jarang", icon: "🟡" },
    inactive: { class: "bg-red-100 text-red-800", label: "Tidak Aktif", icon: "🔴" },
    never: { class: "bg-gray-100 text-gray-600", label: "Belum Pernah", icon: "⚫" },
  };
  return badges[status] || badges.never;
};

const formatDate = (dateStr) => {
  if (!dateStr) return "Belum pernah";
  const date = new Date(dateStr);
  return date.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Monitoring Aktivitas User</h1>
      <p class="text-gray-600">Pantau status aktivitas user berdasarkan role</p>
    </div>

    <!-- Alert -->
    <Alert
      v-if="error"
      type="error"
      :message="error"
      :auto-close="true"
      :duration="5000"
    />

    <!-- Statistics Cards -->
    <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-blue-100 rounded-lg">
            <Users :size="20" class="text-blue-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Total User</p>
            <p class="text-xl font-bold text-gray-900">{{ statistics.total }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-green-100 rounded-lg">
            <CheckCircle :size="20" class="text-green-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Aktif</p>
            <p class="text-xl font-bold text-green-600">{{ statistics.active }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <Clock :size="20" class="text-yellow-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Jarang</p>
            <p class="text-xl font-bold text-yellow-600">{{ statistics.rarely }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-red-100 rounded-lg">
            <XCircle :size="20" class="text-red-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Tidak Aktif</p>
            <p class="text-xl font-bold text-red-600">{{ statistics.inactive }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-gray-100 rounded-lg">
            <AlertCircle :size="20" class="text-gray-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Belum Login</p>
            <p class="text-xl font-bold text-gray-600">{{ statistics.never }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Statistics -->
    <div v-if="statistics?.by_role" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold mb-4">Statistik per Role</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="text-left py-2 px-3">Role</th>
              <th class="text-center py-2 px-3">Total</th>
              <th class="text-center py-2 px-3">🟢 Aktif</th>
              <th class="text-center py-2 px-3">🟡 Jarang</th>
              <th class="text-center py-2 px-3">🔴 Tidak Aktif</th>
              <th class="text-center py-2 px-3">⚫ Belum Login</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(stats, role) in statistics.by_role" :key="role" class="border-b hover:bg-gray-50">
              <td class="py-2 px-3 font-medium capitalize">{{ role }}</td>
              <td class="text-center py-2 px-3">{{ stats.total }}</td>
              <td class="text-center py-2 px-3 text-green-600">{{ stats.active }}</td>
              <td class="text-center py-2 px-3 text-yellow-600">{{ stats.rarely }}</td>
              <td class="text-center py-2 px-3 text-red-600">{{ stats.inactive }}</td>
              <td class="text-center py-2 px-3 text-gray-600">{{ stats.never }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cari User</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Nama atau email..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            @keyup.enter="handleFilter"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select
            v-model="filters.role"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="handleFilter"
          >
            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status Aktivitas</label>
          <select
            v-model="filters.activity_status"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            @change="handleFilter"
          >
            <option v-for="opt in activityOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button
            @click="handleFilter"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Filter
          </button>
          <button
            @click="clearFilters"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <DataTable
      :items="users"
      :columns="tableColumns"
      :loading="loading"
      empty-message="Tidak ada data user"
      :empty-icon="Users"
    >
      <template #cell-roles="{ value }">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="role in value"
            :key="role"
            class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 capitalize"
          >
            {{ role }}
          </span>
        </div>
      </template>

      <template #cell-activity_status="{ value }">
        <span
          :class="['px-2 py-1 text-xs rounded-full', getStatusBadge(value).class]"
        >
          {{ getStatusBadge(value).icon }} {{ getStatusBadge(value).label }}
        </span>
      </template>

      <template #cell-last_login_at="{ value }">
        <span class="text-sm text-gray-600">{{ formatDate(value) }}</span>
      </template>

      <template #cell-branch="{ value }">
        <span class="text-sm">{{ value || "-" }}</span>
      </template>
    </DataTable>
  </div>
</template>
