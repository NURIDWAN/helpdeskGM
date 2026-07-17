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
  <ul :class="cn('mt-1 space-y-1', $props.class)">
    <slot />
  </ul>
</template>
