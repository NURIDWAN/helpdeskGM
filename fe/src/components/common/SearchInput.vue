<template>
  <div class="relative">
    <Search
      :size="20"
      class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
    />
    <input
      :value="modelValue"
      @input="handleInput"
      type="text"
      :placeholder="placeholder"
      class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
      :class="{ 'border-red-500': error }"
    />
  </div>
  <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
</template>

<script setup>
import { ref, watch } from "vue";
import { Search } from "lucide-vue-next";

// Props
const props = defineProps({
  modelValue: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "Cari...",
  },
  error: {
    type: String,
    default: "",
  },
  debounce: {
    type: Number,
    default: 0, // 0 means no debounce
  },
});

// Emits
const emit = defineEmits(["update:modelValue"]);

// Reactive data
const internalValue = ref(props.modelValue);
let debounceTimer = null;

// Methods
const handleInput = (event) => {
  const value = event.target.value;
  internalValue.value = value;

  if (props.debounce > 0) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      emit("update:modelValue", value);
    }, props.debounce);
  } else {
    emit("update:modelValue", value);
  }
};

// Watch for external changes
watch(
  () => props.modelValue,
  (newValue) => {
    internalValue.value = newValue;
  }
);
</script>
