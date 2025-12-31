<script setup>
import { onMounted, ref, onUnmounted, nextTick } from "vue";
import { useTicketStore } from "@/stores/ticket";
import { useTicketReplyStore } from "@/stores/ticketReply";
import { useTicketAttachmentStore } from "@/stores/ticketAttachment";
import { storeToRefs } from "pinia";
import { DateTime } from "luxon";
import { useRoute } from "vue-router";
import {
  CheckCircle,
  AlertCircle,
  ArrowLeft,
  Download,
  MessageCircle,
  Send,
  FileText,
  Image,
  X,
} from "lucide-vue-next";

const ticketStore = useTicketStore();
const ticketReplyStore = useTicketReplyStore();
const ticketAttachmentStore = useTicketAttachmentStore();
const { success, error, loading } = storeToRefs(ticketStore);
const {
  replies,
  loading: replyLoading,
  error: replyError,
} = storeToRefs(ticketReplyStore);
const { attachments, loading: attachmentLoading } = storeToRefs(
  ticketAttachmentStore
);
const { fetchTicketByCode, createTicketReply } = ticketStore;
const { getByTicketId } = ticketReplyStore;
const { getByTicketId: getAttachmentsByTicketId } = ticketAttachmentStore;

const route = useRoute();

// TODO: Create refs for ticket and form
// Hint: You'll need ticket object and form with content field
const ticket = ref({});
const form = ref({
  content: "",
});

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

const fetchTicketDetail = async () => {
  try {
    const response = await fetchTicketByCode(route.params.code);
    ticket.value = response;

    // Fetch replies and attachments after getting ticket data
    await Promise.all([loadRepliesData(), loadAttachmentsData()]);

    // Start smart polling after data is loaded
    startPolling();
  } catch (error) {
    console.error("Error fetching ticket:", error);
  }
};

const loadRepliesData = async () => {
  try {
    await getByTicketId(ticket.value.id);
  } catch (error) {
    console.error("Error fetching replies:", error);
  }
};

