<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { useSidebarContext } from "./context";

const props = defineProps({
  class: {
    type: null,
    default: "",
  },
});

const sidebar = useSidebarContext();

const insetClass = computed(() =>
  cn(
    "min-h-screen transition-all duration-200",
    sidebar?.collapsed?.value ? "lg:pl-16" : "lg:pl-60",
    props.class
  )
);
</script>

<template>
  <div :class="insetClass">
    <slot />
  </div>
</template>
