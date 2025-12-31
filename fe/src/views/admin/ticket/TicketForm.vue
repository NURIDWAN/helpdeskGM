<script setup>
import { reactive, onMounted, computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useTicketStore } from "@/stores/ticket";
import { useBranchStore } from "@/stores/branch";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import MultiSelect from "@/components/common/MultiSelect.vue";
import {
  ArrowLeft,
  Save,
  ChevronRight,
  FileText,
  MessageSquare,
  Building,
  AlertTriangle,
  CheckCircle2,
  User,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { useUserStore } from "@/stores/user";
import { useTicketAttachmentStore } from "@/stores/ticketAttachment";
import AttachmentViewDialog from "@/components/common/AttachmentViewDialog.vue";

const route = useRoute();
const router = useRouter();

const ticketStore = useTicketStore();
const { error, loading } = storeToRefs(ticketStore);
const { createTicket, updateTicket, fetchTicket } = ticketStore;

const branchStore = useBranchStore();
const { branches } = storeToRefs(branchStore);
const { fetchBranches } = branchStore;

const userStore = useUserStore();
const { users } = storeToRefs(userStore);
const { fetchUsers } = userStore;

const ticketAttachmentStore = useTicketAttachmentStore();
const { attachments } = storeToRefs(ticketAttachmentStore);
const { getByTicketId } = ticketAttachmentStore;

const isEdit = computed(() => route.name === "admin.ticket.edit");
const ticketId = computed(() => route.params.id);

// Dialog state
const showAttachmentDialog = ref(false);
const selectedAttachment = ref(null);

const form = reactive({
  title: "",
  description: "",
  branch_id: "",
  priority: "",
  status: "",
  assigned_staff: [],
});

const priorities = [
  { value: "low", label: "Low" },
  { value: "medium", label: "Medium" },
  { value: "high", label: "High" },
];

const statuses = [
  { value: "open", label: "Open" },
  { value: "in_progress", label: "In Progress" },
  { value: "resolved", label: "Resolved" },
  { value: "closed", label: "Closed" },
];

const handleSubmit = async () => {
  try {
    const payload = {
      title: form.title.trim(),
      description: form.description.trim(),
      branch_id: form.branch_id,
      priority: form.priority,
      assigned_staff: form.assigned_staff,
    };

    if (isEdit.value) {
      payload.status = form.status;
      await updateTicket(ticketId.value, payload);
    } else {
      await createTicket(payload);
    }

    if (!error.value) {
      router.push({ name: "admin.tickets" });
    }
  } catch (error) {
    console.error("Error submitting form:", error);
  }
};

const loadTicketData = async () => {
  if (isEdit.value && ticketId.value) {
    try {
      const ticket = await fetchTicket(ticketId.value);

      console.log(ticket);

      if (ticket) {
        form.title = ticket.title;
        form.description = ticket.description;
        form.branch_id = ticket.branch?.id;
        form.priority = ticket.priority;
        form.status = ticket.status;
        form.assigned_staff = ticket.assigned_staff?.map((staff) => staff.id);
      }
    } catch (error) {
      console.error("Error loading ticket data:", error);
      router.push({ name: "admin.tickets" });
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

const loadUsersData = async () => {
  try {
    await fetchUsers({
      roles: ["staff"],
    });
  } catch (error) {
    console.error("Error loading users:", error);
  }
};

const loadAttachmentsData = async () => {
  if (isEdit.value && ticketId.value) {
    try {
      await getByTicketId(ticketId.value);
    } catch (error) {
      console.error("Error loading attachments:", error);
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
  loadBranchesData();
  loadUsersData();
  loadTicketData();
  loadAttachmentsData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.tickets' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEdit ? "Edit Ticket" : "Tambah Ticket Baru" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi ticket yang sudah ada"
              : "Tambahkan ticket baru ke dalam sistem"
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
      <RouterLink :to="{ name: 'admin.tickets' }" class="hover:text-gray-700">
        Data Ticket
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Form Card -->
  <FormCard
    title="Informasi Ticket"
    subtitle="Lengkapi data ticket dengan benar"
    :icon="FileText"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Title -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.title"
            id="title"
            name="title"
            label="Judul"
            :label-icon="FileText"
            placeholder="Masukkan judul ticket"
            :error="error?.title?.join(', ')"
            :required="true"
          />
        </div>

        <!-- Description -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.description"
            id="description"
            name="description"
            label="Deskripsi"
            :label-icon="MessageSquare"
            placeholder="Jelaskan masalah atau kebutuhan"
            :error="error?.description?.join(', ')"
            :required="true"
            type="textarea"
            :rows="4"
          />
        </div>

        <!-- Branch -->
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
              branches.map((b) => ({ value: String(b.id), label: b.name }))
            "
          />
        </div>

        <!-- Priority -->
        <div>
          <FormField
            v-model="form.priority"
            id="priority"
            name="priority"
            label="Prioritas"
            :label-icon="AlertTriangle"
            :error="error?.priority?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih prioritas"
            :options="priorities"
          />
        </div>

        <!-- Status (edit only) -->
        <div v-if="isEdit">
          <FormField
            v-model="form.status"
            id="status"
            name="status"
            label="Status"
            :label-icon="CheckCircle2"
            :error="error?.status?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih status"
            :options="statuses"
          />
        </div>

        <!-- Assign Staff -->
        <div>
          <label
            for="assigned_staff"
            class="block text-sm font-medium text-gray-700 mb-2"
          >
            <User :size="16" class="inline mr-2" />
            Assign ke Staff
          </label>
          <MultiSelect
            v-model="form.assigned_staff"
            :options="users.map((u) => ({ value: u.id, label: u.name }))"
            placeholder="Pilih staff (bisa lebih dari satu)"
          />
          <p v-if="error?.assigned_staff" class="mt-2 text-sm text-red-600">
            {{ error?.assigned_staff?.join(", ") }}
          </p>
        </div>
      </div>

      <!-- Attachments Section (Edit only) -->
      <div
        v-if="isEdit && attachments.length > 0"
        class="mt-8 pt-6 border-t border-gray-200"
      >
        <h3
          class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
        >
          <FileText :size="20" />
          Attachments
        </h3>
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
                    attachment.file_type.split("/")[1]?.toUpperCase() || "FILE"
                  }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  {{
                    new Date(attachment.created_at).toLocaleDateString("id-ID")
                  }}
                </p>
                <button
                  @click="openAttachmentDialog(attachment)"
                  class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 mt-2"
                  type="button"
                >
                  <FileText :size="12" />
                  View File
                </button>
              </div>
            </div>
          </div>
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
            :to="{ name: 'admin.tickets' }"
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
              loading
                ? "Menyimpan..."
                : isEdit
                ? "Update Ticket"
                : "Simpan Ticket"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>

  <!-- Attachment View Dialog -->
  <AttachmentViewDialog
    :show="showAttachmentDialog"
    :attachment="selectedAttachment"
    @close="closeAttachmentDialog"
  />
</template>
