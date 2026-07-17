<script setup>
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { useSidebarContext } from "./context";

const props = defineProps({
  as: {
    type: [String, Object],
    default: "button",
  },
  isActive: {
    type: Boolean,
    default: false,
  },
  class: {
    type: null,
    default: "",
  },
});

const sidebar = useSidebarContext();

const buttonClass = computed(() =>
  cn(
    "flex min-h-9 w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground/75 transition hover:bg-sidebar-accent hover:text-sidebar-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring",
    props.isActive ? "bg-sidebar-accent text-sidebar-primary shadow-sm" : "",
    sidebar?.collapsed?.value ? "lg:min-h-11 lg:justify-center lg:px-2" : "",
    props.class
  )
);
</script>

<template>
  <component :is="as" :class="buttonClass">
    <slot />
  </component>
</template>
