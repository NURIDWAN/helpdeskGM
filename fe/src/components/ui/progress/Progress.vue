<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0,
  },
  class: {
    type: String,
    default: "",
  },
});

const width = computed(() => `${Math.max(0, Math.min(100, props.modelValue))}%`);
</script>

<template>
  <div :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-slate-100', $props.class)">
    <div class="h-full w-full flex-1 rounded-full bg-blue-600 transition-all" :style="{ transform: `translateX(-${100 - parseFloat(width)}%)` }" />
  </div>
</template>
