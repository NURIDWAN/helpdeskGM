<script setup>
import { reactive, onMounted, computed, ref, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useTicketStore } from "@/stores/ticket";
import { useTicketAttachmentStore } from "@/stores/ticketAttachment";
import { useTicketReplyStore } from "@/stores/ticketReply";
import FormCard from "@/components/common/FormCard.vue";
import AttachmentViewDialog from "@/components/common/AttachmentViewDialog.vue";
import {
  ArrowLeft,
  ChevronRight,
  FileText,
  MessageSquare,
  Building,
  User,
  Eye,
  Send,
  Calendar,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();

const ticketStore = useTicketStore();
const { error, loading } = storeToRefs(ticketStore);
const { fetchTicket, updateTicket } = ticketStore;

// Auth
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const isStaff = computed(() => (user.value?.roles || []).includes("staff"));

// Check if current user is assigned to this ticket
const isAssignedStaff = computed(() => {
  if (!ticket.value?.assigned_staff || !user.value) return false;
  return ticket.value.assigned_staff.some(
    (staff) => staff.id === user.value.id
  );
});

// Check if user is admin
const isAdmin = computed(() => (user.value?.roles || []).includes("admin"));

// Check if user can reply or update status
const canInteractWithTicket = computed(() => {
  return isAdmin.value || (isStaff.value && isAssignedStaff.value) || (user.value?.id === ticket.value?.user_id);
});

const ticketAttachmentStore = useTicketAttachmentStore();
const { attachments } = storeToRefs(ticketAttachmentStore);
const { getByTicketId } = ticketAttachmentStore;

const ticketReplyStore = useTicketReplyStore();
const {
  replies,
  loading: replyLoading,
  error: replyError,
} = storeToRefs(ticketReplyStore);
const { getByTicketId: getRepliesByTicketId, createReply } = ticketReplyStore;

const ticketId = computed(() => route.params.id);
const ticket = ref(null);

// Polling state
const pollingInterval = ref(null);
const isUserTyping = ref(false);
const typingTimeout = ref(null);
const lastPollingTime = ref(null);
const countdownTimer = ref(null);
const nextPollIn = ref(0);
const isPolling = ref(false);

// Dialog state
const showAttachmentDialog = ref(false);
const selectedAttachment = ref(null);

// Reply form state
const replyForm = reactive({
  content: "",
});

// Status update state (for staff)
const statusForm = reactive({
  status: "",
});

const initializeStatusForm = () => {
  if (ticket.value?.status) {
    statusForm.status = ticket.value.status;
  }
};

// Success dialog state
const showStatusDialog = ref(false);

const handleStatusUpdate = async () => {
  if (!ticketId.value || !statusForm.status) return;
  try {
    await updateTicket(ticketId.value, { status: statusForm.status });
    // reflect locally
    ticket.value.status = statusForm.status;
    showStatusDialog.value = true;
  } catch (e) {
    // no-op, store handles error
  }
};

const loadTicketData = async () => {
  if (ticketId.value) {
    try {
      ticket.value = await fetchTicket(ticketId.value);
      initializeStatusForm();

      // Start smart polling after ticket data is loaded
      startPolling();
    } catch (error) {
      console.error("Error loading ticket data:", error);
      router.push({ name: "admin.tickets" });
    }
  }
};

const loadAttachmentsData = async () => {
  if (ticketId.value) {
    try {
      await getByTicketId(ticketId.value);
    } catch (error) {
      console.error("Error loading attachments:", error);
    }
  }
};

const loadRepliesData = async () => {
  if (ticketId.value) {
    try {
      await getRepliesByTicketId(ticketId.value);
    } catch (error) {
      console.error("Error loading replies:", error);
    }
  }
};

// Smart polling functions
const startPolling = () => {
  if (pollingInterval.value) return; // Already polling

  // Start countdown timer
  startCountdown();

  pollingInterval.value = setInterval(async () => {
    // Skip polling if user is typing
    if (isUserTyping.value) {
      console.log("Skipping poll - user is typing");
      return;
    }

    // Skip if page is not visible
    if (document.hidden) {
      console.log("Skipping poll - page is hidden");
      return;
    }

    console.log("Polling for new replies...");
    await pollReplies();
  }, 10000); // Poll every 10 seconds
};

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
    pollingInterval.value = null;
  }

  // Stop countdown timer
  if (countdownTimer.value) {
    clearInterval(countdownTimer.value);
    countdownTimer.value = null;
  }
};

const startCountdown = () => {
  nextPollIn.value = 10; // Start with 10 seconds

  countdownTimer.value = setInterval(() => {
    if (nextPollIn.value > 0) {
      nextPollIn.value--;
    } else {
      nextPollIn.value = 10; // Reset to 10 seconds
    }
  }, 1000);
};

