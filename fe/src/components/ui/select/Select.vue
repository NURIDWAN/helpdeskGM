<script setup>
import { cn } from "@/lib/utils";

defineProps({
  modelValue: {
    type: [String, Number],
    default: "",
  },
  class: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:modelValue", "change"]);
</script>

<template>
  <select
    :value="modelValue"
    :class="
      cn(
        'flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 ring-offset-white transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        $props.class
      )
    "
    @change="
      emit('update:modelValue', $event.target.value);
      emit('change', $event);
    "
  >
    <slot />
  </select>
</template>
