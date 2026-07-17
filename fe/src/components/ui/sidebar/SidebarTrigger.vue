<script setup>
import { PanelLeft } from "lucide-vue-next";
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
    variant="outline"
    size="icon"
    :class="$props.class"
    aria-label="Buka sidebar"
    @click="sidebar?.toggleOpen()"
  >
    <PanelLeft :size="22" class="pointer-events-none" />
  </Button>
</template>
