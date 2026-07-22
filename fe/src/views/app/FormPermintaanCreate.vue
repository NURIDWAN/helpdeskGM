<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useFormPermintaanStore } from "@/stores/formPermintaan";
import { useAuthStore } from "@/stores/auth";
import { useTicketStore } from "@/stores/ticket";
import { useBranchStore } from "@/stores/branch";
import { useUserStore } from "@/stores/user";
import { storeToRefs } from "pinia";
import RichTextEditor from "@/components/common/RichTextEditor.vue";
import { can, hasRole } from "@/helpers/permissionHelper";
import {
  ArrowLeft,
  CheckCircle,
  Send,
  Upload,
  X,
  FileText,
  Plus,
  Trash2,
  AlertCircle,
  Download,
  ClipboardList,
} from "lucide-vue-next";

const router = useRouter();
const route = useRoute();

// Deteksi apakah berada di layout admin atau app
const listRoute = computed(() =>
  route.name?.toString().startsWith('admin.')
    ? 'admin.form-permintaan'
    : 'app.form-permintaan'
);

const formPermintaanStore = useFormPermintaanStore();
const { loading, error } = storeToRefs(formPermintaanStore);
const { createFormPermintaan, deleteAttachment, downloadAttachment: downloadFormAttachment, fetchFormPermintaan, updateFormPermintaan, uploadAttachment } = formPermintaanStore;

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);

const branchStore = useBranchStore();
const { branches } = storeToRefs(branchStore);

const userStore = useUserStore();
const { users: allUsers } = storeToRefs(userStore);

const ticketStore = useTicketStore();
const { tickets } = storeToRefs(ticketStore);
const { fetchTickets } = ticketStore;

// Step management
const currentStep = ref(1);
const createdForm = ref(null);
const selectedFiles = ref([]);
const uploadingAttachments = ref(false);
const loadingInitialData = ref(false);
const existingAttachments = ref([]);
const deletingAttachmentId = ref(null);

// Validation errors
const validationErrors = ref({});

// Admin bisa pilih outlet, user biasa auto dari branch sendiri
const canPickBranch = computed(() => can('form-permintaan-view-all'));
const selectedBranchId = ref(null);

// Hanya superadmin yang bisa pilih user (pemohon), admin default sesuai cabang sendiri
const canPickUser = computed(() => hasRole('superadmin'));
const selectedUserId = ref(null);

// Form data
const form = ref({
  ticket_id: null,
  priority: "",
  request_type: "",
  fa_number: "",
  reason: "",
  items: [
    { id: null, product_description: "", quantity: 1, uom: "", notes: "" },
  ],
});

// Current date (formatted)
const currentDate = computed(() => {
  const now = new Date();
  const day = String(now.getDate()).padStart(2, "0");
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const year = now.getFullYear();
  return `${day}/${month}/${year}`;
});

const userBranchName = computed(() => user.value?.branch?.name || "-");

const hasUserBranch = computed(() => {
  if (canPickBranch.value) return Boolean(selectedBranchId.value);
  return Boolean(user.value?.branch_id);
});

const isEditMode = computed(() => Boolean(route.params.id));

const formTitle = computed(() => isEditMode.value ? "Edit Form Permintaan" : "Form Permintaan Baru");

const formSubtitle = computed(() => isEditMode.value
  ? "Ubah data form permintaan yang masih pending"
  : "Isi form di bawah ini untuk membuat permintaan baru");

// Request type options
const requestTypeOptions = [
  { value: "pembelian_produk_baru", label: "Pembelian produk (unit) baru" },
  { value: "penggantian_produk_lama", label: "Penggantian produk (unit) lama" },
  { value: "servis", label: "Servis" },
  { value: "penggantian_part", label: "Penggantian part" },
  { value: "jasa", label: "Jasa" },
];

// Priority options
const priorityOptions = [
  { value: "low", label: "Low", color: "green", description: "Tidak mendesak" },
  { value: "medium", label: "Medium", color: "yellow", description: "Normal" },
  { value: "high", label: "High", color: "orange", description: "Mendesak" },
  { value: "urgent", label: "Urgent", color: "red", description: "Sangat mendesak" },
];

const uomOptions = [
  "Unit",
  "Pcs",
  "Set",
  "Box",
  "Pack",
  "Meter",
  "Roll",
  "Lembar",
  "Pasang",
  "Lot",
];

// Computed: show FA Number field
const showFaNumber = computed(() => {
  return ["penggantian_produk_lama", "servis", "penggantian_part"].includes(
    form.value.request_type
  );
});

// Computed: show Alasan field
const showReason = computed(() => {
  return form.value.request_type === "pembelian_produk_baru";
});

