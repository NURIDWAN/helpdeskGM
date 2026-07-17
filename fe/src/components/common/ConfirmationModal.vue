<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm"
        @click.self="$emit('close')"
      >
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="translate-y-2 scale-95 opacity-0"
            enter-to-class="translate-y-0 scale-100 opacity-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="translate-y-0 scale-100 opacity-100"
            leave-to-class="translate-y-2 scale-95 opacity-0"
          >
            <div
              v-if="show"
              class="w-full max-w-md overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl"
              @click.stop
            >
              <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-950">{{ title }}</h3>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  class="size-8 text-slate-500 hover:text-slate-900"
                  aria-label="Tutup dialog"
                  @click="$emit('close')"
                >
                  <X :size="18" />
                </Button>
              </div>

              <div class="px-5 py-5">
                <div class="flex gap-4">
                  <div
                    :class="[
                      'flex size-10 shrink-0 items-center justify-center rounded-full',
                      iconBgClass,
                    ]"
                  >
                    <component :is="icon" :size="20" :class="iconClass" />
                  </div>

                  <div class="min-w-0 flex-1">
                    <p class="text-sm leading-6 text-slate-700">{{ message }}</p>
                    <p v-if="subtitle" class="mt-1 text-xs leading-5 text-slate-500">
                      {{ subtitle }}
                    </p>
                  </div>
                </div>
                <slot name="body" />
              </div>

              <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                <Button
                  type="button"
                  variant="outline"
                  :disabled="loading"
                  @click="$emit('close')"
                >
                  {{ cancelText }}
                </Button>
                <Button
                  type="button"
                  :variant="type === 'danger' ? 'destructive' : 'default'"
                  :disabled="loading || disabled"
                  @click="$emit('confirm')"
                >
                  <span v-if="loading" class="flex items-center gap-2">
                    <span class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                    {{ loadingText }}
                  </span>
                  <span v-else>{{ confirmText }}</span>
                </Button>
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
import { AlertTriangle, CheckCircle, Info, X } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

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
    default: "danger",
    validator: (value) =>
      ["danger", "warning", "info", "success"].includes(value),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["close", "confirm"]);

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
    danger: "bg-red-50",
    warning: "bg-amber-50",
    info: "bg-blue-50",
    success: "bg-green-50",
  };
  return classes[props.type];
});

const iconClass = computed(() => {
  const classes = {
    danger: "text-red-600",
    warning: "text-amber-600",
    info: "text-blue-600",
    success: "text-green-600",
  };
  return classes[props.type];
});
</script>
