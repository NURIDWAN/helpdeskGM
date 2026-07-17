<template>
  <Transition
    enter-active-class="transition ease-out duration-200"
    enter-from-class="translate-y-1 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-1 opacity-0"
  >
    <div
      v-if="show"
      :class="[
        'rounded-lg border p-4 shadow-sm',
        alertClasses,
      ]"
    >
      <div class="flex gap-3">
        <component :is="alertIcon" :size="20" :class="iconClasses" />

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 v-if="title" :class="titleClasses">{{ title }}</h3>
              <p :class="messageClasses">{{ message }}</p>
            </div>

            <Button
              v-if="dismissible"
              type="button"
              variant="ghost"
              size="icon"
              class="-mr-2 -mt-2 size-8 shrink-0"
              :class="closeButtonClasses"
              aria-label="Tutup notifikasi"
              @click="handleClose"
            >
              <X :size="16" />
            </Button>
          </div>

          <div v-if="$slots.default" class="mt-3">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from "vue";
import { AlertTriangle, CheckCircle, Info, X, XCircle } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const props = defineProps({
  type: {
    type: String,
    default: "info",
    validator: (value) =>
      ["success", "error", "danger", "warning", "info"].includes(value),
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

const emit = defineEmits(["close"]);
const show = ref(true);

const normalizedType = computed(() =>
  props.type === "danger" ? "error" : props.type
);

const alertIcon = computed(() => {
  const icons = {
    success: CheckCircle,
    error: XCircle,
    warning: AlertTriangle,
    info: Info,
  };
  return icons[normalizedType.value];
});

const alertClasses = computed(() => {
  const classes = {
    success: "border-green-200 bg-green-50",
    error: "border-red-200 bg-red-50",
    warning: "border-amber-200 bg-amber-50",
    info: "border-blue-200 bg-blue-50",
  };
  return classes[normalizedType.value];
});

const iconClasses = computed(() => {
  const classes = {
    success: "mt-0.5 shrink-0 text-green-600",
    error: "mt-0.5 shrink-0 text-red-600",
    warning: "mt-0.5 shrink-0 text-amber-600",
    info: "mt-0.5 shrink-0 text-blue-600",
  };
  return classes[normalizedType.value];
});

const titleClasses = computed(() => {
  const classes = {
    success: "text-sm font-semibold text-green-900",
    error: "text-sm font-semibold text-red-900",
    warning: "text-sm font-semibold text-amber-900",
    info: "text-sm font-semibold text-blue-900",
  };
  return classes[normalizedType.value];
});

const messageClasses = computed(() => {
  const classes = {
    success: "text-sm leading-6 text-green-800",
    error: "text-sm leading-6 text-red-800",
    warning: "text-sm leading-6 text-amber-800",
    info: "text-sm leading-6 text-blue-800",
  };
  return classes[normalizedType.value];
});

const closeButtonClasses = computed(() => {
  const classes = {
    success: "text-green-700 hover:bg-green-100 hover:text-green-900",
    error: "text-red-700 hover:bg-red-100 hover:text-red-900",
    warning: "text-amber-700 hover:bg-amber-100 hover:text-amber-900",
    info: "text-blue-700 hover:bg-blue-100 hover:text-blue-900",
  };
  return classes[normalizedType.value];
});

const handleClose = () => {
  show.value = false;
  emit("close");
};

let autoCloseTimer = null;

const startAutoClose = () => {
  if (props.autoClose && props.duration > 0) {
    autoCloseTimer = setTimeout(handleClose, props.duration);
  }
};

const clearAutoClose = () => {
  if (autoCloseTimer) {
    clearTimeout(autoCloseTimer);
    autoCloseTimer = null;
  }
};

watch(
  () => props.autoClose,
  (newVal) => {
    if (newVal) startAutoClose();
    else clearAutoClose();
  }
);

watch(show, (newVal) => {
  if (newVal && props.autoClose) startAutoClose();
  else clearAutoClose();
});

if (props.autoClose) {
  startAutoClose();
}

onUnmounted(clearAutoClose);
</script>
