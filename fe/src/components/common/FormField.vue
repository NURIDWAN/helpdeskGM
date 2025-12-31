<template>
  <div :class="containerClass">
    <label :for="id" class="block text-sm font-medium text-gray-700 mb-2">
      <component v-if="labelIcon" :is="labelIcon" :size="16" class="inline mr-2" />
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>

    <div class="relative">
      <!-- Select Dropdown -->
      <select
        v-if="type === 'select' || type === 'multiselect'"
        :id="id"
        :name="name"
        v-model="selectedValue"
        @blur="handleBlur"
        :required="required"
        :multiple="type === 'multiselect'"
        :class="inputClasses"
        v-bind="$attrs"
      >
        <option value="" disabled>{{ placeholder || "Pilih..." }}</option>
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>

      <!-- Regular Input/Textarea -->
      <component
        v-else
        :is="inputComponent"
        :id="id"
        :name="name"
        :value="modelValue"
        @input="handleInput"
        @blur="handleBlur"
        :required="required"
        :placeholder="placeholder"
        :rows="rows"
        :type="type"
        :class="inputClasses"
        v-bind="$attrs"
      />
    </div>

    <!-- Error Message -->
    <p v-if="hasError" class="mt-2 text-sm text-red-600">
      {{ error }}
    </p>

    <!-- Success Message -->
    <p v-else-if="isValid && modelValue" class="mt-2 text-sm text-green-600">
      {{ successMessage }}
    </p>

    <!-- Helper Text -->
    <p v-if="helperText && !hasError" class="mt-1 text-xs text-gray-500">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number, Array],
    default: "",
  },
  id: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  labelIcon: {
    type: [String, Object, Function],
    default: null,
  },
  placeholder: {
    type: String,
    default: "",
  },
  error: {
    type: String,
    default: "",
  },
  successMessage: {
    type: String,
    default: "",
  },
  helperText: {
    type: String,
    default: "",
  },
  required: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: "text",
  },
  rows: {
    type: Number,
    default: 3,
  },
  containerClass: {
    type: String,
    default: "",
  },
  options: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:modelValue", "blur"]);

const inputComponent = computed(() => {
  if (props.type === "textarea") return "textarea";
  if (props.type === "select" || props.type === "multiselect") return "select";
  return "input";
});

const hasError = computed(() => !!props.error);
const isValid = computed(() => !!props.modelValue && !hasError.value);

const inputClasses = computed(() => {
  const baseClasses =
    "w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200";

  if (hasError.value) {
    return `${baseClasses} border-red-500 focus:ring-red-500 focus:border-red-500`;
  } else if (isValid.value) {
    return `${baseClasses} border-green-500 focus:ring-green-500 focus:border-green-500`;
  }

  return baseClasses;
});

// v-model binding for select/multiselect
const selectedValue = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    // Ensure array for multiselect
    if (props.type === "multiselect" && !Array.isArray(value)) {
      emit("update:modelValue", Array.from(value));
    } else {
      emit("update:modelValue", value);
    }
  },
});

const handleInput = (event) => {
  if (props.type === "multiselect") {
    const selectedOptions = Array.from(event.target.selectedOptions).map(
      (option) => option.value
    );
    emit("update:modelValue", selectedOptions);
  } else {
    emit("update:modelValue", event.target.value);
  }
};

const handleBlur = (event) => {
  emit("blur", event);
};
</script>