// Computed: can add more items
const canAddItem = computed(() => form.value.items.length < 20);

const availableTickets = computed(() => tickets.value.filter((ticket) => ticket.status !== "closed"));

const selectedTicket = computed(() => {
  if (!form.value.ticket_id) return null;
  return tickets.value.find((ticket) => String(ticket.id) === String(form.value.ticket_id));
});

const getTicketLabel = (ticket) => {
  const titlePart = ticket.title || `[${ticket.category?.name || "Tanpa Kategori"}]`;
  return `${titlePart} - ${ticket.code}`;
};

const getPlainText = (html) => {
  const element = document.createElement("div");
  element.innerHTML = html || "";
  return element.textContent || element.innerText || "";
};

// Add line item
const addItem = () => {
  if (!canAddItem.value) return;
  form.value.items.push({ id: null, product_description: "", quantity: 1, uom: "", notes: "" });
};

// Remove line item
const removeItem = (index) => {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1);
  }
};

// Client-side validation
const validateForm = () => {
  const errors = {};

  if (!hasUserBranch.value) {
    if (canPickBranch.value) {
      errors.branch_id = ["Outlet wajib dipilih"];
    } else {
      errors.branch_id = ["Akun Anda belum terhubung ke outlet/cabang"];
    }
  }
  if (!form.value.priority) {
    errors.priority = ["Prioritas wajib dipilih"];
  }
  if (!form.value.request_type) {
    errors.request_type = ["Jenis Permintaan wajib dipilih"];
  }

  // Conditional field validation
  if (showFaNumber.value && !form.value.fa_number.trim()) {
    errors.fa_number = ["No FA wajib diisi untuk jenis permintaan ini"];
  }
  if (showReason.value && !form.value.reason.trim()) {
    errors.reason = ["Alasan wajib diisi untuk pembelian produk baru"];
  }

  // Line items validation
  if (form.value.items.length === 0) {
    errors.items = ["Minimal satu item harus ditambahkan"];
  } else {
    const itemErrors = {};
    form.value.items.forEach((item, index) => {
      if (!getPlainText(item.product_description).trim()) {
        itemErrors[`items.${index}.product_description`] = ["Deskripsi produk wajib diisi"];
      }
      if (!item.quantity || item.quantity < 1 || item.quantity > 9999 || !Number.isInteger(Number(item.quantity))) {
        itemErrors[`items.${index}.quantity`] = ["QTY harus berupa angka bulat antara 1-9999"];
      }
      if (!item.uom) {
        itemErrors[`items.${index}.uom`] = ["UoM wajib diisi"];
      }
    });
    if (Object.keys(itemErrors).length > 0) {
      Object.assign(errors, itemErrors);
    }
  }

  validationErrors.value = errors;
  return Object.keys(errors).length === 0;
};

// Handle form submit (Step 1)
const handleSubmit = async () => {
  if (!validateForm()) return;

  uploadingAttachments.value = false;

  try {
    const payload = {
      priority: form.value.priority,
      ticket_id: form.value.ticket_id || null,
      request_type: form.value.request_type,
      fa_number: showFaNumber.value ? form.value.fa_number : null,
      reason: showReason.value ? form.value.reason : null,
      ...(canPickBranch.value && selectedBranchId.value ? { branch_id: selectedBranchId.value } : {}),
      ...(canPickUser.value && selectedUserId.value ? { user_id: selectedUserId.value } : {}),
      items: form.value.items.map((item) => ({
        id: item.id || null,
        product_description: item.product_description,
        quantity: Number(item.quantity),
        uom: item.uom || null,
        notes: item.notes || null,
      })),
    };

    const response = isEditMode.value
      ? await updateFormPermintaan(route.params.id, payload)
      : await createFormPermintaan(payload);
    if (response) {
      createdForm.value = response;
      if (isEditMode.value) {
        router.push({ name: listRoute.value });
      } else {
        if (selectedFiles.value.length > 0) {
          uploadingAttachments.value = true;
          for (const attachment of selectedFiles.value) {
            const itemId = Number.isInteger(attachment.itemIndex)
              ? response.items?.[attachment.itemIndex]?.id
              : null;
            await uploadAttachment(response.id, attachment.file, itemId);
          }
        }
        router.push({ name: listRoute.value });
      }
    }
  } catch (err) {
    // If backend returns validation errors, map them
    if (err?.response?.data?.errors) {
      validationErrors.value = err.response.data.errors;
    } else if (!isEditMode.value && selectedFiles.value.length > 0) {
      fileError.value = "Form berhasil disimpan, tetapi lampiran gagal diupload. Silakan edit form untuk menambahkan lampiran.";
    }
  } finally {
    uploadingAttachments.value = false;
  }
};

