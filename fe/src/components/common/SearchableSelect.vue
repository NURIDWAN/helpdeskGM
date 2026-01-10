<template>
  <div class="relative" ref="wrapper">
    <!-- Control -->
    <div
      class="w-full min-h-[46px] flex items-center gap-2 px-3 py-2 bg-white border rounded-lg cursor-pointer transition-all duration-200"
      :class="controlClasses"
      @click="toggleOpen"
    >
      <!-- Selected Value Display -->
      <div class="flex-1 flex items-center gap-2 min-w-0">
        <template v-if="!open">
          <span
            v-if="selectedItem"
            class="text-sm text-gray-900 truncate"
          >
            {{ selectedItem.label }}
          </span>
          <span v-else class="text-sm text-gray-400">
            {{ placeholder }}
          </span>
        </template>
        
        <!-- Search Input (shown when open) -->
        <input
          v-if="open"
          ref="searchInput"
          v-model="search"
          :placeholder="selectedItem ? selectedItem.label : placeholder"
          class="flex-1 min-w-0 outline-none text-sm text-gray-700 bg-transparent"
          @click.stop
          @keydown.escape="closeDropdown"
          @keydown.enter.prevent="selectHighlighted"
          @keydown.down.prevent="highlightNext"
          @keydown.up.prevent="highlightPrev"
        />
      </div>

      <!-- Clear Button -->
      <button
        v-if="clearable && selectedItem && !disabled"
        type="button"
        class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors"
        @click.stop="clearSelection"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Dropdown Arrow -->
      <svg
        class="w-4 h-4 text-gray-400 transition-transform duration-200"
        :class="{ 'rotate-180': open }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="open"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
      >
        <!-- No Results -->
        <div
          v-if="filteredOptions.length === 0"
          class="px-4 py-3 text-sm text-gray-500 text-center"
        >
          <span v-if="search">Tidak ditemukan "{{ search }}"</span>
          <span v-else>Tidak ada opsi tersedia</span>
        </div>

        <!-- Options List -->
        <ul v-else class="py-1">
          <li
            v-for="(opt, index) in filteredOptions"
            :key="opt.value"
            class="px-4 py-2.5 text-sm cursor-pointer transition-colors duration-100"
            :class="getOptionClasses(opt, index)"
            @click.stop="selectOption(opt)"
            @mouseenter="highlightedIndex = index"
          >
            <div class="flex items-center gap-2">
              <!-- Check icon for selected -->
              <svg
                v-if="isSelected(opt.value)"
                class="w-4 h-4 text-blue-600 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <div v-else class="w-4 h-4 flex-shrink-0"></div>
              
              <span class="truncate">{{ opt.label }}</span>
            </div>
          </li>
        </ul>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: "",
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: "Pilih...",
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
  clearable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const search = ref("");
const wrapper = ref(null);
const searchInput = ref(null);
const highlightedIndex = ref(0);

// Computed
const selectedItem = computed(() => {
  if (!props.modelValue && props.modelValue !== 0) return null;
  return props.options.find((o) => String(o.value) === String(props.modelValue));
});

const filteredOptions = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return props.options;
  return props.options.filter((o) => 
    o.label.toLowerCase().includes(q)
  );
});

const controlClasses = computed(() => {
  const classes = [];
  
  if (props.disabled) {
    classes.push("opacity-60 pointer-events-none bg-gray-50");
  }
  
  if (props.error) {
    classes.push("border-red-500 focus-within:ring-2 focus-within:ring-red-500 focus-within:border-red-500");
  } else if (open.value) {
    classes.push("border-blue-500 ring-2 ring-blue-500");
  } else {
    classes.push("border-gray-300 hover:border-gray-400 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500");
  }
  
  return classes.join(" ");
});

// Methods
const isSelected = (value) => String(props.modelValue) === String(value);

const getOptionClasses = (opt, index) => {
  const classes = [];
  
  if (isSelected(opt.value)) {
    classes.push("bg-blue-50 text-blue-700");
  } else if (highlightedIndex.value === index) {
    classes.push("bg-gray-100 text-gray-900");
  } else {
    classes.push("text-gray-700 hover:bg-gray-50");
  }
  
  return classes.join(" ");
};

const toggleOpen = () => {
  if (props.disabled) return;
  
  if (open.value) {
    closeDropdown();
  } else {
    openDropdown();
  }
};

const openDropdown = () => {
  open.value = true;
  search.value = "";
  highlightedIndex.value = 0;
  
  // Find current selected index
  if (props.modelValue) {
    const currentIndex = props.options.findIndex(
      (o) => String(o.value) === String(props.modelValue)
    );
    if (currentIndex >= 0) {
      highlightedIndex.value = currentIndex;
    }
  }
  
  nextTick(() => {
    searchInput.value?.focus();
  });
};

const closeDropdown = () => {
  open.value = false;
  search.value = "";
};

const selectOption = (opt) => {
  emit("update:modelValue", opt.value);
  closeDropdown();
};

const clearSelection = () => {
  emit("update:modelValue", "");
};

const selectHighlighted = () => {
  if (filteredOptions.value.length > 0 && highlightedIndex.value < filteredOptions.value.length) {
    selectOption(filteredOptions.value[highlightedIndex.value]);
  }
};

const highlightNext = () => {
  if (highlightedIndex.value < filteredOptions.value.length - 1) {
    highlightedIndex.value++;
  }
};

const highlightPrev = () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
  }
};

const onClickOutside = (e) => {
  if (!wrapper.value) return;
  if (!wrapper.value.contains(e.target)) {
    closeDropdown();
  }
};

// Reset highlighted index when search changes
watch(search, () => {
  highlightedIndex.value = 0;
});

// Lifecycle
onMounted(() => {
  document.addEventListener("click", onClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", onClickOutside);
});
</script>
