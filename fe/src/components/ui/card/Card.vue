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
        'rounded-lg border border-slate-200 bg-white text-slate-950 shadow-sm',
        $props.class
      )
    "
  >
    <slot />
  </div>
</template>
