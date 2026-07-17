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
  <li :class="cn('list-none', $props.class)">
    <slot />
  </li>
</template>
