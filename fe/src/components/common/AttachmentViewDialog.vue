<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 overflow-y-auto"
    @click="closeDialog"
  >
    <div class="flex min-h-screen items-center justify-center p-4">
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
      ></div>

      <!-- Dialog -->
      <div
        class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div
          class="flex items-center justify-between p-6 border-b border-gray-200"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              {{ attachment?.file_type?.split("/")[1]?.toUpperCase() || "Foto" }}
            </h3>
            <p v-if="attachment?.created_at" class="text-sm text-gray-500 mt-1">
              {{ new Date(attachment.created_at).toLocaleDateString("id-ID") }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="closeDialog"
              class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
            >
              <X :size="20" />
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          <div v-if="attachment" class="flex justify-center">
            <!-- Image Preview -->
            <div
              v-if="attachment.file_type?.startsWith('image/') || (!attachment.file_type && attachment.file_path)"
              class="max-w-full"
            >
              <img
                :src="attachment.file_path"
                :alt="`Photo ${attachment.id || ''}`"
                class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-lg"
                @error="handleImageError"
              />
            </div>

            <!-- PDF Preview -->
            <div
              v-else-if="attachment.file_type === 'application/pdf'"
              class="w-full"
            >
              <iframe
                :src="attachment.file_path"
                class="w-full h-[60vh] border border-gray-200 rounded-lg"
                frameborder="0"
              ></iframe>
            </div>

            <!-- Other Files -->
            <div v-else class="text-center py-12">
              <div
                class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-4"
              >
                <FileText :size="48" class="text-gray-400" />
              </div>
              <h4 class="text-lg font-medium text-gray-900 mb-2">
                {{
                  attachment.file_type.split("/")[1]?.toUpperCase() || "FILE"
                }}
                File
              </h4>
              <p class="text-gray-500 mb-4">
                Preview tidak tersedia untuk file ini
              </p>
              <div class="flex gap-3 justify-center">
                <a
                  :href="attachment.file_path"
                  target="_blank"
                  class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <ExternalLink :size="16" />
                  Buka di Tab Baru
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { X, FileText, ExternalLink } from "lucide-vue-next";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  attachment: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close"]);

const closeDialog = () => {
  emit("close");
};

const handleImageError = (event) => {
  event.target.style.display = "none";
};
</script>
