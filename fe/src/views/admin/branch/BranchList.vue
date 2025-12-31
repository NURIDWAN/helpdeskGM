<script setup>
import { ref, onMounted } from "vue";
import { useBranchStore } from "@/stores/branch";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Plus, Edit, Trash2, Building } from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";

const branchStore = useBranchStore();
const { branches, meta, loading, success, error } = storeToRefs(branchStore);
const { fetchBranchesPaginated, deleteBranch } = branchStore;

// Table columns configuration
const tableColumns = [
  {
    key: "logo",
    label: "Logo",
    nowrap: true,
  },
  {
    key: "name",
    label: "Nama Cabang",
    bold: true,
    nowrap: true,
  },
  {
    key: "address",
    label: "Alamat",
  },
  {
    key: "created_at",
    label: "Dibuat",
    nowrap: true,
  },
];

// Reactive data
const searchQuery = ref("");
const showDeleteModal = ref(false);
const branchToDelete = ref(null);

// Methods
const fetchBranches = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
  };
  fetchBranchesPaginated(params);
};

const handleSearch = () => {
  meta.value.current_page = 1;
  fetchBranches();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchBranches();
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchBranches();
};

const confirmDelete = (branch) => {
  branchToDelete.value = branch;
  showDeleteModal.value = true;
};

const handleDeleteBranch = async () => {
  if (branchToDelete.value) {
    await deleteBranch(branchToDelete.value.id);
    if (success.value) {
      showDeleteModal.value = false;
      branchToDelete.value = null;
      fetchBranches();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  branchToDelete.value = null;
};

// Lifecycle
onMounted(() => {
  fetchBranches();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Cabang</h1>
        <p class="text-gray-600">Kelola data cabang perusahaan</p>
      </div>
      <RouterLink
        :to="{ name: 'admin.branch.create' }"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <Plus :size="20" class="mr-2" />
        Tambah Cabang
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
            placeholder="Cari cabang..."
            :debounce="500"
            @update:modelValue="handleSearch"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :items="branches"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data cabang"
      :empty-icon="Building"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-logo="{ item }">
        <div class="flex items-center justify-center">
          <div v-if="item.logo" class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200">
            <img
              :src="item.logo"
              :alt="item.name + ' logo'"
              class="w-full h-full object-cover"
            />
          </div>
          <div v-else class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
            <Building :size="20" class="text-gray-400" />
          </div>
        </div>
      </template>

      <template #cell-created_at="{ value }">
        <div class="text-sm text-gray-500">
          {{ formatToClientTimezone(value) }}
        </div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <RouterLink
            :to="{
              name: 'admin.branch.edit',
              params: { id: item.id },
            }"
            class="text-blue-600 hover:text-blue-900 p-1"
          >
            <Edit :size="16" />
          </RouterLink>
          <button
            @click="confirmDelete(item)"
            class="text-red-600 hover:text-red-900 p-1"
          >
            <Trash2 :size="16" />
          </button>
        </div>
      </template>
    </DataTable>

    <ConfirmationModal
      :show="showDeleteModal"
      title="Konfirmasi Hapus"
      :message="`Apakah Anda yakin ingin menghapus cabang **${branchToDelete?.name}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteBranch"
    />
  </div>
</template>
