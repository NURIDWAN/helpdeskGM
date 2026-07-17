<template>
  <div :class="containerClass">
    <label :for="id" class="mb-2 block text-sm font-medium text-slate-700">
      <component v-if="labelIcon" :is="labelIcon" :size="16" class="inline mr-2" />
      {{ label }}
      <span v-if="required" class="ml-1 text-red-500">*</span>
    </label>

    <div class="relative">
      <!-- Searchable Select Dropdown -->
      <SearchableSelect
        v-if="type === 'select'"
        :model-value="modelValue"
        @update:model-value="handleSelectChange"
        :options="options"
        :placeholder="placeholder || 'Pilih...'"
        :disabled="$attrs.disabled"
        :error="hasError"
        :clearable="clearable"
      />

      <!-- Native Multi-Select (fallback) -->
      <select
        v-else-if="type === 'multiselect'"
        :id="id"
        :name="name"
        v-model="selectedValue"
        @blur="handleBlur"
        :required="required"
        multiple
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

    <p v-else-if="isValid && modelValue && successMessage" class="mt-2 text-sm text-green-600">
      {{ successMessage }}
    </p>

    <p v-if="helperText && !hasError" class="mt-1 text-xs text-slate-500">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup>
import { computed } from "vue";
import SearchableSelect from "./SearchableSelect.vue";

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
  clearable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(["update:modelValue", "blur"]);

const inputComponent = computed(() => {
  if (props.type === "textarea") return "textarea";
  return "input";
});

const hasError = computed(() => !!props.error);
const isValid = computed(() => !!props.modelValue && !hasError.value);

const inputClasses = computed(() => {
  const baseClasses =
    "w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500";

  if (hasError.value) {
    return `${baseClasses} border-red-500 focus:border-red-500 focus:ring-red-500/20`;
  } else if (isValid.value) {
    return `${baseClasses} border-slate-200`;
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

const handleSelectChange = (value) => {
  emit("update:modelValue", value);
};

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
