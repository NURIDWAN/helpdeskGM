<script setup>
import { onMounted, ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFormPermintaanStore } from "@/stores/formPermintaan";
import { storeToRefs } from "pinia";
import FormCard from "@/components/common/FormCard.vue";
import {
  ArrowLeft,
  ChevronRight,
  ClipboardList,
  Calendar,
  User,
  Building,
  AlertTriangle,
  FileText,
  Image,
  X,
  Download,
  Paperclip,
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();

const formPermintaanStore = useFormPermintaanStore();
const { loading, error } = storeToRefs(formPermintaanStore);
const { downloadAttachment: downloadFormAttachment, downloadPDF } = formPermintaanStore;

const formData = ref(null);

const handleDownloadPDF = async () => {
  if (formData.value) {
    await downloadPDF(formId.value, formData.value.request_number);
  }
};

const formId = computed(() => route.params.id);
const routePrefix = computed(() =>
  route.name?.startsWith("admin.") ? "admin" : "app"
);
const dashboardRoute = computed(() => ({
  name: `${routePrefix.value}.dashboard`,
}));
const listRoute = computed(() => ({
  name: `${routePrefix.value}.form-permintaan`,
}));

const requestTypeLabels = {
  pembelian_produk_baru: "Pembelian Produk (Unit) Baru",
  penggantian_produk_lama: "Penggantian Produk (Unit) Lama",
  servis: "Servis",
  penggantian_part: "Penggantian Part",
  jasa: "Jasa",
};

const priorityColors = {
  low: "bg-green-100 text-green-800",
  medium: "bg-yellow-100 text-yellow-800",
  high: "bg-orange-100 text-orange-800",
  urgent: "bg-red-100 text-red-800",
};

const showFaNumber = computed(() => {
  const type = formData.value?.request_type;
  return ["penggantian_produk_lama", "servis", "penggantian_part"].includes(type);
});

const showAlasan = computed(() => {
  return formData.value?.request_type === "pembelian_produk_baru";
});

const generalAttachments = computed(() =>
  formData.value?.attachments || []
);

// Attachment preview dialog
const showPreviewDialog = ref(false);
const selectedAttachment = ref(null);

const isImageFile = (attachment) => {
  const type = attachment.file_type?.toLowerCase() || "";
  return type.startsWith("image/") || type === "jpg" || type === "png" || type === "jpeg";
};

const getAttachmentUrl = (attachment) => {
  return attachment?.url || attachment?.file_path || "#";
};

const downloadAttachment = async (attachment) => {
  await downloadFormAttachment(formId.value, attachment);
};

const openPreview = (attachment) => {
  selectedAttachment.value = attachment;
  showPreviewDialog.value = true;
};

const closePreview = () => {
  showPreviewDialog.value = false;
  selectedAttachment.value = null;
};

const loadFormData = async () => {
  if (formId.value) {
    try {
      const response = await formPermintaanStore.fetchFormPermintaan(formId.value);
      if (response) {
        formData.value = response;
      } else {
        // If no data returned (404 or unauthorized), redirect to error page
        router.push({ name: "error.notfound" });
      }
    } catch (err) {
      console.error("Error loading form permintaan:", err);
      router.push({ name: "error.notfound" });
    }
  }
};

onMounted(() => {
  loadFormData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="listRoute"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Detail Form Permintaan</h1>
        <p class="text-gray-600 mt-1">Informasi lengkap form permintaan</p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="dashboardRoute" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink :to="listRoute" class="hover:text-gray-700">
        Form Permintaan
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">Detail</span>
    </nav>
  </div>

  <!-- Content -->
  <div v-if="formData" class="space-y-6">
    <!-- Header Information -->
    <FormCard
      title="Informasi Permintaan"
      subtitle="Detail lengkap form permintaan"
      :icon="ClipboardList"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- No. Permintaan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            No. Permintaan
          </label>
          <p class="text-gray-900 font-semibold">{{ formData.request_number || "-" }}</p>
        </div>

        <!-- Tanggal -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Tanggal
          </label>
          <p class="text-gray-900">
            {{
              formData.date
                ? new Date(formData.date).toLocaleDateString("id-ID", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                  })
                : "-"
            }}
          </p>
        </div>

        <!-- User -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            User
          </label>
          <p class="text-gray-900">{{ formData.user?.name || "-" }}</p>
        </div>

        <!-- Outlet -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Building :size="16" class="inline mr-2" />
            Outlet
          </label>
          <p class="text-gray-900">{{ formData.branch?.name || "-" }}</p>
        </div>

        <!-- Prioritas -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <AlertTriangle :size="16" class="inline mr-2" />
            Prioritas
          </label>
          <span
            :class="priorityColors[formData.priority] || 'bg-gray-100 text-gray-800'"
            class="px-3 py-1 rounded-full text-sm font-medium"
          >
            {{ formData.priority ? formData.priority.charAt(0).toUpperCase() + formData.priority.slice(1) : "-" }}
          </span>
        </div>

        <!-- Ticket Terkait -->
        <div v-if="formData.ticket">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Ticket Terkait
          </label>
          <RouterLink
            :to="{ name: `${routePrefix}.ticket.detail`, params: { id: formData.ticket.id } }"
            class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
          >
            {{ formData.ticket.code || "-" }}
          </RouterLink>
          <p class="mt-1 text-sm text-gray-500">{{ formData.ticket.description || "-" }}</p>
        </div>

        <!-- Jenis Permintaan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Jenis Permintaan
          </label>
          <p class="text-gray-900">{{ requestTypeLabels[formData.request_type] || formData.request_type || "-" }}</p>
        </div>

        <!-- FA Number (conditional) -->
        <div v-if="showFaNumber">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <FileText :size="16" class="inline mr-2" />
            No FA
          </label>
          <p class="text-gray-900">{{ formData.fa_number || "-" }}</p>
        </div>

        <!-- Alasan (conditional) -->
        <div v-if="showAlasan" class="lg:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <FileText :size="16" class="inline mr-2" />
            Alasan
          </label>
          <p class="text-gray-900">{{ formData.reason || "-" }}</p>
        </div>
      </div>
    </FormCard>

    <!-- Items Table -->
    <FormCard
      title="Daftar Item"
      subtitle="Item-item yang diminta"
      :icon="ClipboardList"
    >
      <div v-if="formData.items && formData.items.length > 0" class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left py-3 px-4 font-medium text-gray-600 w-12">No</th>
              <th class="text-left py-3 px-4 font-medium text-gray-600">Deskripsi Produk</th>
              <th class="text-center py-3 px-4 font-medium text-gray-600 w-20">QTY</th>
              <th class="text-left py-3 px-4 font-medium text-gray-600 w-24">UoM</th>
              <th class="text-left py-3 px-4 font-medium text-gray-600">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in formData.items"
              :key="item.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="py-3 px-4 text-gray-600">{{ index + 1 }}</td>
              <td class="py-3 px-4 text-gray-900">
                <div class="rich-text-display" v-html="item.product_description || '-'"></div>
              </td>
              <td class="py-3 px-4 text-center text-gray-900">{{ item.quantity }}</td>
              <td class="py-3 px-4 text-gray-600">{{ item.uom || "-" }}</td>
              <td class="py-3 px-4 text-gray-600">{{ item.notes || "-" }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        <ClipboardList :size="48" class="mx-auto mb-4 text-gray-300" />
        <p class="text-sm">Tidak ada item</p>
      </div>
    </FormCard>

    <!-- Attachments Section -->
    <FormCard
      title="Lampiran"
      subtitle="File lampiran yang disertakan"
      :icon="Paperclip"
    >
      <div v-if="generalAttachments.length > 0">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="attachment in generalAttachments"
            :key="attachment.id"
            @click="openPreview(attachment)"
            class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
          >
            <!-- Thumbnail for images -->
            <div class="flex-shrink-0 mr-3">
              <img
                v-if="isImageFile(attachment)"
                :src="getAttachmentUrl(attachment)"
                :alt="attachment.file_name"
                class="w-12 h-12 object-cover rounded-lg border border-gray-200"
              />
              <div
                v-else
                class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
              >
                <FileText :size="24" class="text-gray-600" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate">
                {{ attachment.file_name }}
              </p>
              <p class="text-xs text-gray-500">
                {{ attachment.file_type }}
                <span v-if="attachment.file_size">
                  · {{ (attachment.file_size / 1024).toFixed(1) }} KB
                </span>
              </p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        <Paperclip :size="48" class="mx-auto mb-4 text-gray-300" />
        <p class="text-sm">Tidak ada lampiran</p>
      </div>
    </FormCard>

    <!-- Action Buttons -->
    <div class="flex justify-start items-center gap-3 pt-6 border-t border-gray-200">
      <RouterLink
        :to="listRoute"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
      >
        Kembali ke List
      </RouterLink>
      <button
        v-if="formData.status === 'approved' || formData.status === 'completed'"
        type="button"
        @click="handleDownloadPDF"
        :disabled="loading"
        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <FileText :size="20" class="mr-2" />
        Cetak PDF
      </button>
    </div>
  </div>

  <!-- Loading State -->
  <div v-else-if="loading" class="flex justify-center items-center py-12">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
  </div>

  <!-- Error State -->
  <div v-else-if="error" class="text-center py-12">
    <AlertTriangle :size="48" class="mx-auto mb-4 text-red-400" />
    <h3 class="text-lg font-medium text-gray-900 mb-2">Gagal Memuat Data</h3>
    <p class="text-gray-500 mb-4">{{ typeof error === 'string' ? error : 'Form permintaan tidak ditemukan atau Anda tidak memiliki akses.' }}</p>
    <RouterLink
      :to="listRoute"
      class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
    >
      <ArrowLeft :size="16" class="mr-2" />
      Kembali ke List
    </RouterLink>
  </div>

  <!-- Attachment Preview Dialog -->
  <div
    v-if="showPreviewDialog && selectedAttachment"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    @click="closePreview"
  >
    <div
      class="bg-white rounded-lg max-w-4xl max-h-[90vh] overflow-hidden w-full mx-4"
      @click.stop
    >
      <!-- Dialog Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 truncate">
          {{ selectedAttachment.file_name }}
        </h3>
        <button
          @click="closePreview"
          class="text-gray-400 hover:text-gray-600 flex-shrink-0 ml-4"
        >
          <X :size="24" />
        </button>
      </div>

      <!-- Dialog Content -->
      <div class="p-4 overflow-auto max-h-[calc(90vh-8rem)]">
        <!-- Image Preview -->
        <div v-if="isImageFile(selectedAttachment)" class="text-center">
          <img
            :src="getAttachmentUrl(selectedAttachment)"
            :alt="selectedAttachment.file_name"
            class="max-w-full max-h-[70vh] object-contain mx-auto rounded-lg"
          />
        </div>
        <!-- PDF Preview -->
        <div v-else class="text-center py-8">
          <FileText :size="64" class="text-gray-400 mx-auto mb-4" />
          <p class="text-gray-600 mb-4">{{ selectedAttachment.file_name }}</p>
          <button
            type="button"
            @click="downloadAttachment(selectedAttachment)"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            <Download :size="16" class="mr-2" />
            Download File
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rich-text-display :deep(p) {
  margin: 0 0 0.5rem;
}

.rich-text-display :deep(p:last-child) {
  margin-bottom: 0;
}

.rich-text-display :deep(ul),
.rich-text-display :deep(ol) {
  margin: 0.4rem 0;
  padding-left: 1.25rem;
}

.rich-text-display :deep(ul) {
  list-style: disc;
}

.rich-text-display :deep(ol) {
  list-style: decimal;
}

.rich-text-display :deep(blockquote) {
  border-left: 3px solid rgb(147 197 253);
  color: rgb(71 85 105);
  margin: 0.5rem 0;
  padding-left: 0.75rem;
}

.rich-text-display :deep(a) {
  color: rgb(37 99 235);
  text-decoration: underline;
}
</style>
