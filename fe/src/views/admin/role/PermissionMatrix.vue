<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoleStore } from "@/stores/role";
import { featureGroups } from "@/config/permissionConfig";
import PermissionMatrixCell from "@/components/admin/PermissionMatrixCell.vue";
import { storeToRefs } from "pinia";
import {
  Loader2,
  AlertCircle,
  RefreshCw,
  X,
  CheckCircle2,
  MinusCircle,
  ArrowLeft,
  Grid3X3,
} from "lucide-vue-next";

const roleStore = useRoleStore();
const { matrix, loading, error } = storeToRefs(roleStore);

// Detail popover state
const showDetail = ref(false);
const detailData = ref(null);

// Load matrix data
const loadMatrix = async () => {
  try {
    await roleStore.fetchMatrix();
  } catch {
    // Error handled by store
  }
};

// Get all permissions for a feature from the config
const getFeaturePermissionList = (featureKey) => {
  const feature = featureGroups[featureKey];
  if (!feature) return [];

  const permissions = [];

  if (feature.permissions) {
    Object.entries(feature.permissions).forEach(([name, def]) => {
      permissions.push({ name, label: def.label, description: def.description });
    });
  }

  if (feature.subFeatures) {
    Object.entries(feature.subFeatures).forEach(([, sub]) => {
      if (sub.permissions) {
        Object.entries(sub.permissions).forEach(([name, def]) => {
          permissions.push({ name, label: def.label, description: def.description, subFeature: sub.label });
        });
      }
    });
  }

  return permissions;
};

// Handle cell click — show detail modal
const handleCellClick = ({ roleName, featureKey }) => {
  const permissions = getFeaturePermissionList(featureKey);
  const roleData = matrix.value?.matrix?.[roleName]?.[featureKey];

  // We need to determine which permissions are active for this role+feature.
  // The matrix endpoint gives us counts but not individual permission status.
  // We'll show the permission list with status based on what we know from the matrix data.
  // For a full detail, we'd need per-permission data. Since this is read-only and the
  // API provides summary data, we show the list with count info.
  detailData.value = {
    roleName,
    featureKey,
    featureLabel: featureGroups[featureKey]?.label || featureKey,
    permissions,
    selected: roleData?.selected || 0,
    total: roleData?.total || 0,
    status: roleData?.status || "empty",
  };
  showDetail.value = true;
};

const closeDetail = () => {
  showDetail.value = false;
  detailData.value = null;
};

// Computed: features from matrix response (fallback to config keys)
const features = computed(() => {
  if (matrix.value?.features) {
    return matrix.value.features;
  }
  // Fallback: derive from featureGroups config
  return Object.entries(featureGroups).map(([key, fg]) => ({
    key,
    label: fg.label,
  }));
});

// Computed: roles from matrix response
const roles = computed(() => {
  return matrix.value?.roles || [];
});

// Get cell data for a role+feature combination
const getCellData = (roleName, featureKey) => {
  return matrix.value?.matrix?.[roleName]?.[featureKey] || { selected: 0, total: 0, status: "empty" };
};

