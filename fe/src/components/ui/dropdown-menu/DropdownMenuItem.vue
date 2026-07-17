<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  as: {
    type: [String, Object],
    default: "button",
  },
  variant: {
    type: String,
    default: "default",
  },
  class: {
    type: String,
    default: "",
  },
});

const itemClass = computed(() =>
  cn(
    'relative flex w-full cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-slate-100 focus:bg-slate-100',
    props.variant === 'destructive' ? 'text-red-600 hover:bg-red-50' : 'text-slate-700',
    props.class
  )
);
</script>

<template>
  <component :is="as" :class="itemClass">
    <slot />
  </component>
</template>
