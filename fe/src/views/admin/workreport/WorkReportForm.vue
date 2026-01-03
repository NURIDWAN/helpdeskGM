<script setup>
import { reactive, onMounted, computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useWorkReportStore } from "@/stores/workReport";
import { useUserStore } from "@/stores/user";
import { useBranchStore } from "@/stores/branch";
import { useJobTemplateStore } from "@/stores/jobTemplate";
import { useAuthStore } from "@/stores/auth";
import { useWorkOrderStore } from "@/stores/workOrder";
import { useWorkReportAttachmentStore } from "@/stores/workReportAttachment";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import AttachmentViewDialog from "@/components/common/AttachmentViewDialog.vue";
import {
  ArrowLeft,
  Save,
  ChevronRight,
  FileText,
  User,
  Building,
  Briefcase,
  ClipboardList,
  Upload,
  Trash2,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";

const route = useRoute();
const router = useRouter();

const workReportStore = useWorkReportStore();
const { error, loading } = storeToRefs(workReportStore);
const { createWorkReport, updateWorkReport, fetchWorkReport } = workReportStore;

const userStore = useUserStore();
const { users } = storeToRefs(userStore);
const { fetchUsers } = userStore;

// Branch store removed - using default branch

const jobTemplateStore = useJobTemplateStore();
const { jobTemplates } = storeToRefs(jobTemplateStore);
const { fetchJobTemplates } = jobTemplateStore;

const workOrderStore = useWorkOrderStore();
const { workOrders } = storeToRefs(workOrderStore);
const { fetchWorkOrders } = workOrderStore;

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const isStaff = computed(() => (user.value?.roles || []).includes("staff"));
const isRegularUser = computed(
  () =>
    !isStaff.value &&
    !(user.value?.roles || []).includes("admin") &&
    !(user.value?.roles || []).includes("superadmin")
);

const workReportAttachmentStore = useWorkReportAttachmentStore();
const { attachments } = storeToRefs(workReportAttachmentStore);
const { getByWorkReportId, createAttachment, deleteAttachment } =
  workReportAttachmentStore;

const isEdit = computed(() => route.name === "admin.workreport.edit");
const workReportId = computed(() => route.params.id);

// Two-step process state
const currentStep = ref(1);
const createdWorkReportId = ref(null);
const isCreating = ref(false);

// Dialog state
const showAttachmentDialog = ref(false);
const selectedAttachment = ref(null);

// File upload state
const selectedFiles = ref([]);
const isUploading = ref(false);

const statusOptions = [
  { value: "progress", label: "In Progress" },
  { value: "failed", label: "Failed" },
  { value: "completed", label: "Completed" },
];

const form = reactive({
  user_id: null,
  work_order_id: null,
  job_template_id: null,
  description: null,
  custom_job: null,
  status: "progress",
  custom_job: null,
  status: "progress",
});

const inputType = ref("template"); // 'template' or 'manual'

// Computed property to check if "laporan lainnya" is selected
// Computed property to check if "laporan lainnya" is selected - NO LONGER USED for UI visibility
// But kept if needed for validation, though we can use inputType now.
const isOtherReportSelected = computed(() => inputType.value === 'manual');



// Computed options for Work Orders (SPK)
const workOrderOptions = computed(() => {
  return [
    ...workOrders.value.map((wo) => ({
      value: String(wo.id),
      label: `${wo.number} - ${wo.ticket?.title || 'No Ticket'}`,
    })),
    {
      value: 'other',
      label: 'Laporan Lainnya / Non-SPK',
    }
  ];
});

// Watch for Work Order changes
// Watch for Input Type changes to clear hidden fields
watch(inputType, (newValue) => {
  if (newValue === 'template') {
    form.custom_job = ""; // Clear manual input
  } else {
    form.job_template_id = null; // Clear template selection
  }
});

watch(
  () => form.work_order_id,
  (newValue) => {
    if (newValue === 'other') {
      // If "Laporan Lainnya" selected in SPK, suggest Manual input
      inputType.value = 'manual';
    } 
  }
);

const handleSubmit = async () => {
  if (isEdit.value) {
    // Edit mode: Update work report
    await handleUpdateWorkReport();
  } else if (currentStep.value === 1) {
    // Step 1: Create work report
    await handleCreateWorkReport();
  } else {
    // Step 2: Upload attachments and finish
    await handleFinishWithAttachments();
  }
};

const handleCreateWorkReport = async () => {
  isCreating.value = true;
  try {
    // Validate custom job when "laporan lainnya" is selected
    if (isOtherReportSelected.value && !(form.custom_job || "").trim()) {
      workReportStore.error = {
        custom_job: ["Pekerjaan wajib diisi untuk laporan lainnya"],
      };
      return;
    }

    const payload = {
      user_id: form.user_id,
      work_order_id: form.work_order_id === 'other' ? null : (form.work_order_id || null),
      job_template_id:
        form.job_template_id === "laporan_lainnya"
          ? null
          : form.job_template_id || null,
      description: form.description || "",
      custom_job: form.custom_job || "",
      status: form.status,
    };

    const workReport = await createWorkReport(payload);

    if (!error.value && workReport) {
      createdWorkReportId.value = workReport.id;
      currentStep.value = 2;
    }
  } catch (error) {
    console.error("Error creating work report:", error);
  } finally {
    isCreating.value = false;
  }
};

const handleUpdateWorkReport = async () => {
  isCreating.value = true;
  try {
    // Validate custom job when "laporan lainnya" is selected
    if (isOtherReportSelected.value && !(form.custom_job || "").trim()) {
      workReportStore.error = {
        custom_job: ["Pekerjaan wajib diisi untuk laporan lainnya"],
      };
      return;
    }

    const payload = {
      user_id: form.user_id,
      work_order_id: form.work_order_id === 'other' ? null : (form.work_order_id || null),
      job_template_id:
        form.job_template_id === "laporan_lainnya"
          ? null
          : form.job_template_id || null,
      description: form.description || null,
      custom_job: form.custom_job || null,
      status: form.status,
    };

    await updateWorkReport(workReportId.value, payload);

    if (!error.value) {
      router.push({ name: "admin.workreports" });
    }
  } catch (error) {
    console.error("Error updating work report:", error);
  } finally {
    isCreating.value = false;
  }
};

const handleFinishWithAttachments = async () => {
  // Upload any selected files
  if (selectedFiles.value.length > 0) {
    await uploadAttachments();
  }

  // Navigate back to list
  router.push({ name: "admin.workreports" });
};

const handleSkipAttachments = () => {
  router.push({ name: "admin.workreports" });
};

const goBackToStep1 = () => {
  currentStep.value = 1;
};

const loadWorkReportData = async () => {
  if (isEdit.value && workReportId.value) {
    try {
      const workReport = await fetchWorkReport(workReportId.value);
      if (workReport) {
        form.user_id = workReport.user?.id;
        // If work_order_id is null, it might be an "Other" report. We'll set it to 'other' if custom_job is present and no SPK?
        // Or just leave it null. But to be consistent with UI options:
        form.work_order_id = workReport.work_order_id ? String(workReport.work_order_id) : (workReport.custom_job ? 'other' : null);
        
        // If no job template but has custom_job, set to "laporan_lainnya"
        form.job_template_id =
          workReport.job_template?.id ||
          (workReport.custom_job ? "laporan_lainnya" : "");
        form.description = workReport.description;
        form.custom_job = workReport.custom_job;
        form.status = workReport.status || "progress";
        
        // Set input type based on data
        if (workReport.custom_job && !workReport.job_template) {
          inputType.value = "manual";
        } else {
          inputType.value = "template";
        }
      }
    } catch (error) {
      console.error("Error loading work report data:", error);
      router.push({ name: "admin.workreports" });
    }
  } else if (!isEdit.value) {
    // For staff creating new report, auto-set user_id to current user
    if (isStaff.value) {
      form.user_id = user.value?.id;
    }

    // Pre-fill from query params (e.g. from Job Calendar)
    if (route.query.job_template_id) {
        form.job_template_id = String(route.query.job_template_id);
        inputType.value = 'template';
    }
  }
};

const loadUsersData = async () => {
  // Only load users if not staff (admin needs to see all users)
  if (!isStaff.value) {
    try {
      await fetchUsers();
    } catch (error) {
      console.error("Error loading users:", error);
    }
  }
};

// loadBranchesData removed - using default branch

const loadJobTemplatesData = async () => {
  try {
    await fetchJobTemplates();
  } catch (error) {
    console.error("Error loading job templates:", error);
  }
};

const loadWorkOrdersData = async () => {
  try {
    // Staff should only see their own work orders ideally, but list all for now or filter
    // If strict role separation is needed in store fetch
    // But store fetchWorkOrders currently gets paginated or active?
    // Using fetchWorkOrders (all without pagination by default if no params, wait, store implementation uses params)
    // Actually store checks params. If no params, it fetches /work-orders which returns all?
    // Let's assume it returns list.
    await fetchWorkOrders(); 
  } catch (error) {
    console.error("Error loading work orders:", error);
  }
};

const loadAttachmentsData = async () => {
  const reportId = isEdit.value
    ? workReportId.value
    : createdWorkReportId.value;
  if (reportId) {
    try {
      await getByWorkReportId(reportId);
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
    const reportId = isEdit.value
      ? workReportId.value
      : createdWorkReportId.value;
    for (const file of selectedFiles.value) {
      const formData = new FormData();
      formData.append("file", file);
      await createAttachment(reportId, formData);
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

// Watch for job template changes to clear custom job when switching away from "laporan lainnya"


onMounted(() => {
  loadUsersData();
  loadJobTemplatesData();
  loadWorkOrdersData();
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
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEdit ? "Edit Laporan Kerja" : "Tambah Laporan Kerja Baru" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi laporan kerja yang sudah ada"
              : currentStep === 1
              ? "Tambahkan laporan kerja baru ke dalam sistem"
              : "Tambahkan attachment untuk laporan kerja"
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
      <RouterLink
        :to="{ name: 'admin.workreports' }"
        class="hover:text-gray-700"
      >
        Data Laporan Kerja
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Step Indicators (Create mode only) -->
  <div v-if="!isEdit" class="mb-8">
    <div class="flex items-center justify-center">
      <div class="flex items-center space-x-4">
        <!-- Step 1 -->
        <div class="flex items-center">
          <div
            :class="
              currentStep >= 1
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 text-gray-600'
            "
            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          >
            1
          </div>
          <span
            :class="currentStep >= 1 ? 'text-blue-600' : 'text-gray-500'"
            class="ml-2 text-sm font-medium"
          >
            Informasi Laporan
          </span>
        </div>

        <!-- Arrow -->
        <div class="w-8 h-0.5 bg-gray-300"></div>

        <!-- Step 2 -->
        <div class="flex items-center">
          <div
            :class="
              currentStep >= 2
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 text-gray-600'
            "
            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
          >
            2
          </div>
          <span
            :class="currentStep >= 2 ? 'text-blue-600' : 'text-gray-500'"
            class="ml-2 text-sm font-medium"
          >
            Attachments
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Step 1: Work Report Information -->
  <FormCard
    v-if="currentStep === 1 || isEdit"
    title="Informasi Laporan Kerja"
    subtitle="Lengkapi data laporan kerja dengan benar"
    :icon="FileText"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- User (only for non-staff) -->
        <div v-if="!isStaff">
          <FormField
            v-model="form.user_id"
            id="user_id"
            name="user_id"
            label="User"
            :label-icon="User"
            :error="error?.user_id?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih user"
            :options="
              users.map((u) => ({ value: String(u.id), label: u.name }))
            "
          />
        </div>

        <!-- Work Order (SPK) -->
        <div>
           <FormField
            v-model="form.work_order_id"
            id="work_order_id"
            name="work_order_id"
            label="Nomor SPK / Work Order"
            :label-icon="FileText"
            :error="error?.work_order_id?.join(', ')"
            type="select"
            placeholder="Pilih SPK (Opsional)"
            :options="workOrderOptions"
          />
        </div>

        <!-- Job Template -->


        <!-- Job Input Type Switch -->
        <div class="lg:col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
          <label class="block text-sm font-medium text-gray-700 mb-3">Tipe Pekerjaan</label>
          <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="inputType" value="template" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
              <span class="text-sm text-gray-700">Pilih dari Template</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="inputType" value="manual" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
              <span class="text-sm text-gray-700">Input Manual / Lainnya</span>
            </label>
          </div>
        </div>

        <!-- Job Template Input -->
        <div v-if="inputType === 'template'">
          <FormField
            v-model="form.job_template_id"
            id="job_template_id"
            name="job_template_id"
            label="Pilih Template Job"
            :label-icon="ClipboardList"
            :error="error?.job_template_id?.join(', ')"
            type="select"
            placeholder="Pilih template"
            :options="
              jobTemplates.map((t) => ({ value: String(t.id), label: t.name }))
            "
          />
        </div>

        <!-- Custom Job Input -->
        <div v-if="inputType === 'manual'" class="lg:col-span-2">
           <FormField
              v-model="form.custom_job"
              id="custom_job"
              name="custom_job"
              label="Nama Pekerjaan"
              :label-icon="Briefcase"
              :error="error?.custom_job?.join(', ')"
              type="text"
              placeholder="Masukkan nama pekerjaan"
              :required="true"
            />
            <p class="mt-1 text-xs text-gray-500">Masukkan detail pekerjaan secara manual.</p>
        </div>




        <!-- Status -->
        <div>
          <FormField
            v-model="form.status"
            id="status"
            name="status"
            label="Status"
            :label-icon="Briefcase"
            :error="error?.status?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih status"
            :options="statusOptions"
          />
        </div>

        <!-- Description -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.description"
            id="description"
            name="description"
            label="Deskripsi"
            :label-icon="FileText"
            placeholder="Jelaskan detail laporan kerja"
            :error="error?.description?.join(', ')"
            type="textarea"
            :rows="4"
          />
        </div>
      </div>

      <!-- File Upload Section (Edit only) -->
      <div v-if="isEdit" class="mt-8 pt-6 border-t border-gray-200">
        <h3
          class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
        >
          <Upload :size="20" />
          Upload Attachments
        </h3>

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
          <h4 class="text-sm font-medium text-gray-700 mb-2">
            Selected Files:
          </h4>
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

        <!-- Existing Attachments -->
        <div v-if="attachments.length > 0" class="mt-6">
          <h4 class="text-sm font-medium text-gray-700 mb-3">
            Existing Attachments:
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="attachment in attachments"
              :key="attachment.id"
              class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200"
            >
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div
                    v-if="attachment.file_type.startsWith('image/')"
                    class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
                  >
                    <img
                      :src="attachment.file_path"
                      :alt="`Attachment ${attachment.id}`"
                      class="w-10 h-10 object-cover rounded"
                      @error="$event.target.style.display = 'none'"
                    />
                  </div>
                  <div
                    v-else
                    class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center"
                  >
                    <FileText :size="20" class="text-blue-600" />
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{
                      attachment.file_type.split("/")[1]?.toUpperCase() ||
                      "FILE"
                    }}
                  </p>
                  <p class="text-xs text-gray-500 mt-1">
                    {{
                      new Date(attachment.created_at).toLocaleDateString(
                        "id-ID"
                      )
                    }}
                  </p>
                  <div class="flex gap-2 mt-2">
                    <button
                      @click="openAttachmentDialog(attachment)"
                      class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 transition-colors"
                    >
                      <FileText :size="12" />
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
        </div>
      </div>

      <!-- Action Buttons -->
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pt-8 mt-8 border-t border-gray-200"
      >
        <div class="text-sm text-gray-500 text-center sm:text-left">
          <span class="text-red-500">*</span> Wajib diisi
        </div>
        <div
          class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto sm:justify-end"
        >
          <RouterLink
            :to="{ name: 'admin.workreports' }"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium text-center"
          >
            Batal
          </RouterLink>
          <button
            type="submit"
            :disabled="loading || isCreating"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-medium transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <div
              v-if="loading || isCreating"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
            ></div>
            <Save v-if="!loading && !isCreating" :size="16" />
            {{
              loading || isCreating
                ? "Menyimpan..."
                : isEdit
                ? "Update Laporan"
                : "Lanjut ke Attachments"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>

  <!-- Step 2: Attachments -->
  <FormCard
    v-if="currentStep === 2 && !isEdit"
    title="Attachments"
    subtitle="Tambahkan file-file pendukung untuk laporan kerja"
    :icon="Upload"
  >
    <div class="space-y-6">
      <!-- File Selection -->
      <div>
        <h4 class="text-sm font-medium text-gray-700 mb-3">Pilih File:</h4>
        <input
          type="file"
          multiple
          @change="handleFileSelect"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
          accept="image/*,.pdf,.doc,.docx,.txt"
        />
      </div>

      <!-- Selected Files Preview -->
      <div v-if="selectedFiles.length > 0">
        <h4 class="text-sm font-medium text-gray-700 mb-3">
          File yang Dipilih:
        </h4>
        <div class="space-y-2">
          <div
            v-for="(file, index) in selectedFiles"
            :key="index"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
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
      </div>

      <!-- Action Buttons -->
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pt-6 border-t border-gray-200"
      >
        <button
          @click="goBackToStep1"
          class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium text-center"
        >
          Kembali
        </button>
        <div
          class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto sm:justify-end"
        >
          <button
            @click="handleSkipAttachments"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium text-center"
          >
            Lewati Attachments
          </button>
          <button
            @click="handleSubmit"
            :disabled="isUploading"
            class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-medium transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <div
              v-if="isUploading"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
            ></div>
            <Upload v-if="!isUploading" :size="16" />
            {{ isUploading ? "Mengupload..." : "Selesai" }}
          </button>
        </div>
      </div>
    </div>
  </FormCard>

  <!-- Attachment View Dialog -->
  <AttachmentViewDialog
    :show="showAttachmentDialog"
    :attachment="selectedAttachment"
    @close="closeAttachmentDialog"
  />
</template>
