<template>
  <button
    type="button"
    class="inline-flex items-center justify-center rounded-md p-1.5 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
    :class="cellClasses"
    :aria-label="`${roleName} - ${featureKey}: ${statusLabel}`"
    @click="$emit('click', { roleName, featureKey, permissions })"
  >
    <CheckCircle2 v-if="status === 'full'" :size="18" />
    <CircleDot v-else-if="status === 'partial'" :size="18" />
    <MinusCircle v-else :size="18" />
  </button>
</template>

<script setup>
import { computed } from "vue";
import { CheckCircle2, CircleDot, MinusCircle } from "lucide-vue-next";

const props = defineProps({
  status: {
    type: String,
    default: "empty",
    validator: (val) => ["full", "partial", "empty"].includes(val),
  },
  roleName: {
    type: String,
    required: true,
  },
  featureKey: {
    type: String,
    required: true,
  },
  permissions: {
    type: Array,
    default: () => [],
  },
});

defineEmits(["click"]);

const cellClasses = computed(() => {
  switch (props.status) {
    case "full":
      return "bg-emerald-100 text-emerald-600 hover:bg-emerald-200 cursor-pointer";
    case "partial":
      return "bg-amber-100 text-amber-600 hover:bg-amber-200 cursor-pointer";
    default:
      return "bg-slate-100 text-slate-400 hover:bg-slate-200 cursor-pointer";
  }
});

const statusLabel = computed(() => {
  switch (props.status) {
    case "full":
      return "Penuh";
    case "partial":
      return "Sebagian";
    default:
      return "Kosong";
  }
});
</script>
