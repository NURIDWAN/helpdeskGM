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
  <ul :class="cn('space-y-1', $props.class)">
    <slot />
  </ul>
</template>
