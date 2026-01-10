<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoleStore } from "@/stores/role";
import { featureGroups, colorMap, countSelectedByFeature } from "@/config/permissionConfig";
import SearchInput from "@/components/common/SearchInput.vue";
import DataTable from "@/components/common/DataTable.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import { 
  Plus, 
  Edit, 
  Trash2, 
  Shield, 
  Eye, 
  Copy,
  Users,
  BarChart3,
  Tag,
  ClipboardList,
  FileText,
  Calendar,
  Zap,
  Building,
  Activity
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { can } from "@/helpers/permissionHelper";

// Icon map for feature icons
const featureIconMap = {
  dashboard: BarChart3,
  user: Users,
  role: Shield,
  branch: Building,
  ticket: Tag,
  workOrder: ClipboardList,
  workReport: FileText,
  dailyRecord: Calendar,
  electricity: Zap,
  userActivity: Activity
};

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
  { key: "permissions_summary", label: "Permission", nowrap: false },
  { key: "guard_name", label: "Guard", nowrap: true },
];

// Process roles to add permission summary
const processedRoles = computed(() => {
  return roles.value.map(role => {
    const permissions = role.permissions || [];
    const counts = countSelectedByFeature(permissions);
    
    // Get top features with permissions
    const featureSummary = Object.entries(counts)
      .filter(([, c]) => c.selected > 0)
      .sort((a, b) => b[1].selected - a[1].selected)
      .slice(0, 5)
      .map(([key, c]) => ({
        key,
        label: featureGroups[key]?.label || key,
        icon: featureIconMap[key] || Shield,
        color: featureGroups[key]?.color || 'gray',
        count: c.selected,
        total: c.total
      }));

    return {
      ...role,
      featureSummary,
      totalPermissions: permissions.length
    };
  });
});

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
  return ["admin", "staff", "user", "superadmin"].includes(roleName);
};

const getColorClasses = (colorName) => {
  return colorMap[colorName] || colorMap.blue;
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
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
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
      :items="processedRoles"
      :columns="tableColumns"
      :loading="loading"
      empty-message="Belum ada data role"
      :empty-icon="Shield"
    >
      <!-- Role Name Column -->
      <template #cell-name="{ value, item }">
        <div class="flex items-center gap-2">
          <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
            <Shield :size="16" class="text-white" />
          </div>
          <div>
            <span class="font-medium text-gray-900">{{ value }}</span>
            <div class="flex items-center gap-2 mt-0.5">
              <span
                v-if="isProtectedRole(item.name)"
                class="px-2 py-0.5 text-xs font-medium rounded bg-amber-100 text-amber-800"
              >
                Sistem
              </span>
              <span
                v-else
                class="px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800"
              >
                Custom
              </span>
            </div>
          </div>
        </div>
      </template>

      <!-- Permission Summary Column -->
      <template #cell-permissions_summary="{ item }">
        <div class="min-w-0">
          <!-- Feature Badges -->
          <div class="flex flex-wrap gap-1.5 mb-2">
            <div
              v-for="feature in item.featureSummary"
              :key="feature.key"
              class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium"
              :class="[getColorClasses(feature.color).bg, getColorClasses(feature.color).text]"
              :title="`${feature.label}: ${feature.count}/${feature.total} permission`"
            >
              <component :is="feature.icon" :size="12" />
              <span>{{ feature.count }}</span>
            </div>
          </div>
          
          <!-- Total Count -->
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <span class="font-medium">Total:</span>
            <span class="px-2 py-0.5 bg-gray-100 rounded-full text-gray-800 font-semibold">
              {{ item.totalPermissions }}
            </span>
            <span>permission</span>
          </div>
        </div>
      </template>

      <!-- Guard Column -->
      <template #cell-guard_name="{ value }">
        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
          {{ value }}
        </span>
      </template>

      <!-- Actions Column -->
      <template #actions="{ item }">
        <div class="flex justify-end gap-1">
          <!-- View -->
          <RouterLink
            v-if="can('role-list')"
            :to="{ name: 'admin.role.edit', params: { id: item.id } }"
            class="p-2 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
            title="Lihat Detail"
          >
            <Eye :size="16" />
          </RouterLink>
          
          <!-- Edit -->
          <RouterLink
            v-if="can('role-edit')"
            :to="{ name: 'admin.role.edit', params: { id: item.id } }"
            class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
            title="Edit Role"
          >
            <Edit :size="16" />
          </RouterLink>
          
          <!-- Duplicate (only for non-protected roles) -->
          <RouterLink
            v-if="can('role-create') && !isProtectedRole(item.name)"
            :to="{ name: 'admin.role.create', query: { duplicate: item.id } }"
            class="p-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
            title="Duplikat Role"
          >
            <Copy :size="16" />
          </RouterLink>
          
          <!-- Delete -->
          <button
            v-if="can('role-delete') && !isProtectedRole(item.name)"
            @click="confirmDelete(item)"
            class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            title="Hapus Role"
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
      subtitle="Tindakan ini tidak dapat dibatalkan. Pastikan tidak ada user yang menggunakan role ini."
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
