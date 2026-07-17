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

const sidebarClass = computed(() =>
  cn(
    "fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl transition-all duration-200 ease-in-out lg:z-30 lg:shadow-none",
    sidebar?.open?.value ? "translate-x-0" : "-translate-x-full",
    "lg:translate-x-0",
    sidebar?.collapsed?.value ? "lg:w-16" : "lg:w-60",
    props.class
  )
);
</script>

<template>
  <aside :class="sidebarClass">
    <slot />
  </aside>
</template>