const pollReplies = async () => {
  if (isPolling.value) return; // Prevent concurrent polls

  isPolling.value = true;

  try {
    // Get current replies count for comparison
    const currentCount = replies.value.length;

    // Fetch new replies silently
    await getRepliesByTicketId(ticketId.value);

    // Check if new replies were added
    const newCount = replies.value.length;
    if (newCount > currentCount) {
      console.log(
        `New replies detected: ${newCount - currentCount} new replies`
      );
      // You could add a subtle notification here if needed
    }

    lastPollingTime.value = new Date();
  } catch (error) {
    console.error("Error polling replies:", error);
  } finally {
    isPolling.value = false;
  }
};

const handleTypingStart = () => {
  isUserTyping.value = true;

  // Clear existing timeout
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value);
  }

  // Set timeout to stop typing detection after 2 seconds of inactivity
  typingTimeout.value = setTimeout(() => {
    isUserTyping.value = false;
    console.log("User stopped typing");
  }, 2000);
};

const handleTypingStop = () => {
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value);
  }

  // Stop typing detection after 1 second
  setTimeout(() => {
    isUserTyping.value = false;
    console.log("User stopped typing");
  }, 1000);
};

const getPriorityColor = (priority) => {
  const colors = {
    low: "bg-green-100 text-green-800",
    medium: "bg-yellow-100 text-yellow-800",
    high: "bg-red-100 text-red-800",
  };
  return colors[priority] || "bg-gray-100 text-gray-800";
};

const getStatusColor = (status) => {
  const colors = {
    open: "bg-blue-100 text-blue-800",
    in_progress: "bg-yellow-100 text-yellow-800",
    resolved: "bg-green-100 text-green-800",
    closed: "bg-gray-100 text-gray-800",
  };
  return colors[status] || "bg-gray-100 text-gray-800";
};

const openAttachmentDialog = (attachment) => {
  selectedAttachment.value = attachment;
  showAttachmentDialog.value = true;
};

const closeAttachmentDialog = () => {
  showAttachmentDialog.value = false;
  selectedAttachment.value = null;
};

const handleReplySubmit = async () => {
  if (!replyForm.content.trim()) return;

  try {
    // Stop polling during submit
    stopPolling();

    await createReply(ticketId.value, {
      content: replyForm.content.trim(),
    });

    // Clear form and reload replies
    replyForm.content = "";
    await loadRepliesData();

    // Restart polling after submit
    startPolling();
  } catch (error) {
    console.error("Error creating reply:", error);

    // Restart polling even if there's an error
    startPolling();
  }
};

onMounted(() => {
  loadTicketData();
  loadAttachmentsData();
  loadRepliesData();
});

