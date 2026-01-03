<script setup>
import { ref, onMounted } from "vue";
import { useRoleStore } from "@/stores/role";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { Plus, Edit, Trash2, Shield, Eye } from "lucide-vue-next";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can } from "@/helpers/permissionHelper";

const roleStore = useRoleStore();
const { roles, loading, success, error } = storeToRefs(roleStore);
const { fetchRoles, deleteRole } = roleStore;

// Reactive data
const searchQuery = ref("");
const showDeleteModal = ref(false);
const roleToDelete = ref(null);

// Table columns configuration
const tableColumns = [
  { key: "name", label: "Nama Role", bold: true, nowrap: true },
  { key: "permissions_count", label: "Jumlah Permission", nowrap: true },
  { key: "guard_name", label: "Guard", nowrap: true },
];

// Methods
const handleSearch = () => {
  fetchRoles({ search: searchQuery.value });
};

const loadRoles = () => {
  fetchRoles({ search: searchQuery.value });
};

const confirmDelete = (role) => {
  roleToDelete.value = role;
  showDeleteModal.value = true;
};

const handleDeleteRole = async () => {
  if (roleToDelete.value) {
    try {
      await deleteRole(roleToDelete.value.id);
      showDeleteModal.value = false;
      roleToDelete.value = null;
    } catch (e) {
      // Error handled by store
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  roleToDelete.value = null;
};

const isProtectedRole = (roleName) => {
  return ["admin", "staff", "user"].includes(roleName);
};

// Lifecycle
onMounted(() => {
  loadRoles();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Role</h1>
        <p class="text-gray-600">Kelola role dan permission pengguna</p>
      </div>
      <RouterLink
        v-if="can('role-create')"
        :to="{ name: 'admin.role.create' }"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <Plus :size="20" class="mr-2" />
        Tambah Role
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

    <!-- Search -->
    <div class="bg-white p-4 rounded-lg shadow">
      <SearchInput
        v-model="searchQuery"
        placeholder="Cari role..."
        :debounce="500"
        @update:modelValue="handleSearch"
      />
    </div>

    <!-- Table -->
    <DataTable
      :items="roles"
      :columns="tableColumns"
      :loading="loading"
      empty-message="Belum ada data role"
      :empty-icon="Shield"
    >
      <template #cell-name="{ value, item }">
        <div class="flex items-center gap-2">
          <span class="font-medium text-gray-900">{{ value }}</span>
          <span
            v-if="isProtectedRole(item.name)"
            class="px-2 py-0.5 text-xs rounded bg-yellow-100 text-yellow-800"
          >
            Sistem
          </span>
        </div>
      </template>

      <template #cell-permissions_count="{ value }">
        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
          {{ value }} permission
        </span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <RouterLink
            v-if="can('role-list')"
            :to="{ name: 'admin.role.edit', params: { id: item.id } }"
            class="text-green-600 hover:text-green-900 p-1"
            title="View/Edit"
          >
            <Eye :size="16" />
          </RouterLink>
          <RouterLink
            v-if="can('role-edit')"
            :to="{ name: 'admin.role.edit', params: { id: item.id } }"
            class="text-blue-600 hover:text-blue-900 p-1"
            title="Edit"
          >
            <Edit :size="16" />
          </RouterLink>
          <button
            v-if="can('role-delete') && !isProtectedRole(item.name)"
            @click="confirmDelete(item)"
            class="text-red-600 hover:text-red-900 p-1"
            title="Delete"
          >
            <Trash2 :size="16" />
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Delete Modal -->
    <ConfirmationModal
      :show="showDeleteModal"
      title="Konfirmasi Hapus"
      :message="`Apakah Anda yakin ingin menghapus role **${roleToDelete?.name}**?`"
      subtitle="Tindakan ini tidak dapat dibatalkan."
      confirm-text="Hapus"
      cancel-text="Batal"
      loading-text="Menghapus..."
      :loading="loading"
      type="danger"
      @close="closeDeleteModal"
      @confirm="handleDeleteRole"
    />
  </div>
</template>
