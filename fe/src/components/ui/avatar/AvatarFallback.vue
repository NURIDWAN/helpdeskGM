<script setup>
import { cn } from "@/lib/utils";

defineProps({
  class: {
    type: String,
    default: "",
  },
});
</script>

<template>
  <span
    :class="
      cn(
        'flex h-full w-full items-center justify-center rounded-full bg-slate-100 text-sm font-medium text-slate-700',
        $props.class
      )
    "
  >
    <slot />
  </span>
</template>