onUnmounted(() => {
  // Cleanup polling
  stopPolling();

  // Cleanup typing timeout
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value);
  }

  // Cleanup countdown timer
  if (countdownTimer.value) {
    clearInterval(countdownTimer.value);
  }
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
        <h1 class="text-3xl font-bold text-gray-900">Detail Ticket</h1>
        <p class="text-gray-600 mt-1">
          Informasi lengkap dan attachments ticket
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
      <span class="text-gray-900 font-medium">Detail</span>
    </nav>
  </div>

  <!-- Polling Status -->
  <div
    v-if="pollingInterval && !isUserTyping"
    class="mb-6 p-3 bg-blue-50 border border-blue-200 rounded-lg"
  >
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
        <span class="text-sm text-blue-800">
          Memperbarui balasan otomatis setiap 10 detik
        </span>
      </div>
      <div class="flex items-center">
        <span class="text-xs text-blue-600 mr-2"
          >Pembaruan berikutnya dalam:</span
        >
        <span
          class="text-sm font-medium text-blue-800 bg-blue-100 px-2 py-1 rounded"
        >
          {{ nextPollIn }}s
        </span>
      </div>
    </div>
  </div>

  <!-- Typing Status -->
  <div
    v-if="isUserTyping"
    class="mb-6 p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
  >
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
        <span class="text-sm text-yellow-800">
          Mengetik... pembaruan otomatis dijeda
        </span>
      </div>
      <div class="flex items-center">
        <span class="text-xs text-yellow-600 mr-2"
          >Pembaruan akan dilanjutkan dalam:</span
        >
        <span
          class="text-sm font-medium text-yellow-800 bg-yellow-100 px-2 py-1 rounded"
        >
          {{ nextPollIn }}s
        </span>
      </div>
    </div>
  </div>

  <div v-if="ticket" class="space-y-6">
    <!-- Ticket Information -->
    <FormCard
      title="Informasi Ticket"
      subtitle="Detail lengkap ticket"
      :icon="FileText"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Title -->
        <div class="lg:col-span-2">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">
                {{ ticket.title }}
              </h2>
              <p class="text-gray-600 leading-relaxed">
                {{ ticket.description }}
              </p>
            </div>
            <div class="flex gap-2">
              <span
                :class="getPriorityColor(ticket.priority)"
                class="px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ ticket.priority.toUpperCase() }}
              </span>
              <span
                :class="getStatusColor(ticket.status)"
                class="px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ ticket.status.replace("_", " ").toUpperCase() }}
              </span>
            </div>
          </div>
        </div>

        <!-- Branch -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Building :size="16" class="inline mr-2" />
            Cabang
          </label>
          <p class="text-gray-900">{{ ticket.branch?.name || "-" }}</p>
        </div>

        <!-- Assigned Staff -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            Staff Assigned
          </label>
          <div
            v-if="ticket.assigned_staff && ticket.assigned_staff.length > 0"
            class="flex flex-wrap gap-2"
          >
            <span
              v-for="staff in ticket.assigned_staff"
              :key="staff.id"
              class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800"
            >
              {{ staff.name }}
            </span>
          </div>
          <p v-else class="text-gray-500">Belum di-assign</p>
        </div>

        <!-- Created Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Tanggal Dibuat
          </label>
          <p class="text-gray-900">
            {{
              new Date(ticket.created_at).toLocaleDateString("id-ID", {
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
              new Date(ticket.updated_at).toLocaleDateString("id-ID", {
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
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500">
        <FileText :size="48" class="mx-auto mb-4 text-gray-300" />
        <p>Tidak ada attachment untuk ticket ini</p>
      </div>
    </FormCard>

    <!-- Replies Section + Staff Status Control -->
    <FormCard
      title="Balasan Ticket"
      subtitle="Diskusi dan komunikasi terkait ticket"
      :icon="MessageSquare"
    >
      <div
        v-if="showStatusDialog"
        class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 flex items-center justify-between"
      >
        <div class="text-sm text-green-800">
          Status tiket berhasil diperbarui menjadi
          <span class="font-semibold">{{
            statusForm.status.replace("_", " ").toUpperCase()
          }}</span>
        </div>
        <button
          class="text-sm text-green-700 hover:underline"
          @click="showStatusDialog = false"
        >
          Tutup
        </button>
      </div>
      <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4"
      >
        <div class="text-sm text-gray-600">
          Diskusi dan pembaruan status tiket.
        </div>
        <div
          v-if="canInteractWithTicket && ticket"
          class="flex items-center gap-3"
        >
          <label class="text-sm text-gray-700">Status:</label>
          <select
            v-model="statusForm.status"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
          <button
            @click="handleStatusUpdate"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Update
          </button>
        </div>
      </div>
      <!-- Replies List -->
      <div v-if="replies.length > 0" class="space-y-4 mb-6">
        <div
          v-for="reply in replies"
          :key="reply.id"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow duration-200"
        >
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <div
                class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center"
              >
                <User :size="20" class="text-blue-600" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="text-sm font-semibold text-gray-900">
                  {{ reply.user?.name || "Unknown User" }}
                </h4>
                <span class="text-xs text-gray-500">
                  {{
                    new Date(reply.created_at).toLocaleDateString("id-ID", {
                      year: "numeric",
                      month: "short",
                      day: "numeric",
                      hour: "2-digit",
                      minute: "2-digit",
                    })
                  }}
                </span>
              </div>
              <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                {{ reply.content }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-gray-500 mb-6">
        <MessageSquare :size="48" class="mx-auto mb-4 text-gray-300" />
        <p>Belum ada balasan untuk ticket ini</p>
      </div>

      <!-- Access Control Section -->
      <div
        v-if="!canInteractWithTicket && isStaff && !isAssignedStaff"
        class="mb-6"
      >
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-center gap-3">
            <div
              class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
            >
              <User :size="16" class="text-yellow-600" />
            </div>
            <div>
              <h4 class="text-sm font-medium text-yellow-800">
                Akses Terbatas
              </h4>
              <p class="text-sm text-yellow-700 mt-1">
                Hanya staff yang di-assign ke ticket ini yang dapat membalas
                atau mengupdate status.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Reply Form -->
      <div v-if="canInteractWithTicket" class="p-4 bg-gray-50 rounded-lg">
        <form @submit.prevent="handleReplySubmit">
          <div class="mb-4">
            <label
              for="reply-content"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              <MessageSquare :size="16" class="inline mr-2" />
              Balasan Baru
            </label>
            <textarea
              v-model="replyForm.content"
              @input="handleTypingStart"
              @blur="handleTypingStop"
              id="reply-content"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
              placeholder="Tulis balasan untuk ticket ini..."
              :disabled="replyLoading"
            ></textarea>
          </div>
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="replyLoading || !replyForm.content.trim()"
              class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md font-medium"
            >
              <div
                v-if="replyLoading"
                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
              ></div>
              <Send v-if="!replyLoading" :size="16" />
              {{ replyLoading ? "Mengirim..." : "Kirim Balasan" }}
            </button>
          </div>
        </form>
      </div>
    </FormCard>

    <!-- Action Buttons -->
    <div
      class="flex justify-between items-center pt-6 border-t border-gray-200"
    >
      <RouterLink
        :to="{ name: 'admin.tickets' }"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
      >
        Kembali ke List
      </RouterLink>
      <RouterLink
        v-if="can('ticket-edit')"
        :to="{ name: 'admin.ticket.edit', params: { id: ticketId } }"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md font-medium"
      >
        Edit Ticket
      </RouterLink>
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
