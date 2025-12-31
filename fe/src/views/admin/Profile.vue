<script setup>
import { reactive, watch, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";

const authStore = useAuthStore();
const { user, loading, error, success } = storeToRefs(authStore);
const { checkAuth, updateProfile } = authStore;

const form = reactive({
  name: "",
  email: "",
  phone_number: "",
  password: "",
  password_confirmation: "",
});

const syncForm = () => {
  form.name = user.value?.name || "";
  form.email = user.value?.email || "";
  form.phone_number = user.value?.phone_number || "";
};

watch(user, syncForm, { immediate: true });

onMounted(async () => {
  if (!user.value) {
    await checkAuth();
  }
  syncForm();
});

const handleSubmit = async () => {
  const payload = { name: form.name, phone_number: form.phone_number };
  if (form.password) {
    payload.password = form.password;
    payload.password_confirmation = form.password_confirmation;
  }
  await updateProfile(payload);
  form.password = "";
  form.password_confirmation = "";
};
</script>

<template>
  <div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-6 py-5 border-b border-gray-100">
        <h1 class="text-xl font-semibold text-gray-900">Pengaturan Profil</h1>
        <p class="text-gray-600 text-sm mt-1">Perbarui informasi akun Anda.</p>
      </div>
      <div class="p-6 space-y-6">
        <div
          v-if="success"
          class="rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm px-3 py-2"
        >
          {{ success }}
        </div>
        <div
          v-if="error"
          class="rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm px-3 py-2"
        >
          <ul>
            <li v-for="(messages, key) in error" :key="key">
              <span v-if="Array.isArray(messages)">
                <span v-for="(msg, idx) in messages" :key="idx">{{ msg }}</span>
              </span>
              <span v-else>{{ messages }}</span>
            </li>
          </ul>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Nama</label
              >
              <input
                v-model="form.name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :disabled="loading"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Email</label
              >
              <input
                v-model="form.email"
                type="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100"
                disabled
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Nomor Telepon</label
            >
            <input
              v-model="form.phone_number"
              type="tel"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="loading"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Password Baru</label
              >
              <input
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :disabled="loading"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Konfirmasi Password</label
              >
              <input
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :disabled="loading"
              />
            </div>
          </div>

          <div class="pt-2 flex justify-end">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ loading ? "Menyimpan..." : "Simpan Perubahan" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
