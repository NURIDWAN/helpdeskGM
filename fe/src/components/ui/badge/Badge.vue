<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  variant: {
    type: String,
    default: "default",
  },
  class: {
    type: String,
    default: "",
  },
});

const variants = {
  default: "border-transparent bg-blue-600 text-white",
  secondary: "border-transparent bg-slate-100 text-slate-900",
  destructive: "border-transparent bg-red-600 text-white",
  outline: "text-slate-950",
  success: "border-transparent bg-green-50 text-green-700",
  warning: "border-transparent bg-amber-50 text-amber-700",
  info: "border-transparent bg-blue-50 text-blue-700",
  muted: "border-transparent bg-slate-100 text-slate-700",
};

const badgeClass = computed(() =>
  cn(
    "inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors",
    variants[props.variant] || variants.default,
    props.class
  )
);
</script>

<template>
  <span :class="badgeClass">
    <slot />
  </span>
</template>
