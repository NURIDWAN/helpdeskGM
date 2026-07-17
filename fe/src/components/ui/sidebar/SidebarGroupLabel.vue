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

const labelClass = computed(() =>
  cn(
    "px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/60",
    sidebar?.collapsed?.value ? "lg:hidden" : "",
    props.class
  )
);
</script>

<template>
  <div :class="labelClass">
    <slot />
  </div>
</template>
