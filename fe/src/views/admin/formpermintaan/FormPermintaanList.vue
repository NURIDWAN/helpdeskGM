<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { CheckCircle2, Download, Edit, Eye, FileSpreadsheet, FileText, Filter, Plus, Trash2, X } from "lucide-vue-next";

import Alert from "@/components/common/Alert.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import DataTable from "@/components/common/DataTable.vue";
import SearchInput from "@/components/common/SearchInput.vue";
import { useBranchStore } from "@/stores/branch";
import { useFormPermintaanStore } from "@/stores/formPermintaan";
import { formatDate, formatToClientTimezone } from "@/helpers/format";
import { can } from "@/helpers/permissionHelper";

const router = useRouter();
const formPermintaanStore = useFormPermintaanStore();
const branchStore = useBranchStore();

const { forms, meta, loading, error } = storeToRefs(formPermintaanStore);
const { fetchFormPermintaanPaginated, confirmFormPermintaan, updateFormPermintaanStatus, deleteFormPermintaan } = formPermintaanStore;
const { fetchBranches } = branchStore;

const searchQuery = ref("");
const showFilters = ref(false);
const showConfirmModal = ref(false);
const showDeleteModal = ref(false);
const selectedForm = ref(null);
const confirming = ref(false);
const deleting = ref(false);
const showExportMenu = ref(false);
const exportLoading = ref(false);

const filters = ref({
  branchId: "",
  requestType: "",
  status: "",
  startDate: "",
  endDate: "",
});

const tableColumns = [
  { key: "request_number", label: "No. Permintaan", bold: true, nowrap: true },
  { key: "date", label: "Tanggal", nowrap: true },
  { key: "user.name", label: "Pemohon", nowrap: true },
  { key: "branch.name", label: "Outlet", nowrap: true },
  { key: "request_type", label: "Jenis Permintaan", nowrap: true },
  { key: "priority", label: "Prioritas", nowrap: true },
  { key: "status", label: "Status", nowrap: true },
  { key: "created_at", label: "Dibuat", nowrap: true },
];

const requestTypeLabels = {
  pembelian_produk_baru: "Pembelian Produk Baru",
  penggantian_produk_lama: "Penggantian Produk Lama",
  servis: "Servis",
  penggantian_part: "Penggantian Part",
  jasa: "Jasa",
};

const priorityLabels = {
  low: "Low",
  medium: "Medium",
  high: "High",
  urgent: "Urgent",
};

const statusLabels = {
  progress: "Progress",
  pending: "Pending",
  approved: "Approved",
  rejected: "Rejected",
};

const requestTypeOptions = Object.entries(requestTypeLabels).map(([value, label]) => ({ value, label }));
const statusOptions = Object.entries(statusLabels).map(([value, label]) => ({ value, label }));

const branchOptions = computed(() =>
  branchStore.branches.map((branch) => ({
    value: branch.id,
    label: branch.name,
  }))
);

const fetchData = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
    branch_id: filters.value.branchId,
    request_type: filters.value.requestType,
    status: filters.value.status,
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
  searchQuery.value = "";
  filters.value = {
    branchId: "",
    requestType: "",
    status: "",
    startDate: "",
    endDate: "",
  };
  meta.value.current_page = 1;
  fetchData();
};

const openConfirmModal = (item) => {
  selectedForm.value = item;
  showConfirmModal.value = true;
};

const closeConfirmModal = () => {
  if (confirming.value) return;
  selectedForm.value = null;
  showConfirmModal.value = false;
};

const handleConfirm = async () => {
  if (!selectedForm.value) return;

  confirming.value = true;
  try {
    await confirmFormPermintaan(selectedForm.value.id);
    selectedForm.value = null;
    showConfirmModal.value = false;
    fetchData();
  } finally {
    confirming.value = false;
  }
};

const handleDetail = (item) => {
  router.push({ name: "app.form-permintaan.detail", params: { id: item.id } });
};

