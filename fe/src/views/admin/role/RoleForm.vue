<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useRoleStore } from "@/stores/role";
import { usePermissionManager } from "@/composables/usePermissionManager";
import { featureGroups, colorMap, rolePresets } from "@/config/permissionConfig";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import Alert from "@/components/common/Alert.vue";
import { 
  ArrowLeft, 
  ChevronRight, 
  Shield, 
  Save, 
  Check, 
  ChevronDown,
  ChevronUp,
  RotateCcw,
  CheckSquare,
  Lock,
  Info,
  Sparkles,
  Search,
  BarChart3,
  Users,
  Building,
  FileCode,
  Tag,
  FolderTree,
  ClipboardList,
  FileText,
  Calendar,
  Zap,
  MessageCircle,
  Activity,
  MessageSquare,
  Paperclip,
  Gauge,
  Eye,
  Wrench,
  UserCheck
} from "lucide-vue-next";
import { storeToRefs } from "pinia";

// Icon map untuk dynamic icon rendering
const iconMap = {
  BarChart3, Users, Shield, Building, FileCode, Tag, FolderTree,
  ClipboardList, FileText, Calendar, Zap, MessageCircle, Activity,
  MessageSquare, Paperclip, Gauge, Eye, Wrench, UserCheck
};

const route = useRoute();
const router = useRouter();

const roleStore = useRoleStore();
const { loading, error } = storeToRefs(roleStore);
const { fetchRole, createRole, updateRole } = roleStore;

const isEditMode = computed(() => !!route.params.id);
const roleId = computed(() => route.params.id);
const isProtectedRole = ref(false);

const form = reactive({
  name: "",
});

// Permission Manager
const permissionManager = usePermissionManager();
const {
  selectedPermissions,
  togglePermission,
  toggleFeature,
  toggleSubFeature,
  applyPreset,
  selectAll,
  resetAll,
  setPermissions,
  isSelected,
  isLocked,
  getLockedBy,
  getFeatureStatus,
  getSubFeatureStatus,
  getSubFeatureCounts,
  totalSelected,
  featureCounts
} = permissionManager;

// UI State
const expandedFeatures = ref({});
const searchQuery = ref("");
const showPresetDropdown = ref(false);
const toggleWarning = ref(null);

// Initialize expanded state
Object.keys(featureGroups).forEach(key => {
  expandedFeatures.value[key] = false;
});

// Filter features based on search
const filteredFeatures = computed(() => {
  if (!searchQuery.value) return featureGroups;
  
  const query = searchQuery.value.toLowerCase();
  const filtered = {};
  
  Object.entries(featureGroups).forEach(([key, feature]) => {
    // Check feature label
    if (feature.label.toLowerCase().includes(query)) {
      filtered[key] = feature;
      return;
    }
    
    // Check permissions
    let hasMatch = false;
    
    if (feature.permissions) {
      Object.values(feature.permissions).forEach(perm => {
        if (perm.label.toLowerCase().includes(query) || 
            perm.description?.toLowerCase().includes(query)) {
          hasMatch = true;
        }
      });
    }
    
    if (feature.subFeatures) {
      Object.values(feature.subFeatures).forEach(sub => {
        if (sub.label.toLowerCase().includes(query)) {
          hasMatch = true;
        }
        if (sub.permissions) {
          Object.values(sub.permissions).forEach(perm => {
            if (perm.label.toLowerCase().includes(query) || 
                perm.description?.toLowerCase().includes(query)) {
              hasMatch = true;
            }
          });
        }
      });
    }
    
    if (hasMatch) {
      filtered[key] = feature;
    }
  });
  
  return filtered;
});

// Toggle feature expansion
const toggleExpand = (featureKey) => {
  expandedFeatures.value[featureKey] = !expandedFeatures.value[featureKey];
};

// Expand all features that have matches when searching
const expandAllMatches = () => {
  if (searchQuery.value) {
    Object.keys(filteredFeatures.value).forEach(key => {
      expandedFeatures.value[key] = true;
    });
  }
};

// Handle permission toggle with warning
const handleTogglePermission = (permissionName) => {
  const result = togglePermission(permissionName);
  if (!result.success) {
    toggleWarning.value = {
      permission: permissionName,
      message: result.message
    };
    setTimeout(() => {
      toggleWarning.value = null;
    }, 3000);
  }
};

