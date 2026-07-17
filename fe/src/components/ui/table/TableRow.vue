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
  <tr :class="cn('border-b border-slate-100 transition-colors hover:bg-slate-50', $props.class)">
    <slot />
  </tr>
</template>
