<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useTicketStore } from "@/stores/ticket";
import { useTicketAttachmentStore } from "@/stores/ticketAttachment";
import { storeToRefs } from "pinia";
import {
  ArrowLeft,
  CheckCircle,
  Send,
  Upload,
  X,
  FileText,
  Image,
} from "lucide-vue-next";

const router = useRouter();

const ticketStore = useTicketStore();
const { success, error, loading } = storeToRefs(ticketStore);
const { createTicket } = ticketStore;

const ticketAttachmentStore = useTicketAttachmentStore();
const { loading: attachmentLoading } = storeToRefs(ticketAttachmentStore);
const { createAttachment } = ticketAttachmentStore;

// Step management
const currentStep = ref(1);
const createdTicket = ref(null);
const selectedFiles = ref([]);
const uploadedFiles = ref([]);

const form = ref({
  title: "",
  description: "",
  priority: "",
});

const handleSubmit = async () => {
  try {
    const response = await createTicket(form.value);
    createdTicket.value = response;
    currentStep.value = 2;
  } catch (error) {
    console.error("Error creating ticket:", error);
  }
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  selectedFiles.value = [...selectedFiles.value, ...files];
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

const uploadAttachments = async () => {
  if (!createdTicket.value || selectedFiles.value.length === 0) {
    return;
  }

  try {
    for (const file of selectedFiles.value) {
      const formData = new FormData();
      formData.append("file", file);

      await createAttachment(createdTicket.value.id, formData);
    }

    // Redirect to dashboard on success
    router.push({ name: "app.dashboard" });
  } catch (error) {
    console.error("Error uploading attachments:", error);
  }
};

const skipAttachments = () => {
  router.push({ name: "app.dashboard" });
};

const goBackToStep1 = () => {
  currentStep.value = 1;
};
</script>

<template>
  <!-- Progress Indicator -->
  <div class="mb-8">
    <div class="flex items-center justify-center space-x-8">
      <!-- Step 1 -->
      <div class="flex items-center">
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          :class="
            currentStep >= 1
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 text-gray-600'
          "
        >
          1
        </div>
        <span
          class="ml-2 text-sm font-medium"
          :class="currentStep >= 1 ? 'text-blue-600' : 'text-gray-500'"
        >
          Informasi Tiket
        </span>
      </div>

      <!-- Connector -->
      <div
        class="w-16 h-0.5"
        :class="currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200'"
      ></div>

      <!-- Step 2 -->
      <div class="flex items-center">
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          :class="
            currentStep >= 2
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 text-gray-600'
          "
        >
          2
        </div>
        <span
          class="ml-2 text-sm font-medium"
          :class="currentStep >= 2 ? 'text-blue-600' : 'text-gray-500'"
        >
          Lampiran
        </span>
      </div>
    </div>
  </div>

  <!-- Back Button -->
  <div class="mb-6">
    <RouterLink
      v-if="currentStep === 1"
      :to="{ name: 'app.dashboard' }"
      class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800"
    >
      <ArrowLeft :size="16" class="mr-2" />
      Kembali ke Daftar Tiket
    </RouterLink>
    <button
      v-else
      @click="goBackToStep1"
      class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800"
    >
      <ArrowLeft :size="16" class="mr-2" />
      Kembali ke Step 1
    </button>
  </div>
  <!-- Step 1: Ticket Information -->
  <div
    v-if="currentStep === 1"
    class="bg-white rounded-xl shadow-sm border border-gray-100"
  >
    <div class="p-6 border-b border-gray-100">
      <h1 class="text-2xl font-bold text-gray-800">Informasi Tiket</h1>
      <p class="text-sm text-gray-500 mt-1">
        Isi form di bawah ini untuk membuat tiket baru
      </p>
    </div>

    <!-- Success Message -->
    <div v-if="success" class="p-4 bg-green-50 border-l-4 border-green-400">
      <div class="flex">
        <div class="ml-3">
          <p class="text-sm text-green-700">{{ success }}</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
      <!-- Judul Tiket -->
      <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-2"
          >Judul Tiket</label
        >
        <input
          type="text"
          id="title"
          v-model="form.title"
          placeholder="Contoh: Gangguan Jaringan WiFi"
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
        />
        <div v-if="error?.title" class="flex items-center mt-2">
          <p class="text-xs text-red-500">{{ error.title[0] }}</p>
        </div>
      </div>

      <!-- Deskripsi -->
      <div>
        <label
          for="description"
          class="block text-sm font-medium text-gray-700 mb-2"
          >Deskripsi Masalah</label
        >
        <textarea
          id="description"
          v-model="form.description"
          rows="6"
          placeholder="Jelaskan masalah Anda secara detail. Sertakan informasi seperti:&#10;- Kapan masalah mulai terjadi&#10;- Apa yang sudah Anda coba&#10;- Dampak masalah terhadap pekerjaan"
          class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
        ></textarea>
        <div v-if="error?.description" class="flex items-center mt-2">
          <p class="text-xs text-red-500">{{ error.description[0] }}</p>
        </div>
      </div>

      <!-- Prioritas -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Prioritas</label
        >
        <div class="grid grid-cols-3 gap-4">
          <label
            class="relative flex cursor-pointer rounded-lg border"
            :class="[
              form.priority === 'low'
                ? 'border-green-200 bg-green-50'
                : 'border-gray-200',
            ]"
          >
            <input
              type="radio"
              v-model="form.priority"
              value="low"
              class="sr-only"
            />
            <div class="flex w-full items-center justify-between p-4">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-gray-900">Rendah</p>
                  <p class="text-gray-500">Tidak mendesak</p>
                </div>
              </div>
              <div
                class="shrink-0 text-green-600"
                v-show="form.priority === 'low'"
              >
                <CheckCircle :size="24" />
              </div>
            </div>
          </label>
          <label
            class="relative flex cursor-pointer rounded-lg border"
            :class="[
              form.priority === 'medium'
                ? 'border-yellow-200 bg-yellow-50'
                : 'border-gray-200',
            ]"
          >
            <input
              type="radio"
              v-model="form.priority"
              value="medium"
              class="sr-only"
            />
            <div class="flex w-full items-center justify-between p-4">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-gray-900">Sedang</p>
                  <p class="text-gray-500">Normal</p>
                </div>
              </div>
              <div
                class="shrink-0 text-yellow-600"
                v-show="form.priority === 'medium'"
              >
                <CheckCircle :size="24" />
              </div>
            </div>
          </label>
          <label
            class="relative flex cursor-pointer rounded-lg border"
            :class="[
              form.priority === 'high'
                ? 'border-red-200 bg-red-50'
                : 'border-gray-200',
            ]"
          >
            <input
              type="radio"
              v-model="form.priority"
              value="high"
              class="sr-only"
            />
            <div class="flex w-full items-center justify-between p-4">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-gray-900">Tinggi</p>
                  <p class="text-gray-500">Mendesak</p>
                </div>
              </div>
              <div
                class="shrink-0 text-red-600"
                v-show="form.priority === 'high'"
              >
                <CheckCircle :size="24" />
              </div>
            </div>
          </label>
        </div>
        <div v-if="error?.priority" class="flex items-center mt-2">
          <p class="text-xs text-red-500">{{ error.priority[0] }}</p>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end space-x-4">
        <RouterLink
          :to="{ name: 'app.dashboard' }"
          class="px-6 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50"
        >
          Batal
        </RouterLink>
        <button
          type="submit"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
          :disabled="loading"
        >
          <Send :size="16" class="inline-block mr-2" />
          {{ loading ? "Mengirim..." : "Lanjut ke Lampiran" }}
        </button>
      </div>
    </form>
  </div>

  <!-- Step 2: Attachments -->
  <div
    v-if="currentStep === 2"
    class="bg-white rounded-xl shadow-sm border border-gray-100"
  >
    <div class="p-6 border-b border-gray-100">
      <h1 class="text-2xl font-bold text-gray-800">Lampiran</h1>
      <p class="text-sm text-gray-500 mt-1">
        Upload file-file pendukung untuk tiket Anda (opsional)
      </p>
    </div>

    <div class="p-6 space-y-6">
      <!-- File Upload Area -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Pilih File
        </label>
        <div
          class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
        >
          <input
            type="file"
            multiple
            @change="handleFileSelect"
            class="hidden"
            id="file-upload"
            accept="image/*,.pdf,.doc,.docx,.txt"
          />
          <label for="file-upload" class="cursor-pointer">
            <Upload :size="48" class="mx-auto text-gray-400 mb-4" />
            <p class="text-sm text-gray-600 mb-2">
              Klik untuk memilih file atau drag & drop
            </p>
            <p class="text-xs text-gray-500">
              Maksimal 10MB per file. Format: JPG, PNG, PDF, DOC, DOCX, TXT
            </p>
          </label>
        </div>
      </div>

      <!-- Selected Files List -->
      <div v-if="selectedFiles.length > 0">
        <h3 class="text-sm font-medium text-gray-700 mb-3">File Terpilih:</h3>
        <div class="space-y-2">
          <div
            v-for="(file, index) in selectedFiles"
            :key="index"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center">
              <Image
                v-if="file.type.startsWith('image/')"
                :size="20"
                class="text-blue-600 mr-3"
              />
              <FileText v-else :size="20" class="text-gray-600 mr-3" />
              <div>
                <p class="text-sm font-medium text-gray-800">{{ file.name }}</p>
                <p class="text-xs text-gray-500">
                  {{ (file.size / 1024 / 1024).toFixed(2) }} MB
                </p>
              </div>
            </div>
            <button
              @click="removeFile(index)"
              class="text-red-500 hover:text-red-700"
            >
              <X :size="16" />
            </button>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between">
        <button
          @click="skipAttachments"
          class="px-6 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50"
        >
          Lewati Lampiran
        </button>
        <button
          @click="uploadAttachments"
          :disabled="attachmentLoading || selectedFiles.length === 0"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50"
        >
          <Upload :size="16" class="inline-block mr-2" />
          {{ attachmentLoading ? "Mengupload..." : "Selesai" }}
        </button>
      </div>
    </div>
  </div>
</template>
