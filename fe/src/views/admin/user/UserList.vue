<script setup>
import { ref, onMounted } from "vue";
import { useUserStore } from "@/stores/user";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Plus, Edit, Trash2, Users } from "lucide-vue-next";
import { formatToClientTimezone } from "@/helpers/format";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";

const userStore = useUserStore();
const { users, meta, loading, success, error } = storeToRefs(userStore);
const { fetchUsersPaginated, deleteUser } = userStore;

// Table columns configuration
const tableColumns = [
  {
    key: "name",
    label: "Nama",
    bold: true,
    nowrap: true,
  },
  {
    key: "email",
    label: "Email",
    nowrap: true,
  },
  {
    key: "position",
    label: "Jabatan",
  },
  {
    key: "phone_number",
    label: "Telepon",
    nowrap: true,
  },
  {
    key: "branch.name",
    label: "Cabang",
  },
  {
    key: "roles",
    label: "Roles",
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
const userToDelete = ref(null);

// Methods
const fetchUsers = () => {
  const params = {
    search: searchQuery.value,
    row_per_page: meta.value.per_page || 10,
    page: meta.value.current_page || 1,
  };
  fetchUsersPaginated(params);
};

const handleSearch = () => {
  meta.value.current_page = 1;
  fetchUsers();
};

const handlePerPageChange = (newPerPage) => {
  meta.value.per_page = newPerPage;
  meta.value.current_page = 1;
  fetchUsers();
};

const handlePageChange = (page) => {
  meta.value.current_page = page;
  fetchUsers();
};

const confirmDelete = (user) => {
  userToDelete.value = user;
  showDeleteModal.value = true;
};

const handleDeleteUser = async () => {
  if (userToDelete.value) {
    await deleteUser(userToDelete.value.id);
    if (success.value) {
      showDeleteModal.value = false;
      userToDelete.value = null;
      fetchUsers();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  userToDelete.value = null;
};

// Lifecycle
onMounted(() => {
  fetchUsers();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data User</h1>
        <p class="text-gray-600">Kelola data user perusahaan</p>
      </div>
      <RouterLink
        :to="{ name: 'admin.user.create' }"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <Plus :size="20" class="mr-2" />
        Tambah User
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
            placeholder="Cari user..."
            :debounce="500"
            @update:modelValue="handleSearch"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :items="users"
      :columns="tableColumns"
      :loading="loading"
      :meta="meta"
      empty-message="Belum ada data user"
      :empty-icon="Users"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    >
      <template #cell-phone_number="{ value }">
        <div class="text-sm text-gray-700">
          {{ value || "-" }}
        </div>
      </template>

      <template #cell-roles="{ item }">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="role in Array.isArray(item.roles) ? item.roles : []"
            :key="role?.name ?? role"
            class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700"
          >
            {{ role?.name ?? role }}
          </span>
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
              name: 'admin.user.edit',
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
      :message="`Apakah Anda yakin ingin menghapus user **${userToDelete?.name}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteUser"
    />
  </div>
</template>
