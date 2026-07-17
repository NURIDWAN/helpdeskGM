<script setup>
import { reactive, ref, watch, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";

const authStore = useAuthStore();
const { user, loading, error, success } = storeToRefs(authStore);
const { checkAuth, updateProfile, uploadProfilePhoto, deleteProfilePhoto } = authStore;

const form = reactive({
  name: "",
  email: "",
  phone_number: "",
  telegram_chat_id: "",
  password: "",
  password_confirmation: "",
});

const photoInput = ref(null);
const photoPreview = ref(null);
const uploadingPhoto = ref(false);

const syncForm = () => {
  form.name = user.value?.name || "";
  form.email = user.value?.email || "";
  form.phone_number = user.value?.phone_number || "";
  form.telegram_chat_id = user.value?.telegram_chat_id || "";
  photoPreview.value = user.value?.profile_photo || null;
};

watch(user, syncForm, { immediate: true });

onMounted(async () => {
  if (!user.value) {
    await checkAuth();
  }
  syncForm();
});

const handleSubmit = async () => {
  const payload = {
    name: form.name,
    phone_number: form.phone_number,
    telegram_chat_id: form.telegram_chat_id || null,
  };
  if (form.password) {
    payload.password = form.password;
    payload.password_confirmation = form.password_confirmation;
  }
  await updateProfile(payload);
  form.password = "";
  form.password_confirmation = "";
};

const triggerPhotoInput = () => {
  photoInput.value?.click();
};

const handlePhotoChange = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file type
  const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
  if (!allowedTypes.includes(file.type)) {
    authStore.error = { photo: ["Format file harus JPEG, PNG, atau WebP."] };
    return;
  }

  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    authStore.error = { photo: ["Ukuran file maksimal 2MB."] };
    return;
  }

  uploadingPhoto.value = true;
  await uploadProfilePhoto(file);
  uploadingPhoto.value = false;

  // Reset input
  if (photoInput.value) {
    photoInput.value.value = "";
  }
};

const handleDeletePhoto = async () => {
  if (!confirm("Hapus foto profil?")) return;
  uploadingPhoto.value = true;
  await deleteProfilePhoto();
  uploadingPhoto.value = false;
};
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <!-- Profile Card -->
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

        <!-- Profile Photo Section -->
        <div class="flex items-center gap-5">
          <div class="relative group">
            <div
              class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-200 flex items-center justify-center"
            >
              <img
                v-if="photoPreview"
                :src="photoPreview"
                alt="Foto profil"
                class="w-full h-full object-cover"
              />
              <svg
                v-else
                class="w-10 h-10 text-gray-400"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
                />
              </svg>
            </div>
            <div
              v-if="uploadingPhoto"
              class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full"
            >
              <svg
                class="w-6 h-6 text-white animate-spin"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
            </div>
          </div>
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="triggerPhotoInput"
                :disabled="uploadingPhoto"
                class="px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 disabled:opacity-50 transition-colors"
              >
                {{ photoPreview ? "Ganti Foto" : "Upload Foto" }}
              </button>
              <button
                v-if="photoPreview"
                type="button"
                @click="handleDeletePhoto"
                :disabled="uploadingPhoto"
                class="px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 disabled:opacity-50 transition-colors"
              >
                Hapus
              </button>
            </div>
            <p class="text-xs text-gray-500">
              JPG, PNG, atau WebP. Maksimal 2MB.
            </p>
          </div>
          <input
            ref="photoInput"
            type="file"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            class="hidden"
            @change="handlePhotoChange"
          />
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

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Telegram Chat ID</label
            >
            <input
              v-model="form.telegram_chat_id"
              type="text"
              placeholder="Contoh: 123456789"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="loading"
            />
            <p class="mt-1 text-xs text-gray-500">
              ID Telegram untuk menerima notifikasi.
            </p>
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
