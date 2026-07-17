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
  <div
    :class="
      cn(
        'absolute right-0 z-50 mt-2 min-w-52 overflow-hidden rounded-md border border-slate-200 bg-white p-1 text-slate-950 shadow-md',
        $props.class
      )
    "
  >
    <slot />
  </div>
</template>
