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
  <section :class="cn('space-y-1 py-2', $props.class)">
    <slot />
  </section>
</template>
