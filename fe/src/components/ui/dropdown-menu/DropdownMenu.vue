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
  <div :class="cn('relative', $props.class)">
    <slot />
  </div>
</template>
