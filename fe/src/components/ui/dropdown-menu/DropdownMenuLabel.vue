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
  <div :class="cn('px-2 py-1.5 text-sm font-semibold', $props.class)">
    <slot />
  </div>
</template>
