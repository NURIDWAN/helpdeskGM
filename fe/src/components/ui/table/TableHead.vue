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
  <th :class="cn('h-11 px-4 text-left align-middle text-xs font-semibold uppercase text-slate-500', $props.class)">
    <slot />
  </th>
</template>
