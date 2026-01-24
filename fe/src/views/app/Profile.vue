<script setup>
import { reactive, ref, watch, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";
import { axiosInstance } from "@/plugins/axios";
import { Link2, Unlink, ExternalLink } from "lucide-vue-next";

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

// Telegram connection state
const telegramStatus = ref({
  connected: false,
  chat_id: null,
  linked_at: null,
});
const telegramLink = ref("");
const telegramLoading = ref(false);
const telegramError = ref(null);
const telegramSuccess = ref(null);

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
  await fetchTelegramStatus();
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

// Telegram methods
const fetchTelegramStatus = async () => {
  try {
    const response = await axiosInstance.get("/telegram/status");
    telegramStatus.value = response.data.data || { connected: false };
  } catch (e) {
    console.error("Error fetching Telegram status:", e);
  }
};

const generateTelegramLink = async () => {
  telegramLoading.value = true;
  telegramError.value = null;
  telegramSuccess.value = null;

  try {
    const response = await axiosInstance.post("/telegram/generate-token");
    telegramLink.value = response.data.data.link;
    telegramSuccess.value = "Link berhasil dibuat. Klik untuk menghubungkan Telegram.";
  } catch (e) {
    telegramError.value = e.response?.data?.message || "Gagal membuat link";
  } finally {
    telegramLoading.value = false;
  }
};

const disconnectTelegram = async () => {
  if (!confirm("Yakin ingin memutuskan koneksi Telegram?")) return;

  telegramLoading.value = true;
  telegramError.value = null;

  try {
    await axiosInstance.post("/telegram/disconnect");
    telegramStatus.value = { connected: false, chat_id: null, linked_at: null };
    telegramLink.value = "";
    telegramSuccess.value = "Telegram berhasil diputuskan";
    await checkAuth();
  } catch (e) {
    telegramError.value = e.response?.data?.message || "Gagal memutuskan Telegram";
  } finally {
    telegramLoading.value = false;
  }
};

const openTelegramLink = () => {
  if (telegramLink.value) {
    window.open(telegramLink.value, "_blank");
    setTimeout(async () => {
      await fetchTelegramStatus();
      if (telegramStatus.value.connected) {
        telegramSuccess.value = "Telegram berhasil terhubung!";
        telegramLink.value = "";
        await checkAuth();
      }
    }, 5000);
  }
};

const formatDate = (dateString) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-6">
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

        <form @submit.prevent="handleSubmit" class="space-y-4">
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
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Email</label
            >
            <input
              v-model="form.email"
              type="email"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-100"
              :disabled="loading"
              disabled
            />
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

    <!-- Telegram Connection Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-6 py-5 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900">Koneksi Telegram</h2>
        <p class="text-gray-600 text-sm mt-1">Hubungkan akun Telegram untuk menerima notifikasi.</p>
      </div>
      <div class="p-6 space-y-4">
        <!-- Success/Error Messages -->
        <div
          v-if="telegramSuccess"
          class="rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm px-3 py-2"
        >
          {{ telegramSuccess }}
        </div>
        <div
          v-if="telegramError"
          class="rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm px-3 py-2"
        >
          {{ telegramError }}
        </div>

        <!-- Connected State -->
        <div v-if="telegramStatus.connected" class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <span class="font-medium text-green-800">Terhubung</span>
              </div>
              <p class="text-xs text-green-600 mt-1">
                Terhubung pada: {{ formatDate(telegramStatus.linked_at) }}
              </p>
            </div>
            <button
              @click="disconnectTelegram"
              :disabled="telegramLoading"
              class="inline-flex items-center px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 disabled:opacity-50"
            >
              <Unlink :size="16" class="mr-2" />
              Putuskan
            </button>
          </div>
        </div>

        <!-- Not Connected State -->
        <div v-else class="space-y-4">
          <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
              <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
              <span class="font-medium text-gray-700">Belum Terhubung</span>
            </div>
            <p class="text-sm text-gray-600">
              Hubungkan akun Telegram Anda untuk menerima notifikasi langsung di Telegram.
            </p>
          </div>

          <!-- Generate Link Section -->
          <div v-if="!telegramLink" class="flex justify-center">
            <button
              @click="generateTelegramLink"
              :disabled="telegramLoading"
              class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <Link2 :size="16" class="mr-2" />
              {{ telegramLoading ? "Membuat Link..." : "Hubungkan Telegram" }}
            </button>
          </div>

          <!-- Link Ready Section -->
          <div v-else class="space-y-3">
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p class="text-sm text-blue-800 mb-2">
                Klik tombol di bawah untuk membuka Telegram:
              </p>
              <button
                @click="openTelegramLink"
                class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                <ExternalLink :size="16" class="mr-2" />
                Buka Telegram
              </button>
            </div>
            <p class="text-xs text-gray-500 text-center">
              Setelah membuka link, klik "Start" di Telegram.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
