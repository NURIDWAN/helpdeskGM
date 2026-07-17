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
  <div :class="cn('flex-1 overflow-y-auto px-3 py-4', $props.class)">
    <slot />
  </div>
</template>
