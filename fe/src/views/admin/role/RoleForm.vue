<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useRoleStore } from "@/stores/role";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import Alert from "@/components/common/Alert.vue";
import { ArrowLeft, ChevronRight, Shield, Save, Check } from "lucide-vue-next";
import { storeToRefs } from "pinia";

const route = useRoute();
const router = useRouter();

const roleStore = useRoleStore();
const { loading, error, permissions } = storeToRefs(roleStore);
const { fetchRole, fetchPermissions, createRole, updateRole } = roleStore;

const isEditMode = computed(() => !!route.params.id);
const roleId = computed(() => route.params.id);
const isProtectedRole = ref(false);

const form = reactive({
  name: "",
  permissions: [],
});

const loadPermissions = async () => {
  try {
    await fetchPermissions();
  } catch (e) {
    console.error("Error loading permissions:", e);
  }
};

const loadRoleData = async () => {
  if (roleId.value) {
    try {
      const role = await fetchRole(roleId.value);
      form.name = role.name;
      form.permissions = role.permissions || [];
      isProtectedRole.value = ["admin", "staff", "user"].includes(role.name);
    } catch (e) {
      console.error("Error loading role:", e);
      router.push({ name: "admin.roles" });
    }
  }
};

const togglePermission = (permissionName) => {
  const index = form.permissions.indexOf(permissionName);
  if (index === -1) {
    form.permissions.push(permissionName);
  } else {
    form.permissions.splice(index, 1);
  }
};

const toggleAllInModule = (module, permissionsList) => {
  const modulePermissions = permissionsList.map((p) => p.name);
  const allSelected = modulePermissions.every((p) =>
    form.permissions.includes(p)
  );

  if (allSelected) {
    // Deselect all
    form.permissions = form.permissions.filter(
      (p) => !modulePermissions.includes(p)
    );
  } else {
    // Select all
    modulePermissions.forEach((p) => {
      if (!form.permissions.includes(p)) {
        form.permissions.push(p);
      }
    });
  }
};

const isModuleAllSelected = (permissionsList) => {
  return permissionsList.every((p) => form.permissions.includes(p));
};

const isModulePartialSelected = (permissionsList) => {
  const selected = permissionsList.filter((p) =>
    form.permissions.includes(p.name)
  );
  return selected.length > 0 && selected.length < permissionsList.length;
};

const handleSubmit = async () => {
  try {
    const data = {
      name: form.name,
      permissions: form.permissions,
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

const formatModuleName = (module) => {
  return module
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
};

const formatPermissionName = (permission) => {
  const parts = permission.name.split("-");
  return parts[parts.length - 1].charAt(0).toUpperCase() + parts[parts.length - 1].slice(1);
};

onMounted(() => {
  loadPermissions();
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
      <div v-if="loading" class="text-center py-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
        ></div>
        <p class="mt-2 text-gray-600">Memuat permission...</p>
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="(permissionsList, module) in permissions"
          :key="module"
          class="border border-gray-200 rounded-lg overflow-hidden"
        >
          <!-- Module Header -->
          <div
            class="bg-gray-50 px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-100"
            @click="toggleAllInModule(module, permissionsList)"
          >
            <div class="flex items-center gap-3">
              <input
                type="checkbox"
                :checked="isModuleAllSelected(permissionsList)"
                :indeterminate="isModulePartialSelected(permissionsList)"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                @click.stop="toggleAllInModule(module, permissionsList)"
              />
              <span class="font-medium text-gray-900">{{
                formatModuleName(module)
              }}</span>
              <span class="text-xs text-gray-500"
                >({{ permissionsList.length }} permission)</span
              >
            </div>
          </div>

          <!-- Permissions List -->
          <div class="px-4 py-3 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <label
              v-for="permission in permissionsList"
              :key="permission.id"
              class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
            >
              <input
                type="checkbox"
                :checked="form.permissions.includes(permission.name)"
                @change="togglePermission(permission.name)"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">{{
                formatPermissionName(permission)
              }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Selected Count -->
      <div class="mt-4 text-sm text-gray-600">
        {{ form.permissions.length }} permission dipilih
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
        :disabled="loading"
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
</template>
