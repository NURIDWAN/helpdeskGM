<script setup>
import { computed, onMounted, ref } from "vue";
import { useFormPermintaanStore } from "@/stores/formPermintaan";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";

import DataTable from "@/components/common/DataTable.vue";
import Alert from "@/components/common/Alert.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Edit, FileText, Filter, Plus, Trash2, X } from "lucide-vue-next";
import { formatDate } from "@/helpers/format";
import { can } from "@/helpers/permissionHelper";

const router = useRouter();
const formPermintaanStore = useFormPermintaanStore();
const authStore = useAuthStore();
const { forms, meta, loading, error } = storeToRefs(formPermintaanStore);
const { user } = storeToRefs(authStore);
const { deleteFormPermintaan, fetchFormPermintaanPaginated, downloadPDF } = formPermintaanStore;

const handleDownloadPDF = async (item) => {
  await downloadPDF(item.id, item.request_number);
};
const showDeleteModal = ref(false);
const selectedForm = ref(null);
const deleting = ref(false);
const showFilters = ref(false);

const filters = ref({
  branchId: "",
  requestType: "",
  startDate: "",
  endDate: "",
});

// Table columns configuration
const tableColumns = [
  { key: "request_number", label: "No. Permintaan", bold: true, nowrap: true },
  { key: "date", label: "Tanggal", nowrap: true },
  { key: "branch.name", label: "Outlet", nowrap: true },
  { key: "request_type", label: "Jenis Permintaan", nowrap: true },
  { key: "priority", label: "Prioritas", nowrap: true },
  { key: "status", label: "Status", nowrap: true },
];

// Request type label mapping
const requestTypeLabels = {
  pembelian_produk_baru: "Pembelian Produk Baru",
  penggantian_produk_lama: "Penggantian Produk Lama",
  servis: "Servis",
  penggantian_part: "Penggantian Part",
  jasa: "Jasa",
};

const requestTypeOptions = Object.entries(requestTypeLabels).map(([value, label]) => ({
  value,
  label,
}));

// Priority label mapping
const priorityLabels = {
  low: "Low",
  medium: "Medium",
  high: "High",
  urgent: "Urgent",
};

// Status label mapping
const statusLabels = {
  progress: "Progress",
  pending: "Pending",
  approved: "Approved",
  rejected: "Rejected",
  completed: "Completed",
};

const branchOptions = computed(() => {
  const branchMap = new Map();

  if (user.value?.branch_id || user.value?.branch?.id) {
    const id = user.value.branch_id || user.value.branch.id;
    branchMap.set(String(id), user.value.branch?.name || "Outlet Saya");
  }

  forms.value.forEach((item) => {
    if (item.branch?.id) {
      branchMap.set(String(item.branch.id), item.branch.name);
    }
  });

  return Array.from(branchMap, ([value, label]) => ({ value, label }));
});

// Methods
const fetchData = () => {
  const params = {
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
    branch_id: filters.value.branchId,
    request_type: filters.value.requestType,
    start_date: filters.value.startDate,
    end_date: filters.value.endDate,
  };

  Object.keys(params).forEach((key) => {
    if (params[key] === null || params[key] === undefined || params[key] === "") {
      delete params[key];
    }
  });

  fetchFormPermintaanPaginated(params);
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchData();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchData();
};

const handleFilterChange = () => {
  meta.value.current_page = 1;
  fetchData();
};

const clearFilters = () => {
  filters.value = {
    branchId: "",
    requestType: "",
    startDate: "",
    endDate: "",
  };
  meta.value.current_page = 1;
  fetchData();
};

const handleRowClick = (item) => {
  router.push({ name: "app.form-permintaan.detail", params: { id: item.id } });
};

const handleEdit = (item) => {
  router.push({ name: "app.form-permintaan.edit", params: { id: item.id } });
};

const openDeleteModal = (item) => {
  selectedForm.value = item;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  if (deleting.value) return;
  selectedForm.value = null;
  showDeleteModal.value = false;
};

const handleDelete = async () => {
  if (!selectedForm.value) return;

  deleting.value = true;
  try {
    await deleteFormPermintaan(selectedForm.value.id);
    selectedForm.value = null;
    showDeleteModal.value = false;
    fetchData();
  } finally {
    deleting.value = false;
  }
};

