<script setup>
import { PanelLeftClose, PanelLeftOpen } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { useSidebarContext } from "./context";

defineProps({
  class: {
    type: null,
    default: "",
  },
});

const sidebar = useSidebarContext();
</script>

<template>
  <Button
    type="button"
    variant="ghost"
    size="icon"
    :class="[
      'hidden size-8 shrink-0 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:inline-flex',
      $props.class,
    ]"
    :title="sidebar?.collapsed?.value ? 'Perluas sidebar' : 'Minimize sidebar'"
    :aria-label="sidebar?.collapsed?.value ? 'Perluas sidebar' : 'Minimize sidebar'"
    @click="sidebar?.toggleCollapsed()"
  >
    <PanelLeftOpen v-if="sidebar?.collapsed?.value" :size="18" />
    <PanelLeftClose v-else :size="18" />
  </Button>
</template>