// File handling
const MAX_FILES = 10;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
const ACCEPTED_TYPES = [
  "image/jpeg",
  "image/png",
  "application/pdf",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
];
const ACCEPTED_TYPE_LABEL = "JPG, PNG, PDF, DOCX";
const fileError = ref("");

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  fileError.value = "";

  for (const file of files) {
    if (selectedFiles.value.length >= MAX_FILES) {
      fileError.value = `Maksimal ${MAX_FILES} file yang dapat diunggah`;
      break;
    }
    if (file.size > MAX_FILE_SIZE) {
      fileError.value = `File "${file.name}" melebihi batas ukuran 10MB`;
      continue;
    }
    if (!ACCEPTED_TYPES.includes(file.type)) {
      fileError.value = `File "${file.name}" tidak didukung. Format yang diterima: ${ACCEPTED_TYPE_LABEL}`;
      continue;
    }
    selectedFiles.value.push({ file, itemIndex: null });
  }

  // Reset input
  event.target.value = "";
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
  fileError.value = "";
};

const handleEditorImageSelected = async (file, itemIndex) => {
  fileError.value = "";

  if (selectedFiles.value.length >= MAX_FILES) {
    fileError.value = `Maksimal ${MAX_FILES} file yang dapat diunggah`;
    return;
  }
  if (file.size > MAX_FILE_SIZE) {
    fileError.value = `File "${file.name}" melebihi batas ukuran 10MB`;
    return;
  }
  if (!ACCEPTED_TYPES.includes(file.type)) {
    fileError.value = `File "${file.name}" tidak didukung. Format yang diterima: ${ACCEPTED_TYPE_LABEL}`;
    return;
  }

  if (isEditMode.value) {
    const itemId = form.value.items[itemIndex]?.id;
    if (!itemId) {
      fileError.value = "Simpan item terlebih dahulu sebelum menambahkan gambar.";
      return;
    }

    uploadingAttachments.value = true;
    try {
      await uploadAttachment(route.params.id, file, itemId);
      const data = await fetchFormPermintaan(route.params.id);
      fillForm(data);
    } catch (err) {
      fileError.value = "Gagal mengupload gambar. Silakan coba lagi.";
    } finally {
      uploadingAttachments.value = false;
    }
    return;
  }

  selectedFiles.value.push({ file, itemIndex });
};

const getAttachmentUrl = (attachment) => {
  return attachment?.url || attachment?.file_path || "#";
};

const downloadAttachment = async (attachment) => {
  await downloadFormAttachment(route.params.id, attachment);
};

const uploadAttachments = async () => {
  if (!createdForm.value) {
    router.push({ name: listRoute.value });
    return;
  }

  uploadingAttachments.value = true;
  try {
    for (const attachment of selectedFiles.value) {
      const itemId = Number.isInteger(attachment.itemIndex)
        ? createdForm.value.items?.[attachment.itemIndex]?.id
        : null;
      await uploadAttachment(createdForm.value.id, attachment.file, itemId);
    }
    router.push({ name: listRoute.value });
  } catch (err) {
    fileError.value = "Gagal mengupload beberapa file. Silakan coba lagi.";
  } finally {
    uploadingAttachments.value = false;
  }
};

const uploadEditAttachments = async () => {
  if (!isEditMode.value || selectedFiles.value.length === 0) return;

  uploadingAttachments.value = true;
  try {
    for (const attachment of selectedFiles.value) {
      const itemId = Number.isInteger(attachment.itemIndex)
        ? form.value.items[attachment.itemIndex]?.id
        : null;
      await uploadAttachment(route.params.id, attachment.file, itemId);
    }
    selectedFiles.value = [];
    const data = await fetchFormPermintaan(route.params.id);
    existingAttachments.value = data?.attachments || [];
  } catch (err) {
    fileError.value = "Gagal mengupload beberapa file. Silakan coba lagi.";
  } finally {
    uploadingAttachments.value = false;
  }
};

const handleDeleteAttachment = async (attachmentId) => {
  if (!isEditMode.value) return;

  deletingAttachmentId.value = attachmentId;
  try {
    await deleteAttachment(route.params.id, attachmentId);
    const data = await fetchFormPermintaan(route.params.id);
    fillForm(data);
  } finally {
    deletingAttachmentId.value = null;
  }
};

const skipAttachments = () => {
  router.push({ name: listRoute.value });
};