const handleEdit = (item) => {
  router.push({ name: "admin.form-permintaan.edit", params: { id: item.id } });
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

const updatingStatusId = ref(null);

const handleStatusChange = async (item, newStatus) => {
  if (newStatus === item.status) return;
  updatingStatusId.value = item.id;
  try {
    await updateFormPermintaanStatus(item.id, newStatus);
    fetchData();
  } finally {
    updatingStatusId.value = null;
  }
};

// Export methods
const buildExportParams = () => {
  const params = {};
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.branchId) params.branch_id = filters.value.branchId;
  if (filters.value.requestType) params.request_type = filters.value.requestType;
  if (filters.value.startDate) params.start_date = filters.value.startDate;
  if (filters.value.endDate) params.end_date = filters.value.endDate;
  if (searchQuery.value) params.search = searchQuery.value;
  return params;
};

const handleExportPdf = async () => {
  showExportMenu.value = false;
  exportLoading.value = true;
  try {
    const { axiosInstance } = await import('@/plugins/axios');
    const response = await axiosInstance.get('/form-permintaan/export/pdf', {
      params: buildExportParams(),
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `form_permintaan_${new Date().toISOString().slice(0, 10)}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    formPermintaanStore.error = 'Gagal export PDF';
  } finally {
    exportLoading.value = false;
  }
};

const handleExportExcel = async () => {
  showExportMenu.value = false;
  exportLoading.value = true;
  try {
    const { axiosInstance } = await import('@/plugins/axios');
    const response = await axiosInstance.get('/form-permintaan/export/excel', {
      params: buildExportParams(),
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `form_permintaan_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    formPermintaanStore.error = 'Gagal export Excel';
  } finally {
    exportLoading.value = false;
  }
};

const closeExportMenu = (event) => {
  const exportMenuEl = document.getElementById('export-menu-container');
  if (exportMenuEl && !exportMenuEl.contains(event.target)) {
    showExportMenu.value = false;
  }
};

watch(showExportMenu, (val) => {
  if (val) {
    setTimeout(() => {
      document.addEventListener('click', closeExportMenu);
    }, 0);
  } else {
    document.removeEventListener('click', closeExportMenu);
  }
});

onMounted(async () => {
  await fetchBranches({ limit: 100 });
  fetchData();
});

onUnmounted(() => {
  document.removeEventListener('click', closeExportMenu);
});

watch(searchQuery, () => {
  meta.value.current_page = 1;
  fetchData();
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Daftar Form Permintaan</h1>
        <p class="text-gray-600">Pantau dan konfirmasi form permintaan dari outlet.</p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Export Dropdown -->
        <div id="export-menu-container" class="relative" v-if="can('form-permintaan-list')">
          <button
            @click="showExportMenu = !showExportMenu"
            :disabled="exportLoading"
            class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <Download :size="18" class="mr-2" />
            Export
            <span v-if="exportLoading" class="ml-2">
              <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
            </span>
          </button>
          <div
            v-if="showExportMenu"
            class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border z-10"
          >
            <button
              @click="handleExportPdf"
              class="w-full flex items-center px-4 py-2 text-left hover:bg-gray-50 text-gray-700 rounded-t-lg"
            >
              <FileText :size="18" class="mr-2 text-red-500" />
              Export PDF
            </button>
            <button
              @click="handleExportExcel"
              class="w-full flex items-center px-4 py-2 text-left hover:bg-gray-50 text-gray-700 rounded-b-lg"
            >
              <FileSpreadsheet :size="18" class="mr-2 text-green-500" />
              Export Excel
            </button>
          </div>
        </div>
        <RouterLink
          v-if="can('form-permintaan-create')"
          :to="{ name: 'admin.form-permintaan.create' }"
          class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <Plus :size="18" class="mr-2" />
          Buat Form Permintaan
        </RouterLink>
        <button
          type="button"
          @click="showFilters = !showFilters"
          class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <Filter :size="18" class="mr-2" />
          Filter
        </button>
      </div>
    </div>

    <Alert
      v-if="error"
      type="error"
      :message="typeof error === 'string' ? error : 'Gagal memuat data form permintaan'"
      :auto-close="true"
      :duration="5000"
      @close="formPermintaanStore.error = null"
    />

    <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
      <SearchInput
        v-model="searchQuery"
        placeholder="Cari nomor permintaan..."
      />

      <div v-if="showFilters" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mt-4 pt-4 border-t border-gray-100">
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
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            @change="handleFilterChange"
          >
            <option value="">Semua Status</option>
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
        <div class="md:col-span-2 xl:col-span-5 flex justify-end">
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
    </div>

    <DataTable
      :items="forms"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      :show-actions="true"
      empty-message="Belum ada form permintaan"
      :empty-icon="FileText"
      storage-key="admin-form-permintaan-table"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-request_number="{ item }">
        <button
          @click="handleDetail(item)"
          class="font-medium text-blue-600 hover:text-blue-800 hover:underline text-left"
        >
          {{ item.request_number }}
        </button>
      </template>

      <template #cell-date="{ value }">
        <span class="text-sm text-gray-700">{{ value ? formatDate(value) : '-' }}</span>
      </template>

      <template #cell-request_type="{ value }">
        <span class="text-sm text-gray-700">{{ requestTypeLabels[value] || value || '-' }}</span>
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

      <template #cell-status="{ value, item }">
        <span
          v-if="value === 'approved' || value === 'rejected' || !can('form-permintaan-confirm')"
          class="px-2 py-0.5 text-xs rounded font-medium"
          :class="{
            'bg-yellow-100 text-yellow-800': value === 'progress',
            'bg-gray-100 text-gray-800': value === 'pending',
            'bg-blue-100 text-blue-800': value === 'approved',
            'bg-red-100 text-red-800': value === 'rejected',
          }"
        >
          {{ statusLabels[value] || (value ? value.charAt(0).toUpperCase() + value.slice(1) : '-') }}
        </span>
        <select
          v-else
          :value="value"
          :disabled="updatingStatusId === item.id"
          class="px-2 py-1 text-xs font-medium rounded border focus:outline-none focus:ring-1 focus:ring-blue-500"
          :class="{
            'bg-yellow-50 text-yellow-800 border-yellow-200': value === 'progress',
            'bg-gray-50 text-gray-800 border-gray-200': value === 'pending',
            'opacity-50': updatingStatusId === item.id,
          }"
          @change="handleStatusChange(item, $event.target.value)"
        >
          <option value="progress">Progress</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </template>

      <template #cell-created_at="{ value }">
        <span class="text-sm text-gray-700">{{ value ? formatToClientTimezone(value) : '-' }}</span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="handleDetail(item)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-100 rounded-md hover:bg-gray-100"
            title="Lihat detail form permintaan"
          >
            <Eye :size="14" />
            Detail
          </button>
          <button
            v-if="can('form-permintaan-edit')"
            type="button"
            @click="handleEdit(item)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-100 rounded-md hover:bg-blue-100"
            title="Edit form permintaan"
          >
            <Edit :size="14" />
            Edit
          </button>
          <button
            v-if="can('form-permintaan-delete')"
            type="button"
            @click="openDeleteModal(item)"
            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-100 rounded-md hover:bg-red-100"
            title="Hapus form permintaan"
          >
            <Trash2 :size="14" />
            Hapus
          </button>
        </div>
      </template>
    </DataTable>

    <ConfirmationModal
      :show="showConfirmModal"
      title="Konfirmasi Form Permintaan"
      :message="`Konfirmasi form permintaan ${selectedForm?.request_number || ''}?`"
      subtitle="Status form akan berubah menjadi approved."
      type="success"
      confirm-text="Ya, Konfirmasi"
      cancel-text="Batal"
      loading-text="Mengonfirmasi..."
      :loading="confirming"
      @close="closeConfirmModal"
      @confirm="handleConfirm"
    />

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
