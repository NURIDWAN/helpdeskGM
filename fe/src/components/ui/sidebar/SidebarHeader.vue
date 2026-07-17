<script setup>
import { cn } from "@/lib/utils";

defineProps({
  class: {
    type: null,
    default: "",
  },
});
</script>

<template>
  <div :class="cn('border-b border-sidebar-border p-3', $props.class)">
    <slot />
  </div>
</template>
