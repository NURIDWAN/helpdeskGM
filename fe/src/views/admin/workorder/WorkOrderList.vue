<script setup>
import { ref, onMounted } from "vue";
import { useWorkOrderStore } from "@/stores/workOrder";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Plus, Edit, Trash2, ClipboardList, Eye } from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can } from "@/helpers/permissionHelper";

const workOrderStore = useWorkOrderStore();
const { workOrders, meta, loading, error, success } = storeToRefs(workOrderStore);
const { fetchWorkOrdersPaginated, deleteWorkOrder } = workOrderStore;

// Table columns configuration
const tableColumns = [
  { key: "number", label: "Nomor", bold: true, nowrap: true },
  { key: "ticket_info", label: "Ticket", nowrap: true },
  { key: "assigned_user.name", label: "Teknisi", nowrap: true },
  { key: "status", label: "Status", nowrap: true },
  { key: "created_at", label: "Dibuat", nowrap: true },
];

// Reactive data
const searchQuery = ref("");
const showDeleteModal = ref(false);
const workOrderToDelete = ref(null);

// Methods
const fetchWorkOrders = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
  };
  fetchWorkOrdersPaginated(params);
};

const handleSearch = () => {
  meta.value.current_page = 1;
  fetchWorkOrders();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchWorkOrders();
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchWorkOrders();
};

const confirmDelete = (workOrder) => {
  workOrderToDelete.value = workOrder;
  showDeleteModal.value = true;
};

const handleDeleteWorkOrder = async () => {
  if (workOrderToDelete.value) {
    await deleteWorkOrder(workOrderToDelete.value.id);
    if (!error.value) {
      showDeleteModal.value = false;
      workOrderToDelete.value = null;
      fetchWorkOrders();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  workOrderToDelete.value = null;
};

// Lifecycle
onMounted(() => {
  fetchWorkOrders();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Data Surat Perintah Kerja
        </h1>
        <p class="text-gray-600">Kelola data surat perintah kerja</p>
      </div>
      <RouterLink
        v-if="can('work-order-create')"
        :to="{ name: 'admin.workorder.create' }"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <Plus :size="20" class="mr-2" />
        Buat SPK
      </RouterLink>
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
      <div class="flex gap-4">
        <div class="flex-1">
          <SearchInput
            v-model="searchQuery"
            placeholder="Cari surat perintah kerja..."
            :debounce="500"
            @update:modelValue="handleSearch"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :items="workOrders"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data surat perintah kerja"
      :empty-icon="ClipboardList"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-created_at="{ value }">
        <div class="text-sm text-gray-500">
          {{ formatToClientTimezone(value) }}
        </div>
      </template>

      <template #cell-ticket_info="{ item }">
        <div class="text-sm">
          <span v-if="item.ticket" class="text-gray-900">
            {{ item.ticket.code }} - {{ item.ticket.title }}
          </span>
          <span v-else class="text-gray-500 italic"> SPK Standalone </span>
        </div>
      </template>

      <template #cell-status="{ value }">
        <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">{{
          value
        }}</span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <RouterLink
            v-if="can('work-order-list')"
            :to="{ name: 'admin.workorder.detail', params: { id: item.id } }"
            class="text-green-600 hover:text-green-900 p-1"
            title="View Detail"
          >
            <Eye :size="16" />
          </RouterLink>
          <RouterLink
            v-if="can('work-order-edit')"
            :to="{ name: 'admin.workorder.edit', params: { id: item.id } }"
            class="text-blue-600 hover:text-blue-900 p-1"
            title="Edit"
          >
            <Edit :size="16" />
          </RouterLink>
          <button
            v-if="can('work-order-delete')"
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
      :message="`Apakah Anda yakin ingin menghapus surat perintah kerja **${workOrderToDelete?.number}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteWorkOrder"
    />
  </div>
</template>
