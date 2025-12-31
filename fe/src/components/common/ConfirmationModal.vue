<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="$emit('close')"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div
              v-if="show"
              class="relative w-full max-w-md bg-white rounded-xl shadow-xl"
              @click.stop
            >
              <!-- Header -->
              <div
                class="flex items-center justify-between p-6 border-b border-gray-200"
              >
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ title }}
                </h3>
                <button
                  @click="$emit('close')"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <X :size="20" />
                </button>
              </div>

              <!-- Content -->
              <div class="p-6">
                <div class="flex items-start space-x-4">
                  <!-- Icon -->
                  <div
                    :class="[
                      'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                      iconBgClass,
                    ]"
                  >
                    <component :is="icon" :size="20" :class="iconClass" />
                  </div>

                  <!-- Message -->
                  <div class="flex-1">
                    <p class="text-sm text-gray-600 leading-relaxed">
                      {{ message }}
                    </p>
                    <p v-if="subtitle" class="text-xs text-gray-500 mt-1">
                      {{ subtitle }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div
                class="flex justify-end space-x-3 p-6 bg-gray-50 rounded-b-xl"
              >
                <button
                  @click="$emit('close')"
                  :disabled="loading"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  {{ cancelText }}
                </button>
                <button
                  @click="$emit('confirm')"
                  :disabled="loading"
                  :class="[
                    'px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors',
                    confirmButtonClass,
                  ]"
                >
                  <div v-if="loading" class="flex items-center space-x-2">
                    <div
                      class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
                    ></div>
                    <span>{{ loadingText }}</span>
                  </div>
                  <span v-else>{{ confirmText }}</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from "vue";
import { X, AlertTriangle, Info, CheckCircle } from "lucide-vue-next";

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "Konfirmasi",
  },
  message: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: "",
  },
  confirmText: {
    type: String,
    default: "Konfirmasi",
  },
  cancelText: {
    type: String,
    default: "Batal",
  },
  loadingText: {
    type: String,
    default: "Memproses...",
  },
  loading: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: "danger", // danger, warning, info, success
    validator: (value) =>
      ["danger", "warning", "info", "success"].includes(value),
  },
});

// Emits
defineEmits(["close", "confirm"]);

// Computed
const icon = computed(() => {
  const icons = {
    danger: AlertTriangle,
    warning: AlertTriangle,
    info: Info,
    success: CheckCircle,
  };
  return icons[props.type];
});

const iconBgClass = computed(() => {
  const classes = {
    danger: "bg-red-100",
    warning: "bg-yellow-100",
    info: "bg-blue-100",
    success: "bg-green-100",
  };
  return classes[props.type];
});

const iconClass = computed(() => {
  const classes = {
    danger: "text-red-600",
    warning: "text-yellow-600",
    info: "text-blue-600",
    success: "text-green-600",
  };
  return classes[props.type];
});

const confirmButtonClass = computed(() => {
  const classes = {
    danger: "bg-red-600 hover:bg-red-700 focus:ring-red-500",
    warning: "bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500",
    info: "bg-blue-600 hover:bg-blue-700 focus:ring-blue-500",
    success: "bg-green-600 hover:bg-green-700 focus:ring-green-500",
  };
  return classes[props.type];
});
</script>