const loadAttachmentsData = async () => {
  try {
    await getAttachmentsByTicketId(ticket.value.id);
  } catch (error) {
    console.error("Error fetching attachments:", error);
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
    await getByTicketId(ticket.value.id);

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

const openAttachmentDialog = (attachment) => {
  selectedAttachment.value = attachment;
  showAttachmentDialog.value = true;
};

const closeAttachmentDialog = () => {
  showAttachmentDialog.value = false;
  selectedAttachment.value = null;
};

const handleSubmit = async () => {
  try {
    // Stop polling during submit
    stopPolling();

    await createTicketReply(ticket.value.id, form.value);

    // Clear form and error
    form.value.content = "";
    error.value = null;

    // Refresh replies data
    await loadRepliesData();

    // Restart polling after submit
    startPolling();
  } catch (error) {
    console.error("Error creating reply:", error);

    // Restart polling even if there's an error
    startPolling();
  }
};

onMounted(async () => {
  await fetchTicketDetail();
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
  <!-- Success Message -->
  <div
    v-if="success"
    class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg"
  >
    <div class="flex items-center">
      <CheckCircle :size="20" class="text-green-600 mr-2" />
      <span class="text-sm text-green-800">{{ success }}</span>
    </div>
  </div>

  <!-- Error Message -->
  <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
    <div class="flex items-center">
      <AlertCircle :size="20" class="text-red-600 mr-2" />
      <span class="text-sm text-red-800">{{ error }}</span>
    </div>
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

  <!-- Back Button -->
  <div class="mb-6">
    <RouterLink
      :to="{ name: 'app.dashboard' }"
      class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800"
    >
      <ArrowLeft :size="16" class="mr-2" />
      Kembali ke Daftar Tiket
    </RouterLink>
  </div>

  <!-- Loading State -->
  <div
    v-if="loading"
    class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6"
  >
    <div class="p-6">
      <div class="animate-pulse">
        <div class="h-8 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div class="flex space-x-4">
          <div class="h-6 bg-gray-200 rounded w-20"></div>
          <div class="h-6 bg-gray-200 rounded w-20"></div>
          <div class="h-6 bg-gray-200 rounded w-32"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ticket Info -->
  <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-6">
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ ticket.title }}</h1>
          <!-- Description -->
          <p class="text-gray-600 text-sm mb-4 line-clamp-2">
            {{ ticket.description }}
          </p>

          <div class="mt-2 flex items-center space-x-4">
            <span
              class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full"
            >
              {{
                ticket?.status
                  ? ticket.status
                      .replace(/_/g, " ")
                      .replace(/\b\w/g, (l) => l.toUpperCase())
                  : "-"
              }}
            </span>
            <span
              class="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 rounded-full"
            >
              {{
                ticket?.priority
                  ? ticket.priority
                      .replace(/_/g, " ")
                      .replace(/\b\w/g, (l) => l.toUpperCase())
                  : "-"
              }}
            </span>
            <span class="text-sm text-gray-500">#{{ ticket.code }}</span>
            <span class="text-sm text-gray-500">
              Dibuat pada
              {{
                DateTime.fromISO(ticket.created_at).toFormat(
                  "dd MMMM yyyy, HH:mm"
                )
              }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Attachments Section -->
  <div
    v-if="!loading && attachments.length > 0"
    class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6"
  >
    <div class="p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Lampiran</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="attachment in attachments"
          :key="attachment.id"
          @click="openAttachmentDialog(attachment)"
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
        >
          <div class="flex-shrink-0 mr-3">
            <Image
              v-if="attachment.file_type?.startsWith('image/')"
              :size="24"
              class="text-blue-600"
            />
            <FileText v-else :size="24" class="text-gray-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate">
              {{ attachment.file_path?.split("/").pop() || "File" }}
            </p>
            <p class="text-xs text-gray-500">
              {{ attachment.file_type }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Replies Loading State -->
  <div
    v-if="!loading && replyLoading"
    class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6"
  >
    <div class="p-6">
      <div class="animate-pulse">
        <div class="flex items-start space-x-4">
          <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
          <div class="flex-1">
            <div class="h-4 bg-gray-200 rounded w-1/4 mb-2"></div>
            <div class="h-3 bg-gray-200 rounded w-1/3 mb-3"></div>
            <div class="h-4 bg-gray-200 rounded w-full"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Discussion Thread -->
  <div
    v-if="!loading && !replyLoading"
    class="bg-white rounded-xl shadow-sm border border-gray-100"
  >
    <!-- Empty State -->
    <div v-if="replies.length === 0" class="p-6 text-center">
      <div class="text-gray-500">
        <MessageCircle :size="48" class="mx-auto mb-4 text-gray-300" />
        <p class="text-sm">Belum ada balasan untuk tiket ini.</p>
      </div>
    </div>

    <!-- Replies List -->
    <div v-if="replies.length > 0" class="divide-y divide-gray-100">
      <div class="p-6" v-for="reply in replies" :key="reply.id">
        <div class="flex items-start space-x-4">
          <img
            :src="`https://ui-avatars.com/api/?name=${reply.user.name}&background=0D8ABC&color=fff`"
            alt="User"
            class="w-10 h-10 rounded-full"
          />
          <div class="flex-1">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-800">
                  {{ reply.user.name }}
                </h4>
                <p class="text-xs text-gray-500">
                  {{
                    DateTime.fromISO(reply.created_at).toFormat(
                      "dd MMMM yyyy, HH:mm"
                    )
                  }}
                </p>
              </div>
            </div>
            <div class="mt-3 text-sm text-gray-800">
              <p>{{ reply.content }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reply Form -->
    <div class="p-6 border-t border-gray-100">
      <h4 class="text-sm font-medium text-gray-800 mb-4">Tambah Balasan</h4>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="group">
          <textarea
            v-model="form.content"
            @input="handleTypingStart"
            @blur="handleTypingStop"
            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            :class="{ 'border-red-500 ring-red-500': error?.content }"
            rows="4"
            placeholder="Tulis balasan Anda di sini..."
            minlength="10"
          ></textarea>
          <p class="mt-1 text-xs text-red-500" v-if="error?.content">
            {{ error?.content?.join(", ") }}
          </p>
        </div>
        <div class="flex items-center justify-between">
          <div></div>
          <button
            class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
          >
            <Send :size="16" class="inline-block mr-2" />
            <span v-if="!loading"> Kirim Balasan </span>
            <span v-else> Loading... </span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Attachment Dialog -->
  <div
    v-if="showAttachmentDialog"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click="closeAttachmentDialog"
  >
    <div
      class="bg-white rounded-lg max-w-4xl max-h-[90vh] overflow-hidden"
      @click.stop
    >
      <!-- Dialog Header -->
      <div
        class="flex items-center justify-between p-4 border-b border-gray-200"
      >
        <h3 class="text-lg font-semibold text-gray-800">
          {{ selectedAttachment?.file_path?.split("/").pop() || "File" }}
        </h3>
        <button
          @click="closeAttachmentDialog"
          class="text-gray-400 hover:text-gray-600"
        >
          <X :size="24" />
        </button>
      </div>

      <!-- Dialog Content -->
      <div class="p-4">
        <div
          v-if="selectedAttachment?.file_type?.startsWith('image/')"
          class="text-center"
        >
          <img
            :src="selectedAttachment.file_path"
            :alt="selectedAttachment.file_path?.split('/').pop()"
            class="max-w-full max-h-[70vh] object-contain mx-auto rounded-lg"
          />
        </div>
        <div v-else class="text-center py-8">
          <FileText :size="64" class="text-gray-400 mx-auto mb-4" />
          <p class="text-gray-600 mb-4">
            {{ selectedAttachment?.file_type }}
          </p>
          <a
            :href="selectedAttachment?.file_path"
            target="_blank"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            <Download :size="16" class="mr-2" />
            Download File
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