onMounted(() => {
  loadMatrix();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Permission Matrix</h1>
        <p class="text-gray-600">Perbandingan hak akses antar role dalam bentuk matriks</p>
      </div>
      <RouterLink
        :to="{ name: 'admin.roles' }"
        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
      >
        <ArrowLeft :size="18" class="mr-2" />
        Kembali ke Daftar Role
      </RouterLink>
    </div>

    <!-- Loading State -->
    <div
      v-if="loading"
      class="flex flex-col items-center justify-center py-16 bg-white rounded-lg shadow"
    >
      <Loader2 :size="40" class="animate-spin text-blue-500 mb-4" />
      <p class="text-gray-600">Memuat data matrix...</p>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error && !matrix"
      class="flex flex-col items-center justify-center py-16 bg-white rounded-lg shadow"
    >
      <AlertCircle :size="48" class="text-red-400 mb-4" />
      <p class="text-gray-900 font-medium mb-2">Gagal memuat data</p>
      <p class="text-gray-600 text-sm mb-4">{{ error }}</p>
      <button
        @click="loadMatrix"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
      >
        <RefreshCw :size="16" class="mr-2" />
        Coba Lagi
      </button>
    </div>

    <!-- Matrix Table -->
    <div
      v-else-if="matrix"
      class="bg-white rounded-lg shadow overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th
                class="px-4 py-3 text-left text-sm font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10 min-w-[200px]"
              >
                <div class="flex items-center gap-2">
                  <Grid3X3 :size="16" class="text-gray-500" />
                  <span>Fitur</span>
                </div>
              </th>
              <th
                v-for="role in roles"
                :key="role.id"
                class="px-4 py-3 text-center text-sm font-semibold text-gray-700 min-w-[100px]"
              >
                <div class="flex flex-col items-center gap-1">
                  <span>{{ role.name }}</span>
                  <span
                    v-if="role.is_system"
                    class="px-1.5 py-0.5 text-xs rounded bg-amber-100 text-amber-700"
                  >
                    Sistem
                  </span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="feature in features"
              :key="feature.key"
              class="border-b border-gray-100 hover:bg-gray-50/50 transition-colors"
            >
              <td
                class="px-4 py-3 text-sm font-medium text-gray-800 sticky left-0 bg-white z-10"
              >
                {{ feature.label }}
              </td>
              <td
                v-for="role in roles"
                :key="`${role.name}-${feature.key}`"
                class="px-4 py-3 text-center"
              >
                <PermissionMatrixCell
                  :status="getCellData(role.name, feature.key).status"
                  :role-name="role.name"
                  :feature-key="feature.key"
                  :permissions="[]"
                  @click="handleCellClick"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Legend -->
      <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center gap-6 text-sm text-gray-600">
        <span class="font-medium">Keterangan:</span>
        <div class="flex items-center gap-1.5">
          <CheckCircle2 :size="16" class="text-emerald-600" />
          <span>Penuh</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-100">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
          </span>
          <span>Sebagian</span>
        </div>
        <div class="flex items-center gap-1.5">
          <MinusCircle :size="16" class="text-slate-400" />
          <span>Kosong</span>
        </div>
        <span class="ml-auto text-xs text-gray-400">Klik sel untuk melihat detail permission</span>
      </div>
    </div>

    <!-- Detail Modal/Popover -->
    <Teleport to="body">
      <div
        v-if="showDetail && detailData"
        class="fixed inset-0 z-50 flex items-center justify-center"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="closeDetail"
        ></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 max-h-[80vh] flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">
                {{ detailData.featureLabel }}
              </h3>
              <p class="text-sm text-gray-500 mt-0.5">
                Role: <span class="font-medium text-gray-700">{{ detailData.roleName }}</span>
                — {{ detailData.selected }}/{{ detailData.total }} aktif
              </p>
            </div>
            <button
              @click="closeDetail"
              class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
              aria-label="Tutup"
            >
              <X :size="20" />
            </button>
          </div>

          <!-- Body -->
          <div class="overflow-y-auto p-5 space-y-2">
            <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-3">
              Daftar Permission (Read-Only)
            </p>
            <div
              v-for="perm in detailData.permissions"
              :key="perm.name"
              class="flex items-center gap-3 px-3 py-2 rounded-lg"
              :class="detailData.status === 'full'
                ? 'bg-emerald-50'
                : detailData.status === 'empty'
                  ? 'bg-gray-50'
                  : 'bg-gray-50'"
            >
              <div class="flex-shrink-0">
                <CheckCircle2
                  v-if="detailData.status === 'full'"
                  :size="16"
                  class="text-emerald-500"
                />
                <MinusCircle
                  v-else-if="detailData.status === 'empty'"
                  :size="16"
                  class="text-gray-400"
                />
                <span
                  v-else
                  class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-100"
                >
                  <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">
                  {{ perm.label }}
                </p>
                <p v-if="perm.subFeature" class="text-xs text-gray-400">
                  {{ perm.subFeature }}
                </p>
              </div>
            </div>

            <div
              v-if="detailData.permissions.length === 0"
              class="text-center py-4 text-gray-400 text-sm"
            >
              Tidak ada permission untuk fitur ini
            </div>
          </div>

          <!-- Footer -->
          <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 rounded-b-xl">
            <p class="text-xs text-gray-500 text-center">
              Tampilan read-only — untuk mengubah permission, edit role langsung
            </p>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
