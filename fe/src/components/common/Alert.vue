<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-2"
  >
    <div v-if="show" :class="['rounded-lg border p-4 shadow-sm', alertClasses]">
      <div class="flex items-start">
        <!-- Icon -->
        <div class="flex-shrink-0">
          <component :is="alertIcon" :size="20" :class="iconClasses" />
        </div>

        <!-- Content -->
        <div class="ml-3 flex-1">
          <div class="flex items-center justify-between">
            <div>
              <h3 v-if="title" :class="titleClasses">
                {{ title }}
              </h3>
              <p :class="messageClasses">
                {{ message }}
              </p>
            </div>

            <!-- Close Button -->
            <button
              v-if="dismissible"
              @click="handleClose"
              :class="closeButtonClasses"
            >
              <X :size="16" />
            </button>
          </div>

          <!-- Additional Content Slot -->
          <div v-if="$slots.default" class="mt-2">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from "vue";
import { CheckCircle, XCircle, AlertTriangle, Info, X } from "lucide-vue-next";

// Props
const props = defineProps({
  type: {
    type: String,
    default: "info",
    validator: (value) =>
      ["success", "error", "warning", "info"].includes(value),
  },
  title: {
    type: String,
    default: null,
  },
  message: {
    type: String,
    required: true,
  },
  dismissible: {
    type: Boolean,
    default: true,
  },
  autoClose: {
    type: Boolean,
    default: false,
  },
  duration: {
    type: Number,
    default: 5000,
  },
});

// Emits
const emit = defineEmits(["close"]);

// Reactive data
const show = ref(true);

// Computed
const alertIcon = computed(() => {
  const icons = {
    success: CheckCircle,
    error: XCircle,
    warning: AlertTriangle,
    info: Info,
  };
  return icons[props.type];
});

const alertClasses = computed(() => {
  const classes = {
    success: "bg-green-50 border-green-200",
    error: "bg-red-50 border-red-200",
    warning: "bg-yellow-50 border-yellow-200",
    info: "bg-blue-50 border-blue-200",
  };
  return classes[props.type];
});

const iconClasses = computed(() => {
  const classes = {
    success: "text-green-500",
    error: "text-red-500",
    warning: "text-yellow-500",
    info: "text-blue-500",
  };
  return classes[props.type];
});

const titleClasses = computed(() => {
  const classes = {
    success: "text-sm font-medium text-green-800",
    error: "text-sm font-medium text-red-800",
    warning: "text-sm font-medium text-yellow-800",
    info: "text-sm font-medium text-blue-800",
  };
  return classes[props.type];
});

const messageClasses = computed(() => {
  const classes = {
    success: "text-sm text-green-700",
    error: "text-sm text-red-700",
    warning: "text-sm text-yellow-700",
    info: "text-sm text-blue-700",
  };
  return classes[props.type];
});

const closeButtonClasses = computed(() => {
  const classes = {
    success: "text-green-400 hover:text-green-600",
    error: "text-red-400 hover:text-red-600",
    warning: "text-yellow-400 hover:text-yellow-600",
    info: "text-blue-400 hover:text-blue-600",
  };
  return classes[props.type];
});

// Methods
const handleClose = () => {
  show.value = false;
  emit("close");
};

// Auto close functionality
let autoCloseTimer = null;

const startAutoClose = () => {
  if (props.autoClose && props.duration > 0) {
    autoCloseTimer = setTimeout(() => {
      handleClose();
    }, props.duration);
  }
};

const clearAutoClose = () => {
  if (autoCloseTimer) {
    clearTimeout(autoCloseTimer);
    autoCloseTimer = null;
  }
};

// Watch for auto close changes
watch(
  () => props.autoClose,
  (newVal) => {
    if (newVal) {
      startAutoClose();
    } else {
      clearAutoClose();
    }
  }
);

// Lifecycle
watch(show, (newVal) => {
  if (newVal && props.autoClose) {
    startAutoClose();
  } else {
    clearAutoClose();
  }
});

// Start auto close on mount if enabled
if (props.autoClose) {
  startAutoClose();
}

// Cleanup on unmount
onUnmounted(() => {
  clearAutoClose();
});
</script>
