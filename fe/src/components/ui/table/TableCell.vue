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
  <td :class="cn('p-4 align-middle', $props.class)">
    <slot />
  </td>
</template>
