<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useWorkReportStore } from "@/stores/workReport";
import { useWorkReportAttachmentStore } from "@/stores/workReportAttachment";
import FormCard from "@/components/common/FormCard.vue";
import AttachmentViewDialog from "@/components/common/AttachmentViewDialog.vue";
import {
  ArrowLeft,
  ChevronRight,
  FileText,
  User,
  Building,
  Briefcase,
  ClipboardList,
  Calendar,
  Eye,
  Upload,
  Trash2,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";

const route = useRoute();
const router = useRouter();

const workReportStore = useWorkReportStore();
const { error, loading } = storeToRefs(workReportStore);
const { fetchWorkReport } = workReportStore;

const workReportAttachmentStore = useWorkReportAttachmentStore();
const { attachments } = storeToRefs(workReportAttachmentStore);
const { getByWorkReportId, createAttachment, deleteAttachment } =
  workReportAttachmentStore;

const workReportId = computed(() => route.params.id);
const workReport = ref(null);

// Dialog state
const showAttachmentDialog = ref(false);
const selectedAttachment = ref(null);

// File upload state
const selectedFiles = ref([]);
const isUploading = ref(false);

const loadWorkReportData = async () => {
  if (workReportId.value) {
    try {
      workReport.value = await fetchWorkReport(workReportId.value);
    } catch (error) {
      console.error("Error loading work report data:", error);
      router.push({ name: "admin.workreports" });
    }
  }
};

const loadAttachmentsData = async () => {
  if (workReportId.value) {
    try {
      await getByWorkReportId(workReportId.value);
    } catch (error) {
      console.error("Error loading attachments:", error);
    }
  }
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  selectedFiles.value = [...selectedFiles.value, ...files];
};

const removeSelectedFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

const uploadAttachments = async () => {
  if (selectedFiles.value.length === 0) return;

  isUploading.value = true;
  try {
    for (const file of selectedFiles.value) {
      const formData = new FormData();
      formData.append("file", file);
      await createAttachment(workReportId.value, formData);
    }

    // Clear selected files and reload attachments
    selectedFiles.value = [];
    await loadAttachmentsData();
  } catch (error) {
    console.error("Error uploading attachments:", error);
  } finally {
    isUploading.value = false;
  }
};

const handleDeleteAttachment = async (attachmentId) => {
  if (confirm("Apakah Anda yakin ingin menghapus attachment ini?")) {
    try {
      await deleteAttachment(workReportId.value, attachmentId);
      await loadAttachmentsData();
    } catch (error) {
      console.error("Error deleting attachment:", error);
    }
  }
};

const openAttachmentDialog = (attachment) => {
  selectedAttachment.value = attachment;
  showAttachmentDialog.value = true;
};

const closeAttachmentDialog = () => {
  showAttachmentDialog.value = false;
  selectedAttachment.value = null;
};

onMounted(() => {
  loadWorkReportData();
  loadAttachmentsData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.workreports' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Detail Laporan Kerja</h1>
        <p class="text-gray-600 mt-1">Informasi lengkap laporan kerja</p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="{ name: 'admin.dashboard' }" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink
        :to="{ name: 'admin.workreports' }"
        class="hover:text-gray-700"
      >
        Data Laporan Kerja
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">Detail</span>
    </nav>
  </div>

  <div v-if="workReport" class="space-y-6">
    <!-- Work Report Information -->
    <FormCard
      title="Informasi Laporan Kerja"
      subtitle="Detail lengkap laporan kerja"
      :icon="FileText"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Description -->
        <div class="lg:col-span-2">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-gray-600 leading-relaxed">
                {{ workReport.description }}
              </p>
            </div>
          </div>
        </div>

        <!-- User -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            User
          </label>
          <p class="text-gray-900">
            {{ workReport.user?.name || "N/A" }}
          </p>
        </div>

        <!-- Work Order (SPK) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <FileText :size="16" class="inline mr-2" />
            Nomor SPK
          </label>
          <div v-if="workReport.work_order" class="flex items-center gap-2">
             <span class="text-gray-900 font-medium">{{ workReport.work_order.number }}</span>
             <span v-if="workReport.work_order.ticket" class="text-xs text-gray-500">
               ({{ workReport.work_order.ticket.title }})
             </span>
          </div>
          <p v-else class="text-gray-400">Bukan dari SPK</p>
        </div>

        <!-- Job Template -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Template Job
          </label>
          <div v-if="workReport.job_template" class="flex items-center gap-2">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ workReport.job_template.name }}
            </span>
            <span class="text-xs text-gray-500">
              ({{ workReport.job_template.frequency }})
            </span>
          </div>
          <p v-else class="text-gray-400">Tidak menggunakan template</p>
        </div>

        <!-- Custom Job -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Briefcase :size="16" class="inline mr-2" />
            Pekerjaan Lainnya
          </label>
          <div v-if="workReport.custom_job" class="flex items-center gap-2">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              {{ workReport.custom_job }}
            </span>
          </div>
          <p v-else class="text-gray-400">Tidak ada pekerjaan lainnya</p>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Briefcase :size="16" class="inline mr-2" />
            Status
          </label>
          <span
            :class="
              workReport.status === 'completed'
                ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'
                : workReport.status === 'failed'
                ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800'
                : 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800'
            "
          >
            {{
              workReport.status === "completed"
                ? "Completed"
                : workReport.status === "failed"
                ? "Failed"
                : "In Progress"
            }}
          </span>
        </div>

        <!-- Created Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Tanggal Dibuat
          </label>
          <p class="text-gray-900">
            {{
              new Date(workReport.created_at).toLocaleDateString("id-ID", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
              })
            }}
          </p>
        </div>

        <!-- Updated Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Terakhir Diupdate
          </label>
          <p class="text-gray-900">
            {{
              new Date(workReport.updated_at).toLocaleDateString("id-ID", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
              })
            }}
          </p>
        </div>
      </div>
    </FormCard>

    <!-- Attachments Section -->
    <FormCard
      title="Attachments"
      subtitle="File-file yang dilampirkan"
      :icon="FileText"
    >
      <!-- File Upload Section -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3">
          Upload New Attachments:
        </h4>

        <!-- File Selection -->
        <div class="mb-4">
          <input
            type="file"
            multiple
            @change="handleFileSelect"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            accept="image/*,.pdf,.doc,.docx,.txt"
          />
        </div>

        <!-- Selected Files Preview -->
        <div v-if="selectedFiles.length > 0" class="mb-4">
          <h5 class="text-sm font-medium text-gray-700 mb-2">
            Selected Files:
          </h5>
          <div class="space-y-2">
            <div
              v-for="(file, index) in selectedFiles"
              :key="index"
              class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
            >
              <span class="text-sm text-gray-700">{{ file.name }}</span>
              <button
                @click="removeSelectedFile(index)"
                class="text-red-600 hover:text-red-800"
              >
                <Trash2 :size="16" />
              </button>
            </div>
          </div>
          <button
            @click="uploadAttachments"
            :disabled="isUploading"
            class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <div
              v-if="isUploading"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
            ></div>
            <Upload v-if="!isUploading" :size="16" />
            {{ isUploading ? "Uploading..." : "Upload Files" }}
          </button>
        </div>
      </div>

      <!-- Existing Attachments -->
      <div
        v-if="attachments.length > 0"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
      >
        <div
          v-for="attachment in attachments"
          :key="attachment.id"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200"
        >
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <div
                v-if="attachment.file_type.startsWith('image/')"
                class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden"
              >
                <img
                  :src="attachment.file_path"
                  :alt="`Attachment ${attachment.id}`"
                  class="w-full h-full object-cover"
                  @error="$event.target.style.display = 'none'"
                />
              </div>
              <div
                v-else
                class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center"
              >
                <FileText :size="24" class="text-blue-600" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{
                  attachment.file_type.split("/")[1]?.toUpperCase() || "FILE"
                }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                {{
                  new Date(attachment.created_at).toLocaleDateString("id-ID")
                }}
              </p>
              <div class="flex gap-2 mt-3">
                <button
                  @click="openAttachmentDialog(attachment)"
                  class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 transition-colors"
                >
                  <Eye :size="12" />
                  View
                </button>
                <button
                  @click="handleDeleteAttachment(attachment.id)"
                  class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-800 px-2 py-1 rounded bg-red-50 hover:bg-red-100 transition-colors"
                >
                  <Trash2 :size="12" />
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        <FileText :size="48" class="mx-auto mb-4 text-gray-300" />
        <p>Tidak ada attachment untuk laporan kerja ini</p>
      </div>
    </FormCard>

    <!-- Action Buttons -->
    <div
      class="flex justify-between items-center pt-6 border-t border-gray-200"
    >
      <RouterLink
        :to="{ name: 'admin.workreports' }"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
      >
        Kembali ke List
      </RouterLink>
      <div class="flex gap-3">
        <RouterLink
          v-if="can('work-report-edit')"
          :to="{ name: 'admin.workreport.edit', params: { id: workReportId } }"
          class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md font-medium"
        >
          Edit Laporan Kerja
        </RouterLink>
      </div>
    </div>
  </div>

  <!-- Loading State -->
  <div v-else-if="loading" class="flex justify-center items-center py-12">
    <div
      class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
    ></div>
  </div>

  <!-- Error State -->
  <div v-else-if="error" class="text-center py-12 text-red-600">
    <p>{{ error }}</p>
  </div>

  <!-- Attachment View Dialog -->
  <AttachmentViewDialog
    :show="showAttachmentDialog"
    :attachment="selectedAttachment"
    @close="closeAttachmentDialog"
  />
</template>