// Lifecycle
onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Form Permintaan</h1>
        <p class="text-gray-600">Daftar form permintaan Anda</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="showFilters = !showFilters"
          class="inline-flex items-center px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <Filter :size="18" class="mr-2" />
          Filter
        </button>
        <RouterLink
          v-if="can('form-permintaan-create')"
          :to="{ name: 'app.form-permintaan.create' }"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <Plus :size="20" class="mr-2" />
          Buat Form Permintaan
        </RouterLink>
      </div>
    </div>

    <!-- Alert -->
    <Alert
      v-if="error"
      type="error"
      :message="typeof error === 'string' ? error : 'Gagal memuat data form permintaan'"
      :auto-close="true"
      :duration="5000"
      @close="formPermintaanStore.error = null"
    />

    <!-- Filters -->
    <div v-if="showFilters" class="bg-white rounded-lg shadow border border-gray-100 p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
          <input
            v-model="filters.startDate"
            type="date"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            @change="handleFilterChange"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
          <input
            v-model="filters.endDate"
            type="date"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            @change="handleFilterChange"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
          <select
            v-model="filters.branchId"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            @change="handleFilterChange"
          >
            <option value="">Semua Outlet</option>
            <option v-for="branch in branchOptions" :key="branch.value" :value="branch.value">
              {{ branch.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Permintaan</label>
          <select
            v-model="filters.requestType"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            @change="handleFilterChange"
          >
            <option value="">Semua Jenis</option>
            <option v-for="option in requestTypeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="flex justify-end mt-4">
        <button
          type="button"
          @click="clearFilters"
          class="inline-flex items-center px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50"
        >
          <X :size="16" class="mr-1" />
          Reset Filter
        </button>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :items="forms"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      :show-actions="true"
      empty-message="Belum ada form permintaan"
      :empty-icon="FileText"
      storage-key="form-permintaan-list-table"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-request_number="{ item }">
        <button
          @click="handleRowClick(item)"
          class="font-medium text-blue-600 hover:text-blue-800 hover:underline text-left"
        >
          {{ item.request_number }}
        </button>
      </template>

      <template #cell-date="{ value }">
        <span class="text-sm text-gray-700">
          {{ value ? formatDate(value) : '-' }}
        </span>
      </template>

      <template #cell-request_type="{ value }">
        <span class="text-sm text-gray-700">
          {{ requestTypeLabels[value] || value || '-' }}
        </span>
      </template>

      <template #cell-priority="{ value }">
        <span
          class="px-2 py-0.5 text-xs rounded font-medium"
          :class="{
            'bg-green-100 text-green-800': value === 'low',
            'bg-yellow-100 text-yellow-800': value === 'medium',
            'bg-orange-100 text-orange-800': value === 'high',
            'bg-red-100 text-red-800': value === 'urgent',
          }"
        >
          {{ priorityLabels[value] || value || '-' }}
        </span>
      </template>

      <template #cell-status="{ value }">
        <span
          class="px-2 py-0.5 text-xs rounded font-medium"
          :class="{
            'bg-yellow-100 text-yellow-800': value === 'progress',
            'bg-gray-100 text-gray-800': value === 'pending',
            'bg-blue-100 text-blue-800': value === 'approved',
            'bg-red-100 text-red-800': value === 'rejected',
            'bg-green-100 text-green-800': value === 'completed',
          }"
        >
          {{ statusLabels[value] || (value ? value.charAt(0).toUpperCase() + value.slice(1) : '-') }}
        </span>
      </template>

      <template #actions="{ item }">
        <div v-if="item.status === 'pending'" class="flex justify-end gap-2">
          <button
            type="button"
            @click="handleEdit(item)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-100 rounded-md hover:bg-blue-100"
            title="Edit form permintaan"
          >
            <Edit :size="14" />
            Edit
          </button>
          <button
            type="button"
            @click="openDeleteModal(item)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-100 rounded-md hover:bg-red-100"
            title="Hapus form permintaan"
          >
            <Trash2 :size="14" />
            Hapus
          </button>
        </div>
        <div v-else class="flex justify-end items-center gap-2">
          <button
            v-if="item.status === 'approved' || item.status === 'completed'"
            type="button"
            @click="handleDownloadPDF(item)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-100 rounded-md hover:bg-green-100"
            title="Cetak PDF"
          >
            <FileText :size="14" />
            Cetak PDF
          </button>
          <span class="text-xs text-gray-400">Terkunci</span>
        </div>
      </template>
    </DataTable>

    <ConfirmationModal
      :show="showDeleteModal"
      title="Hapus Form Permintaan"
      :message="`Hapus form permintaan ${selectedForm?.request_number || ''}? Data item dan lampiran terkait akan ikut dihapus.`"
      subtitle="Aksi ini tidak bisa dibatalkan."
      type="danger"
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="deleting"
      @close="closeDeleteModal"
      @confirm="handleDelete"
    />
  </div>
</template>
