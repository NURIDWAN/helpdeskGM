<template>
  <div class="relative" ref="wrapper">
    <!-- Control -->
    <div
      class="w-full min-h-[42px] flex items-center gap-2 flex-wrap px-3 py-2 bg-white border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500"
      :class="{ 'opacity-60 pointer-events-none': disabled }"
      @click="toggleOpen"
    >
      <!-- Tags -->
      <template v-if="selectedItems.length">
        <span
          v-for="item in selectedItems"
          :key="item.value"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-sm bg-blue-100 text-blue-700"
        >
          {{ item.label }}
          <button
            type="button"
            class="hover:text-blue-900"
            @click.stop="remove(item.value)"
          >
            ×
          </button>
        </span>
      </template>
      <input
        ref="searchInput"
        v-model="search"
        :placeholder="selectedItems.length ? '' : placeholder"
        class="flex-1 min-w-[120px] outline-none text-sm text-gray-700"
        :disabled="disabled"
        @keydown.stop
      />
    </div>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
    >
      <div
        v-if="filteredOptions.length === 0"
        class="px-3 py-2 text-sm text-gray-500"
      >
        Tidak ada opsi
      </div>
      <ul class="py-1">
        <li
          v-for="opt in filteredOptions"
          :key="opt.value"
          class="px-3 py-2 hover:bg-gray-50 flex items-center gap-2 cursor-pointer"
          @click.stop="toggleValue(opt.value)"
        >
          <input
            type="checkbox"
            class="rounded"
            :checked="isSelected(opt.value)"
          />
          <span class="text-sm text-gray-800">{{ opt.label }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
  modelValue: { type: Array, default: () => [] }, // array of values
  options: { type: Array, default: () => [] }, // [{ value, label }]
  placeholder: { type: String, default: "Pilih..." },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const search = ref("");
const wrapper = ref(null);
const searchInput = ref(null);

const selectedItems = computed(() => {
  const values = new Set(props.modelValue);
  return props.options.filter((o) => values.has(o.value));
});

const filteredOptions = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return props.options;
  return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

const isSelected = (value) => props.modelValue.includes(value);

const toggleValue = (value) => {
  const next = isSelected(value)
    ? props.modelValue.filter((v) => v !== value)
    : [...props.modelValue, value];
  emit("update:modelValue", next);
};

const remove = (value) => {
  emit(
    "update:modelValue",
    props.modelValue.filter((v) => v !== value)
  );
};

const toggleOpen = () => {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) {
    requestAnimationFrame(() => searchInput.value?.focus());
  }
};

const onClickOutside = (e) => {
  if (!wrapper.value) return;
  if (!wrapper.value.contains(e.target)) {
    open.value = false;
  }
};

onMounted(() => {
  document.addEventListener("click", onClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", onClickOutside);
});

watch(
  () => props.modelValue,
  () => {
    // keep search small for better UX
    search.value = "";
  }
);
</script>
