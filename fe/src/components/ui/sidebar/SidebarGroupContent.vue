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
  <div :class="cn('space-y-1', $props.class)">
    <slot />
  </div>
</template>