const fillForm = (data) => {
  if (!data) return;

  form.value.priority = data.priority || "";
  form.value.ticket_id = data.ticket_id || data.ticket?.id || null;
  form.value.request_type = data.request_type || "";
  form.value.fa_number = data.fa_number || "";
  form.value.reason = data.reason || "";
  form.value.items = Array.isArray(data.items) && data.items.length > 0
    ? data.items.map((item) => ({
      id: item.id || null,
      product_description: item.product_description || "",
      quantity: Number(item.quantity) || 1,
      uom: item.uom || "",
      notes: item.notes || "",
    }))
    : [{ id: null, product_description: "", quantity: 1, uom: "", notes: "" }];
  existingAttachments.value = data.attachments || [];
};

// Helper: get preview URL for image files
const getFilePreviewUrl = (file) => {
  return URL.createObjectURL(file);
};

// Helper: format file size
const formatFileSize = (bytes) => {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

// Helper: get error for item field
const getItemError = (index, field) => {
  const key = `items.${index}.${field}`;
  return validationErrors.value[key] ? validationErrors.value[key][0] : "";
};

// Priority color map
const getPriorityClasses = (value, selected) => {
  const colorMap = {
    low: { border: "border-green-200 bg-green-50", check: "text-green-600" },
    medium: { border: "border-yellow-200 bg-yellow-50", check: "text-yellow-600" },
    high: { border: "border-orange-200 bg-orange-50", check: "text-orange-600" },
    urgent: { border: "border-red-200 bg-red-50", check: "text-red-600" },
  };
  if (selected) return colorMap[value]?.border || "border-gray-200";
  return "border-gray-200";
};

const getPriorityCheckClass = (value) => {
  const colorMap = {
    low: "text-green-600",
    medium: "text-yellow-600",
    high: "text-orange-600",
    urgent: "text-red-600",
  };
  return colorMap[value] || "text-gray-600";
};

onMounted(async () => {
  if (!user.value) {
    await authStore.checkAuth();
  }

  await fetchTickets();

  // Admin perlu list semua branch untuk dropdown
  if (canPickBranch.value) {
    await branchStore.fetchBranches({ limit: 200 });
  }

  // Superadmin perlu list semua user untuk dropdown pemohon
  if (canPickUser.value) {
    await userStore.fetchUsers({ limit: 200 });
  }

  if (isEditMode.value) {
    loadingInitialData.value = true;
    try {
      const data = await fetchFormPermintaan(route.params.id);
      fillForm(data);
      // Set selectedBranchId dari data yang ada jika edit mode admin
      if (canPickBranch.value && data?.branch_id) {
        selectedBranchId.value = data.branch_id;
      }
      // Set selectedUserId dari data yang ada jika edit mode superadmin
      if (canPickUser.value && data?.user_id) {
        selectedUserId.value = data.user_id;
      }
    } finally {
      loadingInitialData.value = false;
    }
  }
});
</script>

<template>
  <!-- Back Button -->
  <div class="mb-6">
    <RouterLink
      v-if="currentStep === 1"
      :to="{ name: listRoute }"
      class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800"
    >
      <ArrowLeft :size="16" class="mr-2" />
      Kembali ke Daftar Form Permintaan
    </RouterLink>
    <button
      v-else
      @click="skipAttachments"
      class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800"
    >
      <ArrowLeft :size="16" class="mr-2" />
      Lewati & Selesai
    </button>
  </div>

  <!-- Step 1: Form Information -->
  <div v-if="currentStep === 1" class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
      <h1 class="text-2xl font-bold text-gray-800">{{ formTitle }}</h1>
      <p class="text-sm text-gray-500 mt-1">
        {{ formSubtitle }}
      </p>
    </div>

    <!-- Branch account error (hanya untuk user biasa yang tidak punya branch) -->
    <div v-if="!canPickBranch && !user?.branch_id" class="p-4 bg-red-50 border-l-4 border-red-400">
      <div class="flex items-center">
        <AlertCircle :size="16" class="text-red-600 mr-2" />
        <p class="text-sm text-red-700">
          Akun Anda belum terhubung ke outlet/cabang. Hubungi admin untuk mengatur cabang akun.
        </p>
      </div>
    </div>

    <!-- Backend error -->
    <div v-if="error && typeof error === 'string'" class="p-4 bg-red-50 border-l-4 border-red-400">
      <div class="flex items-center">
        <AlertCircle :size="16" class="text-red-600 mr-2" />
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
    </div>

    <div v-if="loadingInitialData" class="p-8 text-center">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-2 text-sm text-gray-600">Memuat data form...</p>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="p-6 space-y-6">
      <!-- Tanggal & User (readonly) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
          <input
            type="text"
            :value="currentDate"
            readonly
            class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600 cursor-not-allowed"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
          <!-- Superadmin: dropdown pilih user -->
          <select
            v-if="canPickUser"
            v-model="selectedUserId"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          >
            <option :value="null">-- Pilih User (default: {{ user?.name }}) --</option>
            <option v-for="u in allUsers" :key="u.id" :value="u.id">
              {{ u.name }} {{ u.branch ? `(${u.branch.name})` : '' }}
            </option>
          </select>
          <!-- User biasa: readonly -->
          <input
            v-else
            type="text"
            :value="user?.name || '-'"
            readonly
            class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600 cursor-not-allowed"
          />
        </div>
      </div>

      <!-- Outlet -->
      <div>
        <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">
          Outlet <span class="text-red-500">*</span>
        </label>
        <!-- Admin: dropdown pilih outlet -->
        <select
          v-if="canPickBranch"
          id="branch_id"
          v-model="selectedBranchId"
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          :class="{ 'border-red-500': validationErrors.branch_id }"
        >
          <option :value="null">-- Pilih Outlet --</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </option>
        </select>
        <!-- User biasa: readonly dari branch sendiri -->
        <input
          v-else
          id="branch_id"
          type="text"
          :value="userBranchName"
          readonly
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-600 cursor-not-allowed"
          :class="{ 'border-red-500': validationErrors.branch_id }"
        />
        <p v-if="validationErrors.branch_id" class="text-xs text-red-500 mt-1">
          {{ validationErrors.branch_id[0] }}
        </p>
      </div>

      <!-- Ticket Optional -->
      <div>
        <label for="ticket_id" class="block text-sm font-medium text-gray-700 mb-2">
          Ticket <span class="text-gray-400 text-xs font-normal">(Opsional)</span>
        </label>
        <select
          id="ticket_id"
          v-model="form.ticket_id"
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          :class="{ 'border-red-500': validationErrors.ticket_id }"
        >
          <option :value="null">-- Tidak ada ticket --</option>
          <option v-for="ticket in availableTickets" :key="ticket.id" :value="String(ticket.id)">
            {{ getTicketLabel(ticket) }}
          </option>
        </select>
        <p v-if="validationErrors.ticket_id" class="text-xs text-red-500 mt-1">
          {{ validationErrors.ticket_id[0] }}
        </p>
      </div>

      <div v-if="selectedTicket" class="rounded-lg border border-blue-100 bg-blue-50 p-4">
        <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-900">
          <ClipboardList :size="16" />
          Detail Ticket
        </h4>
        <div class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm text-blue-800 md:grid-cols-2">
          <div class="flex gap-2">
            <span class="min-w-[80px] font-medium">Kode:</span>
            <span>{{ selectedTicket.code || "-" }}</span>
          </div>
          <div class="flex gap-2">
            <span class="min-w-[80px] font-medium">Kategori:</span>
            <span>{{ selectedTicket.category?.name || "-" }}</span>
          </div>
          <div class="flex gap-2">
            <span class="min-w-[80px] font-medium">Pelapor:</span>
            <span>{{ selectedTicket.user?.name || "-" }}</span>
          </div>
          <div class="flex gap-2">
            <span class="min-w-[80px] font-medium">Prioritas:</span>
            <span class="capitalize">{{ selectedTicket.priority || "-" }}</span>
          </div>
          <div class="col-span-1 flex gap-2 border-t border-blue-200 pt-2 md:col-span-2">
            <span class="min-w-[80px] font-medium">Deskripsi:</span>
            <span class="italic text-blue-900">{{ selectedTicket.description || "-" }}</span>
          </div>
        </div>
      </div>

      <!-- Prioritas -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Prioritas <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <label
            v-for="option in priorityOptions"
            :key="option.value"
            class="relative flex cursor-pointer rounded-lg border"
            :class="getPriorityClasses(option.value, form.priority === option.value)"
          >
            <input
              type="radio"
              v-model="form.priority"
              :value="option.value"
              class="sr-only"
            />
            <div class="flex w-full items-center justify-between p-4">
              <div class="text-sm">
                <p class="font-medium text-gray-900">{{ option.label }}</p>
                <p class="text-gray-500">{{ option.description }}</p>
              </div>
              <div
                :class="getPriorityCheckClass(option.value)"
                class="shrink-0"
                v-show="form.priority === option.value"
              >
                <CheckCircle :size="24" />
              </div>
            </div>
          </label>
        </div>
        <p v-if="validationErrors.priority" class="text-xs text-red-500 mt-1">
          {{ validationErrors.priority[0] }}
        </p>
      </div>

      <!-- Jenis Permintaan -->
      <div>
        <label for="request_type" class="block text-sm font-medium text-gray-700 mb-2">
          Jenis Permintaan <span class="text-red-500">*</span>
        </label>
        <select
          id="request_type"
          v-model="form.request_type"
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          :class="{ 'border-red-500': validationErrors.request_type }"
        >
          <option value="">Pilih Jenis Permintaan</option>
          <option v-for="opt in requestTypeOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
        <p v-if="validationErrors.request_type" class="text-xs text-red-500 mt-1">
          {{ validationErrors.request_type[0] }}
        </p>
      </div>

      <!-- FA Number (conditional) -->
      <div v-if="showFaNumber">
        <label for="fa_number" class="block text-sm font-medium text-gray-700 mb-2">
          No FA <span class="text-red-500">*</span>
        </label>
        <input
          id="fa_number"
          type="text"
          v-model="form.fa_number"
          placeholder="Masukkan nomor FA"
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          :class="{ 'border-red-500': validationErrors.fa_number }"
        />
        <p v-if="validationErrors.fa_number" class="text-xs text-red-500 mt-1">
          {{ validationErrors.fa_number[0] }}
        </p>
      </div>

      <!-- Alasan (conditional) -->
      <div v-if="showReason">
        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
          Alasan <span class="text-red-500">*</span>
        </label>
        <textarea
          id="reason"
          v-model="form.reason"
          rows="3"
          placeholder="Masukkan alasan pembelian produk baru"
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          :class="{ 'border-red-500': validationErrors.reason }"
        ></textarea>
        <p v-if="validationErrors.reason" class="text-xs text-red-500 mt-1">
          {{ validationErrors.reason[0] }}
        </p>
      </div>

      <!-- Line Items -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="block text-sm font-medium text-gray-700">
            Item Permintaan <span class="text-red-500">*</span>
          </label>
          <span class="text-xs text-gray-500">{{ form.items.length }}/20 item</span>
        </div>

        <p v-if="validationErrors.items" class="text-xs text-red-500 mb-2">
          {{ validationErrors.items[0] }}
        </p>

        <!-- Items List -->
        <div class="space-y-4">
          <div
            v-for="(item, index) in form.items"
            :key="index"
            class="rounded-lg border border-gray-200 bg-white p-4"
          >
            <div class="mb-3 flex items-center justify-between">
              <h3 class="text-sm font-semibold text-gray-800">Item {{ index + 1 }}</h3>
              <button
                type="button"
                @click="removeItem(index)"
                :disabled="form.items.length <= 1"
                class="inline-flex items-center rounded-md p-2 text-red-500 hover:bg-red-50 hover:text-red-700 disabled:text-gray-300 disabled:hover:bg-transparent disabled:cursor-not-allowed"
                title="Hapus item"
              >
                <Trash2 :size="16" />
              </button>
            </div>

            <div class="space-y-4">
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">
                  Deskripsi Produk <span class="text-red-500">*</span>
                </label>
                <RichTextEditor
                  v-model="item.product_description"
                  :id="`product_description_${index}`"
                  placeholder="Merk, tipe, spesifikasi, ukuran, warna, atau detail produk"
                  min-height="150px"
                  :error="Boolean(getItemError(index, 'product_description'))"
                  @image-selected="(file) => handleEditorImageSelected(file, index)"
                />
                <p v-if="getItemError(index, 'product_description')" class="mt-1 text-xs text-red-500">
                  {{ getItemError(index, 'product_description') }}
                </p>
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-[120px_160px_1fr]">
                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">
                    QTY <span class="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    v-model.number="item.quantity"
                    min="1"
                    max="9999"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-red-500': getItemError(index, 'quantity') }"
                  />
                  <p v-if="getItemError(index, 'quantity')" class="mt-1 text-xs text-red-500">
                    {{ getItemError(index, 'quantity') }}
                  </p>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">
                    UoM <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="item.uom"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    :class="{ 'border-red-500': getItemError(index, 'uom') }"
                  >
                    <option value="">Pilih UoM</option>
                    <option v-for="option in uomOptions" :key="option" :value="option">
                      {{ option }}
                    </option>
                  </select>
                  <p v-if="getItemError(index, 'uom')" class="mt-1 text-xs text-red-500">
                    {{ getItemError(index, 'uom') }}
                  </p>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">Catatan</label>
                  <input
                    type="text"
                    v-model="item.notes"
                    placeholder="Catatan tambahan"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Item Button -->
        <div class="mt-3">
          <button
            type="button"
            @click="addItem"
            :disabled="!canAddItem"
            class="inline-flex items-center px-3 py-2 text-sm text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Plus :size="16" class="mr-1" />
            Tambah Item
          </button>
          <span v-if="!canAddItem" class="ml-2 text-xs text-orange-600">
            Maksimal 20 item telah tercapai
          </span>
        </div>
      </div>

      <div v-if="isEditMode" class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <div class="mb-3 flex items-center justify-between">
          <label class="text-sm font-medium text-gray-700">Lampiran Permintaan</label>
          <span class="text-xs text-gray-500">{{ existingAttachments.length }} file</span>
        </div>

        <div v-if="existingAttachments.length > 0" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-2">
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3"
          >
            <img
              v-if="attachment.file_type?.startsWith('image/')"
              :src="getAttachmentUrl(attachment)"
              :alt="attachment.file_name"
              class="h-12 w-12 rounded border border-gray-200 object-cover"
            />
            <div v-else class="flex h-12 w-12 items-center justify-center rounded border border-gray-200 bg-gray-50">
              <FileText :size="22" class="text-gray-500" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-gray-800">{{ attachment.file_name }}</p>
              <p class="text-xs text-gray-500">
                {{ attachment.file_type || "File" }}
                <span v-if="attachment.file_size"> · {{ formatFileSize(attachment.file_size) }}</span>
              </p>
            </div>
            <button
              type="button"
              @click="downloadAttachment(attachment)"
              class="p-2 text-gray-500 hover:text-blue-600"
              title="Download lampiran"
            >
              <Download :size="16" />
            </button>
            <button
              type="button"
              @click="handleDeleteAttachment(attachment.id)"
              :disabled="deletingAttachmentId === attachment.id"
              class="p-2 text-gray-500 hover:text-red-600 disabled:opacity-50"
              title="Hapus lampiran"
            >
              <Trash2 :size="16" />
            </button>
          </div>
        </div>

        <div class="rounded-lg border-2 border-dashed border-gray-300 bg-white p-3">
          <input
            id="edit-file-upload"
            type="file"
            multiple
            class="hidden"
            accept=".jpg,.jpeg,.png,.pdf,.docx"
            @change="handleFileSelect"
          />
          <label for="edit-file-upload" class="block text-center cursor-pointer">
            <Upload :size="28" class="mx-auto mb-2 text-gray-400" />
            <p class="text-sm text-gray-600">Tambah lampiran untuk permintaan ini</p>
            <p class="text-xs text-gray-500">JPG, PNG, PDF. Maksimal 10MB per file.</p>
          </label>
        </div>

        <div v-if="selectedFiles.length > 0" class="mt-3">
          <div class="mb-2 flex items-center justify-between">
            <p class="text-sm font-medium text-gray-700">File baru ({{ selectedFiles.length }})</p>
            <button
              type="button"
              @click="uploadEditAttachments"
              :disabled="uploadingAttachments"
              class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
            >
              <Upload :size="16" class="mr-1" />
              {{ uploadingAttachments ? "Mengupload..." : "Upload Lampiran" }}
            </button>
          </div>
          <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <div
              v-for="(attachment, fileIndex) in selectedFiles"
              :key="fileIndex"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-2"
            >
              <FileText :size="18" class="text-gray-500" />
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-gray-800">{{ attachment.file.name }}</p>
                <p class="text-xs text-gray-500">
                  {{ formatFileSize(attachment.file.size) }}
                  <span v-if="Number.isInteger(attachment.itemIndex)"> · Item {{ attachment.itemIndex + 1 }}</span>
                </p>
              </div>
              <button
                type="button"
                @click="removeFile(fileIndex)"
                class="p-1 text-gray-500 hover:text-red-600"
                title="Hapus dari pilihan"
              >
                <X :size="16" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <div class="mb-3 flex items-center justify-between">
          <label class="text-sm font-medium text-gray-700">Lampiran Permintaan</label>
          <span class="text-xs text-gray-500">{{ selectedFiles.length }}/{{ MAX_FILES }} file</span>
        </div>

        <p v-if="fileError" class="mb-3 flex items-center text-xs text-red-500">
          <AlertCircle :size="14" class="mr-1" />
          {{ fileError }}
        </p>

        <div class="rounded-lg border-2 border-dashed border-gray-300 bg-white p-3">
          <input
            id="new-file-upload"
            type="file"
            multiple
            class="hidden"
            accept=".jpg,.jpeg,.png,.pdf,.docx"
            @change="handleFileSelect"
          />
          <label for="new-file-upload" class="block text-center cursor-pointer">
            <Upload :size="28" class="mx-auto mb-2 text-gray-400" />
            <p class="text-sm text-gray-600">Pilih lampiran untuk permintaan ini</p>
            <p class="text-xs text-gray-500">JPG, PNG, PDF. Maksimal 10MB per file.</p>
          </label>
        </div>

        <div v-if="selectedFiles.length > 0" class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
          <div
            v-for="(attachment, fileIndex) in selectedFiles"
            :key="fileIndex"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-2"
          >
            <img
              v-if="attachment.file.type.startsWith('image/')"
              :src="getFilePreviewUrl(attachment.file)"
              :alt="attachment.file.name"
              class="h-12 w-12 rounded border border-gray-200 object-cover"
            />
            <div v-else class="flex h-12 w-12 items-center justify-center rounded border border-gray-200 bg-gray-50">
              <FileText :size="22" class="text-gray-500" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm text-gray-800">{{ attachment.file.name }}</p>
              <p class="text-xs text-gray-500">
                {{ formatFileSize(attachment.file.size) }}
                <span v-if="Number.isInteger(attachment.itemIndex)"> · Item {{ attachment.itemIndex + 1 }}</span>
              </p>
            </div>
            <button
              type="button"
              @click="removeFile(fileIndex)"
              class="p-1 text-gray-500 hover:text-red-600"
              title="Hapus dari pilihan"
            >
              <X :size="16" />
            </button>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100">
        <RouterLink
          :to="{ name: listRoute }"
          class="px-6 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50"
        >
          Batal
        </RouterLink>
        <button
          type="submit"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50"
          :disabled="loading || uploadingAttachments || !hasUserBranch"
        >
          <Send :size="16" class="inline-block mr-2" />
          {{ loading || uploadingAttachments ? "Menyimpan..." : (isEditMode ? "Simpan Perubahan" : "Simpan Permintaan") }}
        </button>
      </div>
    </form>
  </div>

  <!-- Step 2: Attachments -->
  <div v-if="currentStep === 2" class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-6 border-b border-gray-100">
      <h1 class="text-2xl font-bold text-gray-800">Lampiran</h1>
      <p class="text-sm text-gray-500 mt-1">
        Upload file pendukung untuk permintaan Anda (opsional)
      </p>
    </div>

    <div class="p-6 space-y-6">
      <p v-if="fileError" class="flex items-center text-xs text-red-500">
        <AlertCircle :size="14" class="mr-1" />
        {{ fileError }}
      </p>

      <div class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-gray-800">Lampiran Permintaan</h3>
          </div>
          <span class="text-xs text-gray-500">{{ selectedFiles.length }}/{{ MAX_FILES }} file</span>
        </div>

        <div class="rounded-lg border-2 border-dashed border-gray-300 p-4 text-center hover:border-gray-400">
          <input
            id="create-file-upload"
            type="file"
            multiple
            class="hidden"
            accept=".jpg,.jpeg,.png,.pdf,.docx"
            @change="handleFileSelect"
          />
          <label :for="`create-file-upload`" class="cursor-pointer">
            <Upload :size="36" class="mx-auto mb-3 text-gray-400" />
            <p class="text-sm text-gray-600">Pilih lampiran untuk permintaan ini</p>
            <p class="text-xs text-gray-500">JPG, PNG, PDF. Maksimal 10MB per file.</p>
          </label>
        </div>

        <div v-if="selectedFiles.length > 0" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
          <div
            v-for="(attachment, fileIndex) in selectedFiles"
            :key="fileIndex"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3"
          >
            <img
              v-if="attachment.file.type.startsWith('image/')"
              :src="getFilePreviewUrl(attachment.file)"
              :alt="attachment.file.name"
              class="h-12 w-12 rounded border border-gray-200 object-cover"
            />
            <div v-else class="flex h-12 w-12 items-center justify-center rounded border border-gray-200 bg-white">
              <FileText :size="22" class="text-gray-500" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-gray-800">{{ attachment.file.name }}</p>
              <p class="text-xs text-gray-500">
                {{ formatFileSize(attachment.file.size) }}
                <span v-if="Number.isInteger(attachment.itemIndex)"> · Item {{ attachment.itemIndex + 1 }}</span>
              </p>
            </div>
            <button
              type="button"
              @click="removeFile(fileIndex)"
              class="p-2 text-gray-500 hover:text-red-600"
              title="Hapus dari pilihan"
            >
              <X :size="16" />
            </button>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-between pt-4 border-t border-gray-100">
        <button
          @click="skipAttachments"
          class="px-6 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50"
        >
          Lewati Lampiran
        </button>
        <button
          @click="uploadAttachments"
          :disabled="uploadingAttachments || selectedFiles.length === 0"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50"
        >
          <Upload :size="16" class="inline-block mr-2" />
          {{ uploadingAttachments ? "Mengupload..." : "Upload & Selesai" }}
        </button>
      </div>
    </div>
  </div>
</template>