// Handle preset selection
const handlePresetSelect = (presetKey) => {
  applyPreset(presetKey);
  showPresetDropdown.value = false;
};

// Get icon component
const getIcon = (iconName) => {
  return iconMap[iconName] || Shield;
};

// Get color classes
const getColorClasses = (colorName) => {
  return colorMap[colorName] || colorMap.blue;
};

// Load data
const loadRoleData = async () => {
  if (roleId.value) {
    try {
      const role = await fetchRole(roleId.value);
      form.name = role.name;
      setPermissions(role.permissions || []);
      isProtectedRole.value = ["admin", "staff", "user", "superadmin"].includes(role.name);
      
      // Expand features that have selected permissions
      Object.entries(featureGroups).forEach(([key]) => {
        const counts = featureCounts.value[key];
        if (counts && counts.selected > 0) {
          expandedFeatures.value[key] = true;
        }
      });
    } catch (e) {
      console.error("Error loading role:", e);
      router.push({ name: "admin.roles" });
    }
  }
};

const handleSubmit = async () => {
  try {
    const data = {
      name: form.name,
      permissions: selectedPermissions.value,
    };

    if (isEditMode.value) {
      await updateRole(roleId.value, data);
    } else {
      await createRole(data);
    }

    router.push({ name: "admin.roles" });
  } catch (e) {
    console.error("Error saving role:", e);
  }
};

