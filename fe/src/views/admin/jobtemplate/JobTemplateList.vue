<script setup>
import { ref, onMounted } from "vue";
import { useJobTemplateStore } from "@/stores/jobTemplate";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Plus, Edit, Trash2, FileText, Building } from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";

const jobTemplateStore = useJobTemplateStore();
const { jobTemplates, meta, loading, success, error, frequencies } =
  storeToRefs(jobTemplateStore);
const { fetchJobTemplatesPaginated, deleteJobTemplate } = jobTemplateStore;

// Table columns configuration
const tableColumns = [
  {
    key: "name",
    label: "Nama Template",
    bold: true,
    nowrap: true,
  },
  {
    key: "description",
    label: "Deskripsi",
  },
  {
    key: "frequency",
    label: "Frekuensi",
    nowrap: true,
  },
  {
    key: "is_active",
    label: "Status",
    nowrap: true,
  },
  {
    key: "branches",
    label: "Cabang",
    nowrap: true,
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
const jobTemplateToDelete = ref(null);

// Methods
const fetchJobTemplates = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: 10,
    page: 1,
  };
  fetchJobTemplatesPaginated(params);
};

const handleSearch = () => {
  meta.current_page = 1;
  fetchJobTemplates();
};

const handlePerPageChange = (newPerPage) => {
  meta.per_page = newPerPage;
  meta.current_page = 1;
  fetchJobTemplates();
};

const handlePageChange = (page) => {
  meta.current_page = page;
  fetchJobTemplates();
};

const confirmDelete = (jobTemplate) => {
  jobTemplateToDelete.value = jobTemplate;
  showDeleteModal.value = true;
};

const handleDeleteJobTemplate = async () => {
  if (jobTemplateToDelete.value) {
    await deleteJobTemplate(jobTemplateToDelete.value.id);
    if (success.value) {
      showDeleteModal.value = false;
      jobTemplateToDelete.value = null;
      fetchJobTemplates();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  jobTemplateToDelete.value = null;
};

const getFrequencyLabel = (value) => {
  const frequency = frequencies.value.find((f) => f.value === value);
  return frequency ? frequency.label : value;
};

// Lifecycle
onMounted(() => {
  fetchJobTemplates();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Template Job</h1>
        <p class="text-gray-600">Kelola template pekerjaan untuk cabang</p>
      </div>
      <RouterLink
        :to="{ name: 'admin.job-template.create' }"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <Plus :size="20" class="mr-2" />
        Tambah Template
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
            placeholder="Cari template job..."
            :debounce="500"
            @update:modelValue="handleSearch"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :items="jobTemplates"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data template job"
      :empty-icon="FileText"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-description="{ value }">
        <div class="max-w-xs truncate" :title="value">
          {{ value }}
        </div>
      </template>

      <template #cell-frequency="{ value }">
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          {{ getFrequencyLabel(value) }}
        </span>
      </template>

      <template #cell-is_active="{ value }">
        <span
          :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
          ]"
        >
          {{ value ? "Aktif" : "Tidak Aktif" }}
        </span>
      </template>

      <template #cell-branches="{ item }">
        <div
          v-if="item.branches && item.branches.length > 0"
          class="flex flex-wrap gap-1"
        >
          <span
            v-for="branch in item.branches.slice(0, 2)"
            :key="branch.id"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800"
          >
            <Building :size="12" class="mr-1" />
            {{ branch.name }}
          </span>
          <span
            v-if="item.branches.length > 2"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800"
          >
            +{{ item.branches.length - 2 }} lagi
          </span>
        </div>
        <span v-else class="text-gray-400 text-sm">Belum ada cabang</span>
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
              name: 'admin.job-template.edit',
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
      :message="`Apakah Anda yakin ingin menghapus template job **${jobTemplateToDelete?.name}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteJobTemplate"
    />
  </div>
</template>
