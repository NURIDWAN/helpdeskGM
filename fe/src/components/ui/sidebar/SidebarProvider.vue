<script setup>
import { computed } from "vue";
import { provideSidebarContext } from "./context";

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:open", "update:collapsed"]);

const open = computed(() => props.open);
const collapsed = computed(() => props.collapsed);

provideSidebarContext({
  open,
  collapsed,
  setOpen: (value) => emit("update:open", value),
  toggleOpen: () => emit("update:open", !props.open),
  setCollapsed: (value) => emit("update:collapsed", value),
  toggleCollapsed: () => emit("update:collapsed", !props.collapsed),
});
</script>

<template>
  <div
    class="min-h-screen"
    :data-sidebar-collapsed="collapsed ? 'true' : 'false'"
  >
    <slot />
  </div>
</template>
