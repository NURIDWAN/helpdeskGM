<script setup>
import { ref, onMounted } from "vue";
import { useWorkOrderStore } from "@/stores/workOrder";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Plus, Edit, Trash2, ClipboardList, Eye, PlayCircle, CheckCircle } from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can, hasRole } from "@/helpers/permissionHelper";
import { useToast } from "vue-toastification";

const toast = useToast();
const workOrderStore = useWorkOrderStore();
const { workOrders, meta, loading, error, success } = storeToRefs(workOrderStore);
const { fetchWorkOrdersPaginated, deleteWorkOrder, updateWorkOrder } = workOrderStore;

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

// Confirm SPK (update status to in_progress)
const confirmingId = ref(null);
const handleConfirmSPK = async (workOrder) => {
  confirmingId.value = workOrder.id;
  try {
    await updateWorkOrder(workOrder.id, { status: 'in_progress' });
    toast.success(`SPK ${workOrder.number} berhasil dikonfirmasi`);
    fetchWorkOrders();
  } catch (e) {
    toast.error('Gagal mengkonfirmasi SPK');
  } finally {
    confirmingId.value = null;
  }
};

// Mark SPK as Done
const handleCompleteSPK = async (workOrder) => {
  confirmingId.value = workOrder.id;
  try {
    await updateWorkOrder(workOrder.id, { status: 'done' });
    toast.success(`SPK ${workOrder.number} berhasil diselesaikan`);
    fetchWorkOrders();
  } catch (e) {
    toast.error('Gagal menyelesaikan SPK');
  } finally {
    confirmingId.value = null;
  }
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
        <div v-if="item.ticket" class="flex flex-col gap-1 py-1">
          <div class="flex items-center gap-2">
            <span class="font-medium text-gray-900 text-sm">{{ item.ticket.code }}</span>
            <span v-if="item.ticket.category" 
              class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 border border-gray-200">
              {{ item.ticket.category.name }}
            </span>
          </div>
          <p class="text-xs text-gray-500 line-clamp-2 max-w-sm">
            {{ item.ticket.title || item.ticket.description || '-' }}
          </p>
        </div>
        <span v-else class="text-gray-500 italic text-sm"> SPK Standalone </span>
      </template>

      <template #cell-status="{ value }">
        <span 
          class="px-2 py-0.5 text-xs rounded font-medium"
          :class="{
            'bg-yellow-100 text-yellow-800': value === 'pending',
            'bg-blue-100 text-blue-800': value === 'in_progress',
            'bg-green-100 text-green-800': value === 'done'
          }"
        >
          {{ value === 'pending' ? 'Pending' : value === 'in_progress' ? 'In Progress' : 'Done' }}
        </span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <!-- Konfirmasi Button (Pending -> In Progress) -->
          <button
            v-if="item.status === 'pending' && can('work-order-update-status') && hasRole('staff')"
            @click="handleConfirmSPK(item)"
            :disabled="confirmingId === item.id"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600 disabled:opacity-50"
            title="Konfirmasi & Mulai Kerja"
          >
            <PlayCircle :size="14" />
            Konfirmasi
          </button>
          
          <!-- Selesai Button (In Progress -> Done) -->
          <button
            v-if="item.status === 'in_progress' && can('work-order-update-status') && hasRole('staff')"
            @click="handleCompleteSPK(item)"
            :disabled="confirmingId === item.id"
            class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
            title="Tandai Selesai"
          >
            <CheckCircle :size="14" />
            Selesai
          </button>
          
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
