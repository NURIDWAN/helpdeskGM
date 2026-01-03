<script setup>
import { reactive, ref, watch, onMounted, computed } from "vue";
import { useUtilityReadingStore } from "@/stores/utilityReading";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import { useToastStore } from "@/stores/toast";
import {
  Flame,
  Droplet,
  Save,
  Gauge,
  MapPin,
} from "lucide-vue-next";

const props = defineProps({
  dailyRecordId: {
    type: [String, Number],
    default: null,
  },
  previousReadings: {
    type: Object,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["saved"]);

const utilityReadingStore = useUtilityReadingStore();
const { utilityReadings, loading, error } = storeToRefs(utilityReadingStore);
const { getByDailyRecordId, createUtilityReading, updateUtilityReading } = utilityReadingStore;

const toast = useToastStore();

const saving = ref(false);

// Gas form
const gasForm = reactive({
  id: null,
  category: "gas",
  location: "",
  meter_value: "",
  stove_type: "",
  gas_type: "",
  photo: null,
});
const gasPhotoPreview = ref(null);
const gasExists = ref(false);

// Water form
const waterForm = reactive({
  id: null,
  category: "water",
  location: "",
  meter_value: "",
  photo: null,
});
const waterPhotoPreview = ref(null);
const waterExists = ref(false);

// Computed Opening Values
const gasOpening = computed(() => {
  if (!props.previousReadings?.utility) return 0;
  // Find previous gas reading
  const prev = props.previousReadings.utility.find(
    (r) => (r.category?.value || r.category) === "gas"
  );
  return prev ? parseFloat(prev.meter_value) : 0;
});

const waterOpening = computed(() => {
  if (!props.previousReadings?.utility) return 0;
  // Find previous water reading
  // Logic: try to match location if possible, otherwise take first.
  // Ideally user should select location, but here we likely only have 1 main water meter usually, 
  // or we just take the first previous one if location matches.
  // For simplicity and safety: 
  // If waterForm.location is set, try to find match.
  // Else default to first found water reading.
  let prev = null;
  const prevs = props.previousReadings.utility.filter((r) => (r.category?.value || r.category) === "water");
  
  if (waterForm.location) {
     prev = prevs.find(p => p.location === waterForm.location);
  }
  
  if (!prev && prevs.length > 0) {
     prev = prevs[0]; // Default fallback
  }
  
  return prev ? parseFloat(prev.meter_value) : 0;
});

// Load existing data function - defined BEFORE the watch that uses it
const loadExistingData = async () => {
  if (!props.dailyRecordId) return;

  try {
    await getByDailyRecordId(props.dailyRecordId);
    
    // Find existing gas reading
    const gasReading = utilityReadings.value.find(
      (r) => (r.category?.value || r.category) === "gas"
    );
    if (gasReading) {
      gasForm.id = gasReading.id;
      gasForm.location = gasReading.location || "";
      gasForm.meter_value = gasReading.meter_value ?? "";
      gasForm.stove_type = gasReading.stove_type || "";
      gasForm.gas_type = gasReading.gas_type || "";
      gasPhotoPreview.value = gasReading.photo || null;
      gasExists.value = true;
    }

    // Find existing water reading
    const waterReading = utilityReadings.value.find(
      (r) => (r.category?.value || r.category) === "water"
    );
    if (waterReading) {
      waterForm.id = waterReading.id;
      waterForm.location = waterReading.location || "";
      waterForm.meter_value = waterReading.meter_value ?? "";
      waterPhotoPreview.value = waterReading.photo || null;
      waterExists.value = true;
    }
  } catch (error) {
    console.error("Error loading utility readings:", error);
  }
};

// Watch for dailyRecordId changes - defined AFTER loadExistingData
watch(
  () => props.dailyRecordId,
  async (newId) => {
    if (newId) {
      await loadExistingData();
    }
  },
  { immediate: true }
);

const handleGasPhotoSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    gasForm.photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      gasPhotoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleWaterPhotoSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    waterForm.photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      waterPhotoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const saveGas = async (recordId) => {
  const targetId = recordId || props.dailyRecordId;
  if (!targetId) return;
  // If empty and not exists, skip silently
  if (!gasForm.meter_value && !gasForm.id) return;
  
  if (!gasForm.meter_value) {
    throw new Error("Nilai meter gas wajib diisi");
  }

  // Double check validation before save
  const gVal = parseFloat(gasForm.meter_value);
  if (gVal < gasOpening.value) {
     throw new Error(`Nilai Meter Gas (${gVal}) tidak boleh lebih kecil dari Opening (${gasOpening.value})`);
  }
  
  saving.value = true;
  try {
    const payload = {
      daily_record_id: targetId,
      category: "gas",
      location: gasForm.location || null,
      meter_value: parseFloat(gasForm.meter_value),
      stove_type: gasForm.stove_type || null,
      gas_type: gasForm.gas_type || null,
      photo: gasForm.photo,
    };

    if (gasForm.id) {
      await updateUtilityReading(gasForm.id, payload);
    } else {
      await createUtilityReading(payload, targetId);
    }
    
    await loadExistingData();
  } catch (err) {
    console.error("Error saving gas:", err);
    throw err;
  } finally {
    saving.value = false;
  }
};

const saveWater = async (recordId) => {
  const targetId = recordId || props.dailyRecordId;
  if (!targetId) return;
  // If empty and not exists, skip silently
  if (!waterForm.meter_value && !waterForm.id) return;

  if (!waterForm.meter_value) {
    throw new Error("Nilai meter air wajib diisi");
  }

  // Double check validation before save
  const wVal = parseFloat(waterForm.meter_value);
  if (wVal < waterOpening.value) {
     throw new Error(`Nilai Meter Air (${wVal}) tidak boleh lebih kecil dari Opening (${waterOpening.value})`);
  }
  
  saving.value = true;
  try {
    const payload = {
      daily_record_id: targetId,
      category: "water",
      location: waterForm.location || null,
      meter_value: parseFloat(waterForm.meter_value),
      photo: waterForm.photo,
    };

    if (waterForm.id) {
      await updateUtilityReading(waterForm.id, payload);
    } else {
      await createUtilityReading(payload, targetId);
    }
    
    await loadExistingData();
  } catch (err) {
    console.error("Error saving water:", err);
    throw err;
  } finally {
    saving.value = false;
  }
};

const validate = () => {
  const missing = [];
  // Strict validation: must have value (new or edit) AND photo
  
  // Gas
  if (!gasForm.meter_value) {
      missing.push("Nilai Meter Gas");
  } else {
      // Logic validation
      if (parseFloat(gasForm.meter_value) < gasOpening.value) {
          missing.push(`Meter Gas < Opening (${gasOpening.value})`);
      }
  }

  // Check photo: must be a file object (new) or have photoPreview (existing)
  if (!gasForm.photo && !gasPhotoPreview.value) missing.push("Foto Meter Gas");
  
  // Water
  if (!waterForm.meter_value) {
      missing.push("Nilai Meter Air");
  } else {
      // Logic validation
      if (parseFloat(waterForm.meter_value) < waterOpening.value) {
          missing.push(`Meter Air < Opening (${waterOpening.value})`);
      }
  }

  if (!waterForm.photo && !waterPhotoPreview.value) missing.push("Foto Meter Air");

  return missing;
};

defineExpose({
  saveGas,
  saveWater,
  validate
});

onMounted(() => {
  if (props.dailyRecordId) {
    loadExistingData();
  }
});
</script>

<template>
  <div class="mt-8 pt-6 border-t border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-6">
      <Gauge :size="20" />
      Pembacaan Utilitas (Gas & Air)
    </h3>

    <!-- No daily record yet -->
    <!-- No daily record yet - REMOVED BLOCKER for single page flow -->

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Gas Form -->
      <div class="border border-orange-200 rounded-lg p-4 bg-orange-50/50">
        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-orange-200">
          <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
            <Flame :size="20" class="text-orange-600" />
          </div>
          <div class="flex-1">
            <h4 class="text-sm font-semibold text-gray-900">Gas</h4>
            <p class="text-xs text-gray-500">Pembacaan meter gas</p>
          </div>
          <div
            v-if="gasExists"
            class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded"
          >
            Tersimpan
          </div>
        </div>

        <div class="space-y-4">
          <!-- Meter Value -->
          <div>
            <div class="flex justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700">
                Nilai Meter <span class="text-red-500">*</span>
                </label>
                <div class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                    Opening: <span class="font-medium text-gray-900">{{ gasOpening }}</span>
                </div>
            </div>
            <div class="relative">
              <Gauge :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="gasForm.meter_value"
                type="number"
                step="0.01"
                placeholder="0.00"
                :disabled="disabled"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-50 bg-white"
                :class="{'border-red-500 focus:ring-red-500 focus:border-red-500': gasForm.meter_value && parseFloat(gasForm.meter_value) < gasOpening}"
              />
            </div>
            <p v-if="gasForm.meter_value && parseFloat(gasForm.meter_value) < gasOpening" class="text-xs text-red-600 mt-1">
                Nilai meter tidak boleh lebih kecil dari Opening ({{ gasOpening }})
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <!-- Stove Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kompor</label>
              <input
                v-model="gasForm.stove_type"
                type="text"
                placeholder="Contoh: Kompor 2 Tungku"
                :disabled="disabled"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-50 text-sm"
              />
            </div>
            <!-- Gas Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Gas</label>
              <input
                v-model="gasForm.gas_type"
                type="text"
                placeholder="Contoh: LPG 12kg"
                :disabled="disabled"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-50 text-sm"
              />
            </div>
          </div>

          <!-- Location -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <div class="relative">
              <MapPin :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="gasForm.location"
                type="text"
                placeholder="Lokasi meter"
                :disabled="disabled"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-50 text-sm"
              />
            </div>
          </div>

          <!-- Photo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Meter</label>
            <input
              type="file"
              accept="image/*"
              @change="handleGasPhotoSelect"
              :disabled="disabled"
              class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-orange-100 file:text-orange-700 hover:file:bg-orange-200"
            />
            <img
              v-if="gasPhotoPreview"
              :src="gasPhotoPreview"
              alt="Gas meter"
              class="mt-2 w-20 h-20 object-cover rounded border"
            />
          </div>

          <!-- Save Button - Hidden for orchestration, unless editing explicitly needed later? Removing for single page flow -->
        </div>
      </div>

      <!-- Water Form -->
      <div class="border border-blue-200 rounded-lg p-4 bg-blue-50/50">
        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-blue-200">
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <Droplet :size="20" class="text-blue-600" />
          </div>
          <div class="flex-1">
            <h4 class="text-sm font-semibold text-gray-900">Air</h4>
            <p class="text-xs text-gray-500">Pembacaan meter air</p>
          </div>
          <div
            v-if="waterExists"
            class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded"
          >
            Tersimpan
          </div>
        </div>

        <div class="space-y-4">
          <!-- Meter Value -->
          <div>
            <div class="flex justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700">
                Nilai Meter <span class="text-red-500">*</span>
                </label>
                <div class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                    Opening: <span class="font-medium text-gray-900">{{ waterOpening }}</span>
                </div>
            </div>
            <div class="relative">
              <Gauge :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="waterForm.meter_value"
                type="number"
                step="0.01"
                placeholder="0.00"
                :disabled="disabled"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 bg-white"
                :class="{'border-red-500 focus:ring-red-500 focus:border-red-500': waterForm.meter_value && parseFloat(waterForm.meter_value) < waterOpening}"
              />
            </div>
            <p v-if="waterForm.meter_value && parseFloat(waterForm.meter_value) < waterOpening" class="text-xs text-red-600 mt-1">
                Nilai meter tidak boleh lebih kecil dari Opening ({{ waterOpening }})
            </p>
          </div>

          <!-- Location -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
            <div class="relative">
              <MapPin :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="waterForm.location"
                type="text"
                placeholder="Lokasi meter"
                :disabled="disabled"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 text-sm"
              />
            </div>
          </div>

          <!-- Photo -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Meter</label>
            <input
              type="file"
              accept="image/*"
              @change="handleWaterPhotoSelect"
              :disabled="disabled"
              class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200"
            />
            <img
              v-if="waterPhotoPreview"
              :src="waterPhotoPreview"
              alt="Water meter"
              class="mt-2 w-20 h-20 object-cover rounded border"
            />
          </div>

          <!-- Save Button - Hidden for orchestration -->
        </div>
      </div>
    </div>

    <!-- Error display -->
    <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-sm text-red-600">{{ error }}</p>
    </div>
  </div>
</template>