onMounted(() => {
  if (isEditMode.value) {
    loadRoleData();
  }
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.roles' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEditMode ? "Edit Role" : "Tambah Role" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEditMode
              ? "Perbarui informasi dan permission role"
              : "Buat role baru dengan permission yang sesuai"
          }}
        </p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="{ name: 'admin.dashboard' }" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink :to="{ name: 'admin.roles' }" class="hover:text-gray-700">
        Data Role
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">{{
        isEditMode ? "Edit" : "Tambah"
      }}</span>
    </nav>
  </div>

  <!-- Alert -->
  <Alert
    v-if="error"
    type="error"
    :message="error"
    :auto-close="true"
    :duration="5000"
    class="mb-6"
  />

  <!-- Toggle Warning -->
  <div
    v-if="toggleWarning"
    class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg animate-pulse"
  >
    <div class="flex items-center gap-3">
      <Lock :size="20" class="text-amber-600" />
      <div>
        <h4 class="font-medium text-amber-800">Tidak dapat menonaktifkan</h4>
        <p class="text-sm text-amber-700">{{ toggleWarning.message }}</p>
      </div>
    </div>
  </div>

  <!-- Protected Role Warning -->
  <div
    v-if="isProtectedRole"
    class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg"
  >
    <div class="flex items-center gap-3">
      <Shield :size="20" class="text-yellow-600" />
      <div>
        <h4 class="font-medium text-yellow-800">Role Sistem</h4>
        <p class="text-sm text-yellow-700">
          Nama role ini tidak dapat diubah, tetapi Anda dapat mengubah
          permission-nya.
        </p>
      </div>
    </div>
  </div>

  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Basic Info -->
    <FormCard
      title="Informasi Role"
      subtitle="Nama dan identitas role"
      :icon="Shield"
    >
      <div class="grid grid-cols-1 gap-6">
        <FormField
          id="name"
          label="Nama Role"
          v-model="form.name"
          placeholder="Contoh: supervisor"
          required
          :disabled="isProtectedRole"
          :icon="Shield"
        />
      </div>
    </FormCard>

    <!-- Permissions -->
    <FormCard
      title="Permission"
      subtitle="Pilih permission yang diberikan ke role ini"
      :icon="Check"
    >
      <!-- Permission Header with Actions -->
      <div class="mb-6 pb-4 border-b border-gray-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <!-- Counter -->
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-lg">
              <CheckSquare :size="18" class="text-blue-600" />
              <span class="font-semibold text-blue-900">{{ totalSelected }}</span>
              <span class="text-blue-700">permission dipilih</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Search -->
            <div class="relative">
              <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="searchQuery"
                @input="expandAllMatches"
                type="text"
                placeholder="Cari permission..."
                class="pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48"
              />
            </div>

            <!-- Preset Dropdown -->
            <div class="relative">
              <button
                type="button"
                @click="showPresetDropdown = !showPresetDropdown"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors"
              >
                <Sparkles :size="16" />
                Template
                <ChevronDown :size="14" />
              </button>
              
              <!-- Dropdown Menu -->
              <div
                v-if="showPresetDropdown"
                class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-10"
              >
                <div class="p-2">
                  <p class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">
                    Pilih Template Role
                  </p>
                  <button
                    v-for="(preset, key) in rolePresets"
                    :key="key"
                    type="button"
                    @click="handlePresetSelect(key)"
                    class="w-full flex items-start gap-3 px-3 py-2 text-left hover:bg-gray-50 rounded-lg transition-colors"
                  >
                    <component :is="getIcon(preset.icon)" :size="18" class="text-purple-600 mt-0.5" />
                    <div>
                      <p class="font-medium text-gray-900">{{ preset.label }}</p>
                      <p class="text-xs text-gray-500">{{ preset.description }}</p>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Select All -->
            <button
              type="button"
              @click="selectAll"
              class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors"
            >
              <CheckSquare :size="16" />
              Pilih Semua
            </button>

            <!-- Reset -->
            <button
              type="button"
              @click="resetAll"
              class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors"
            >
              <RotateCcw :size="16" />
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
        ></div>
        <p class="mt-2 text-gray-600">Memuat permission...</p>
      </div>

      <!-- Feature Cards -->
      <div v-else class="space-y-4">
        <div
          v-for="(feature, featureKey) in filteredFeatures"
          :key="featureKey"
          class="border border-gray-200 rounded-xl overflow-hidden transition-all duration-200"
          :class="{
            'ring-2 ring-blue-500 ring-offset-1': featureCounts[featureKey]?.selected > 0
          }"
        >
          <!-- Feature Header -->
          <div
            class="flex items-center justify-between px-4 py-3 cursor-pointer transition-colors"
            :class="[
              getColorClasses(feature.color).bg,
              'hover:brightness-95'
            ]"
            @click="toggleExpand(featureKey)"
          >
            <div class="flex items-center gap-3">
              <!-- Feature Icon -->
              <div
                class="w-10 h-10 rounded-lg flex items-center justify-center"
                :class="getColorClasses(feature.color).accent"
              >
                <component :is="getIcon(feature.icon)" :size="20" class="text-white" />
              </div>
              
              <!-- Feature Info -->
              <div>
                <h3 class="font-semibold" :class="getColorClasses(feature.color).text">
                  {{ feature.label }}
                </h3>
                <p class="text-xs text-gray-600">{{ feature.description }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <!-- Count Badge -->
              <div class="flex items-center gap-2">
                <span
                  class="px-2.5 py-1 text-sm font-medium rounded-full"
                  :class="[
                    featureCounts[featureKey]?.selected > 0 
                      ? 'bg-white text-gray-900 shadow-sm' 
                      : 'bg-gray-200 text-gray-600'
                  ]"
                >
                  {{ featureCounts[featureKey]?.selected || 0 }}/{{ featureCounts[featureKey]?.total || 0 }}
                </span>
              </div>

              <!-- Toggle All Button -->
              <button
                type="button"
                @click.stop="toggleFeature(featureKey)"
                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                :class="[
                  getFeatureStatus(featureKey) === 'all'
                    ? 'bg-white text-gray-700 hover:bg-gray-100'
                    : 'bg-white/80 text-gray-700 hover:bg-white'
                ]"
              >
                {{ getFeatureStatus(featureKey) === 'all' ? 'Hapus Semua' : 'Pilih Semua' }}
              </button>

              <!-- Expand Icon -->
              <component
                :is="expandedFeatures[featureKey] ? ChevronUp : ChevronDown"
                :size="20"
                :class="getColorClasses(feature.color).text"
              />
            </div>
          </div>

          <!-- Feature Content -->
          <div
            v-show="expandedFeatures[featureKey]"
            class="p-4 bg-white"
          >
            <!-- Simple permissions (no sub-features) -->
            <template v-if="feature.permissions && !feature.subFeatures">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <label
                  v-for="(permission, permKey) in feature.permissions"
                  :key="permKey"
                  class="relative flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200 group"
                  :class="[
                    isSelected(permKey)
                      ? 'border-blue-300 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50',
                    isLocked(permKey) ? 'opacity-75' : ''
                  ]"
                >
                  <input
                    type="checkbox"
                    :checked="isSelected(permKey)"
                    :disabled="isLocked(permKey)"
                    @change="handleTogglePermission(permKey)"
                    class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-medium text-gray-900">
                        {{ permission.label }}
                      </span>
                      <Lock v-if="isLocked(permKey)" :size="12" class="text-amber-500" />
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">
                      {{ permission.description }}
                    </p>
                    <!-- Locked By Info -->
                    <p
                      v-if="isLocked(permKey)"
                      class="text-xs text-amber-600 mt-1 flex items-center gap-1"
                    >
                      <Info :size="10" />
                      Dibutuhkan oleh permission lain
                    </p>
                  </div>
                </label>
              </div>
            </template>

            <!-- Sub-features -->
            <template v-if="feature.subFeatures">
              <div class="space-y-4">
                <div
                  v-for="(subFeature, subKey) in feature.subFeatures"
                  :key="subKey"
                  class="border border-gray-200 rounded-lg overflow-hidden"
                >
                  <!-- Sub-feature Header -->
                  <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                    <div class="flex items-center gap-3">
                      <component
                        :is="getIcon(subFeature.icon)"
                        :size="18"
                        class="text-gray-600"
                      />
                      <div>
                        <h4 class="font-medium text-gray-900">{{ subFeature.label }}</h4>
                        <!-- Dependency Warning -->
                        <p
                          v-if="subFeature.dependsOn"
                          class="text-xs text-amber-600 flex items-center gap-1 mt-0.5"
                        >
                          <Lock :size="10" />
                          Membutuhkan: {{ subFeature.dependsOn.map(d => {
                            const parts = d.split('-');
                            return parts[parts.length - 1].charAt(0).toUpperCase() + parts[parts.length - 1].slice(1);
                          }).join(', ') }}
                        </p>
                      </div>
                    </div>

                    <div class="flex items-center gap-2">
                      <!-- Count -->
                      <span class="text-sm text-gray-600">
                        {{ getSubFeatureCounts(featureKey, subKey).selected }}/{{ getSubFeatureCounts(featureKey, subKey).total }}
                      </span>
                      <!-- Toggle All -->
                      <button
                        type="button"
                        @click="toggleSubFeature(featureKey, subKey)"
                        class="px-2 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded hover:bg-gray-100 transition-colors"
                      >
                        {{ getSubFeatureStatus(featureKey, subKey) === 'all' ? 'Hapus' : 'Pilih' }}
                      </button>
                    </div>
                  </div>

                  <!-- Sub-feature Permissions -->
                  <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <label
                      v-for="(permission, permKey) in subFeature.permissions"
                      :key="permKey"
                      class="relative flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200"
                      :class="[
                        isSelected(permKey)
                          ? 'border-blue-300 bg-blue-50'
                          : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50',
                        isLocked(permKey) ? 'opacity-75' : ''
                      ]"
                    >
                      <input
                        type="checkbox"
                        :checked="isSelected(permKey)"
                        :disabled="isLocked(permKey)"
                        @change="handleTogglePermission(permKey)"
                        class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
                      />
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <span class="text-sm font-medium text-gray-900">
                            {{ permission.label }}
                          </span>
                          <Lock v-if="isLocked(permKey)" :size="12" class="text-amber-500" />
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">
                          {{ permission.description }}
                        </p>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- No Results -->
        <div
          v-if="Object.keys(filteredFeatures).length === 0"
          class="text-center py-8 text-gray-500"
        >
          <Search :size="40" class="mx-auto mb-3 text-gray-400" />
          <p>Tidak ada permission yang cocok dengan pencarian.</p>
        </div>
      </div>
    </FormCard>

    <!-- Action Buttons -->
    <div
      class="flex justify-between items-center pt-6 border-t border-gray-200"
    >
      <RouterLink
        :to="{ name: 'admin.roles' }"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
      >
        Batal
      </RouterLink>
      <button
        type="submit"
        :disabled="loading || !form.name"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md font-medium"
      >
        <div
          v-if="loading"
          class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
        ></div>
        <Save v-if="!loading" :size="16" />
        {{ loading ? "Menyimpan..." : "Simpan" }}
      </button>
    </div>
  </form>

  <!-- Click outside to close preset dropdown -->
  <div
    v-if="showPresetDropdown"
    class="fixed inset-0 z-0"
    @click="showPresetDropdown = false"
  ></div>
</template>
