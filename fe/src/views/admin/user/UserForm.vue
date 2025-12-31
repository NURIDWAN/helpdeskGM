<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "@/stores/user";
import { useBranchStore } from "@/stores/branch";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import MultiSelect from "@/components/common/MultiSelect.vue";
import {
  ArrowLeft,
  Users,
  Save,
  ChevronRight,
  User,
  Mail,
  Lock,
  Building,
  Briefcase,
  CreditCard,
  Shield,
  Phone,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { axiosInstance } from "@/plugins/axios";

const route = useRoute();
const router = useRouter();

const userStore = useUserStore();
const branchStore = useBranchStore();
const { error, loading } = storeToRefs(userStore);
const { branches } = storeToRefs(branchStore);
const { createUser, updateUser, fetchUser } = userStore;
const { fetchBranches } = branchStore;

// Computed
const isEdit = computed(() => route.name === "admin.user.edit");
const userId = computed(() => route.params.id);

// Reactive data
const form = reactive({
  name: "",
  email: "",
  password: "",
  branch_id: "",
  position: "",
  identity_number: "",
  phone_number: "",
  type: "",
  roles: [],
});

// Options
const userTypes = [
  { value: "internal", label: "Internal" },
  { value: "external", label: "External" },
];

const availableRoles = ref([
  { value: "admin", label: "Admin" },
  { value: "staff", label: "Staff" },
  { value: "user", label: "User" },
]);

// Methods
const handleSubmit = async () => {
  try {
    const payload = {
      name: form.name.trim(),
      email: form.email.trim(),
      branch_id: form.branch_id,
      position: form.position.trim(),
      identity_number: form.identity_number.trim(),
      phone_number: form.phone_number.trim(),
      type: form.type,
      roles: form.roles,
    };

    // Only include password for create or if it's provided
    if (!isEdit.value || form.password) {
      payload.password = form.password;
    }

    if (isEdit.value) {
      await updateUser(userId.value, payload);
    } else {
      await createUser(payload);
    }

    if (userStore.success) {
      router.push({ name: "admin.users" });
    }
  } catch (error) {
    console.error("Error submitting form:", error);
  }
};

const loadUserData = async () => {
  if (isEdit.value && userId.value) {
    try {
      const user = await fetchUser(userId.value);
      if (user) {
        form.name = user.name;
        form.email = user.email;
        form.branch_id = user.branch?.id || "";
        form.position = user.position;
        form.identity_number = user.identity_number;
        form.phone_number = user.phone_number || "";
        form.type = user.type;
        form.roles = user.roles;
      }
    } catch (error) {
      console.error("Error loading user data:", error);
      router.push({ name: "admin.users" });
    }
  }
};

const loadBranchesData = async () => {
  try {
    await fetchBranches();
  } catch (error) {
    console.error("Error loading branches:", error);
  }
};

// Lifecycle
onMounted(() => {
  loadBranchesData();
  loadUserData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.users' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEdit ? "Edit User" : "Tambah User Baru" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi user yang sudah ada"
              : "Tambahkan user baru ke dalam sistem"
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
      <RouterLink :to="{ name: 'admin.users' }" class="hover:text-gray-700">
        Data User
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Form Card -->
  <FormCard
    title="Informasi User"
    subtitle="Lengkapi data user dengan benar"
    :icon="Users"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Name Field -->
        <div>
          <FormField
            v-model="form.name"
            id="name"
            name="name"
            label="Nama Lengkap"
            :label-icon="User"
            placeholder="Masukkan nama lengkap"
            :error="error?.name?.join(', ')"
            :required="true"
          />
        </div>

        <!-- Email Field -->
        <div>
          <FormField
            v-model="form.email"
            id="email"
            name="email"
            label="Email"
            :label-icon="Mail"
            placeholder="contoh@email.com"
            :error="error?.email?.join(', ')"
            :required="true"
            type="email"
          />
        </div>

        <!-- Password Field -->
        <div>
          <FormField
            v-model="form.password"
            id="password"
            name="password"
            label="Kata Sandi"
            :label-icon="Lock"
            placeholder="Minimal 8 karakter"
            :error="error?.password?.join(', ')"
            :required="!isEdit"
            type="password"
          />
        </div>

        <!-- Branch Field -->
        <div>
          <FormField
            v-model="form.branch_id"
            id="branch_id"
            name="branch_id"
            label="Cabang"
            :label-icon="Building"
            :error="error?.branch_id?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih cabang"
            :options="
              branches.map((branch) => ({
                value: branch.id,
                label: branch.name,
              }))
            "
          />
        </div>

        <!-- Position Field -->
        <div>
          <FormField
            v-model="form.position"
            id="position"
            name="position"
            label="Jabatan"
            :label-icon="Briefcase"
            placeholder="Masukkan jabatan"
            :error="error?.position?.join(', ')"
            :required="true"
          />
        </div>

        <!-- Identity Number Field -->
        <div>
          <FormField
            v-model="form.identity_number"
            id="identity_number"
            name="identity_number"
            label="Nomor Identitas"
            :label-icon="CreditCard"
            placeholder="Masukkan nomor identitas"
            :error="error?.identity_number?.join(', ')"
            :required="true"
          />
        </div>

        <!-- Phone Number Field -->
        <div>
          <FormField
            v-model="form.phone_number"
            id="phone_number"
            name="phone_number"
            label="Nomor Telepon"
            :label-icon="Phone"
            placeholder="08xxxxxxxxxx"
            :error="error?.phone_number?.join(', ')"
            :required="false"
          />
        </div>

        <!-- Type Field -->
        <div>
          <FormField
            v-model="form.type"
            id="type"
            name="type"
            label="Tipe User"
            :label-icon="Shield"
            :error="error?.type?.join(', ')"
            :required="true"
            type="select"
            :options="userTypes"
          />
        </div>

        <!-- Roles Field -->
        <div class="lg:col-span-2">
          <label
            for="roles"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            <Shield :size="16" class="inline mr-2" /> Roles
            <span class="text-red-500 ml-1">*</span>
          </label>
          <MultiSelect
            v-model="form.roles"
            :options="availableRoles"
            placeholder="Pilih role"
          />
          <p v-if="error?.roles" class="mt-2 text-sm text-red-600">
            {{ error?.roles?.join(", ") }}
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div
        class="flex justify-between items-center pt-8 mt-8 border-t border-gray-200"
      >
        <div class="text-sm text-gray-500">
          <span class="text-red-500">*</span> Wajib diisi
        </div>
        <div class="flex gap-3">
          <RouterLink
            :to="{ name: 'admin.users' }"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
          >
            Batal
          </RouterLink>
          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 font-medium transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <div
              v-if="loading"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
            ></div>
            <Save v-if="!loading" :size="16" />
            {{
              loading ? "Menyimpan..." : isEdit ? "Update User" : "Simpan User"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>
</template>
