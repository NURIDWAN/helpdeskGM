<script setup>
import { reactive, computed, ref, watch, onMounted } from "vue";
import { useElectricityMeterStore } from "@/stores/electricityMeter";
import { useElectricityReadingStore } from "@/stores/electricityReading";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import { useToastStore } from "@/stores/toast";
import {
  Zap,
  Save,
  Plus,
  X,
  Gauge,
  MapPin,
  RefreshCw,
} from "lucide-vue-next";

const props = defineProps({
  branchId: {
    type: [Number, String],
    required: true,
  },
  dailyRecordId: {
    type: [Number, String],
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  previousReadings: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["saved"]);

const electricityMeterStore = useElectricityMeterStore();
const { electricityMeters, loading: metersLoading } = storeToRefs(electricityMeterStore);
const { getByBranchId } = electricityMeterStore;

const electricityReadingStore = useElectricityReadingStore();
const { electricityReadings, loading: readingsLoading, error: readingsError } = storeToRefs(electricityReadingStore);
const { getByDailyRecordId, createMultipleReadings } = electricityReadingStore;

const toast = useToastStore();

// Form state - dynamically holds readings for each meter
const meterReadings = reactive({});
const photoPreviews = reactive({});
const saving = ref(false);

const getPreviousReading = (meterId) => {
  if (!props.previousReadings?.electricity) return null;
  return props.previousReadings.electricity.find(
    (r) => r.electricity_meter_id === meterId
  );
};

const getOpening = (meterId) => {
  const prev = getPreviousReading(meterId);
  return prev && prev.meter_value !== null ? parseFloat(prev.meter_value) : 0;
};

// Function definitions BEFORE watches
const loadMeters = async () => {
  if (!props.branchId) return;
  
  try {
    await getByBranchId(props.branchId);
    initializeMeterReadings();
  } catch (error) {
    console.error("Error loading meters:", error);
  }
};

const loadExistingReadings = async () => {
  if (!props.dailyRecordId) return;
  
  try {
    // First ensure meters are loaded if we have a branchId
    // This handles edit mode where meters might not be initialized yet
    if (props.branchId && electricityMeters.value.length === 0) {
      await getByBranchId(props.branchId);
      initializeMeterReadings();
    }
    
    await getByDailyRecordId(props.dailyRecordId);
    
    // Map existing readings to meter readings
    electricityReadings.value.forEach((reading) => {
      const meterId = reading.electricity_meter_id || reading.electricityMeter?.id;
      
      // If meter reading doesn't exist yet, initialize it
      if (meterId && !meterReadings[meterId]) {
        meterReadings[meterId] = {
          electricity_meter_id: meterId,
          meter_value: "",
          photo: null,
          id: null,
        };
      }
      
      if (meterId && meterReadings[meterId]) {
        meterReadings[meterId].meter_value = reading.meter_value ?? "";
        meterReadings[meterId].id = reading.id;
        
        // Set photo preview if exists
        if (reading.photo) {
          photoPreviews[meterId] = reading.photo;
        }
      }
    });
  } catch (error) {
    console.error("Error loading readings:", error);
  }
};

const initializeMeterReadings = () => {
  electricityMeters.value.forEach((meter) => {
    if (!meterReadings[meter.id]) {
      meterReadings[meter.id] = {
        electricity_meter_id: meter.id,
        meter_value: "",
        photo: null,
        id: null, // For existing readings
      };
    }
  });
};

// Watches AFTER function definitions
watch(
  () => props.branchId,
  async (newBranchId) => {
    if (newBranchId) {
      await loadMeters();
    }
  },
  { immediate: true }
);

watch(
  () => props.dailyRecordId,
  async (newDailyRecordId) => {
    if (newDailyRecordId) {
      await loadExistingReadings();
    }
  },
  { immediate: true }
);

const handlePhotoSelect = (meterId, event) => {
  const file = event.target.files[0];
  if (file && meterReadings[meterId]) {
    meterReadings[meterId].photo = file;
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreviews[meterId] = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removePhoto = (meterId) => {
  if (meterReadings[meterId]) {
    meterReadings[meterId].photo = null;
    delete photoPreviews[meterId];
  }
};

const hasValidReadings = computed(() => {
  return Object.values(meterReadings).some(
    (reading) => reading.meter_value !== ""
  );
});

const handleSave = async (recordId) => {
  const targetId = recordId || props.dailyRecordId;
  if (!targetId) {
    if (!props.dailyRecordId) {
       // Should not happen if orchestrated correctly
        throw new Error("Simpan laporan harian cabang terlebih dahulu");
    }
    return;
  }

  // Validate before saving
  const errors = validate();
  if (errors.length > 0) {
      throw new Error(`Data Listrik tidak valid: ${errors[0]}`);
  }

  saving.value = true;
  
  try {
    // Filter only readings that have values
    const readingsToSave = Object.values(meterReadings)
      .filter((reading) => reading.meter_value !== "")
      .map((reading) => ({
        id: reading.id || null, // Include existing reading ID for updates
        electricity_meter_id: reading.electricity_meter_id,
        meter_value: reading.meter_value ? parseFloat(reading.meter_value) : null,
        photo: reading.photo || null,   // Include photo file
      }));

    if (readingsToSave.length > 0) {
      await createMultipleReadings(targetId, readingsToSave);
      // toast handled parent
      // emit("saved"); // handled by parent flow
      // Reload to get updated data
      await loadExistingReadings();
    }
  } catch (error) {
    console.error("Error saving readings:", error);
    throw error;
  } finally {
    saving.value = false;
  }
};

const validate = () => {
  const missing = [];
  
  // Get all meters
  const meters = electricityMeters.value;
  if (meters.length === 0) return ["Listrik (Belum ada meter)"];

  // Check if AT LEAST ONE meter is filled AND complete
  let hasAtLeastOneFilled = false;

  for (const meter of meters) {
     const reading = meterReadings[meter.id];
     const meterVal = reading.meter_value;
     const photo = reading.photo || photoPreviews[meter.id]; // check file or existing

     // If any field is filled, ALL must be filled for this meter
     if (meterVal !== "" || photo) {
        hasAtLeastOneFilled = true;
        if (meterVal === "") missing.push(`Nilai Meter (${meter.meter_name})`);
        if (!photo) missing.push(`Foto Meter (${meter.meter_name})`);
        
        // Strict Validation: Closing >= Opening
        if (meterVal !== "") {
            const opening = getOpening(meter.id);
            if (parseFloat(meterVal) < opening) {
                missing.push(`Nilai Meter (${meter.meter_name}) < Opening (${opening})`);
            }
        }
     }
  }
  
  if (!hasAtLeastOneFilled) {
    missing.push("Listrik (Minimal satu meter diisi lengkap)");
  }

  return missing;
};

defineExpose({
  handleSave,
  validate
});

const refreshMeters = async () => {
  await loadMeters();
  if (props.dailyRecordId) {
    await loadExistingReadings();
  }
};

onMounted(async () => {
  if (props.branchId) {
    await loadMeters();
  }
  if (props.dailyRecordId) {
    await loadExistingReadings();
  }
});
</script>

<template>
  <div class="mt-8 pt-6 border-t border-gray-200">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
        <Zap :size="20" class="text-yellow-500" />
        Pembacaan Listrik Multi-Meter
      </h3>
      <button
        @click="refreshMeters"
        type="button"
        :disabled="metersLoading"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
      >
        <RefreshCw :size="14" :class="{ 'animate-spin': metersLoading }" />
        Refresh
      </button>
    </div>

    <!-- No branch selected -->
    <div
      v-if="!branchId"
      class="text-center py-6 text-gray-500 text-sm bg-yellow-50 rounded-lg border border-yellow-200"
    >
      <Zap :size="32" class="mx-auto mb-2 text-yellow-400" />
      <p class="text-yellow-700">Pilih cabang terlebih dahulu</p>
    </div>

    <!-- No daily record yet -->
    <!-- No daily record yet - REMOVED BLOCKER for single page flow -->

    <!-- Loading meters -->
    <div v-else-if="metersLoading" class="text-center py-6">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-2 text-gray-500 text-sm">Memuat data meter...</p>
    </div>

    <!-- No meters for this branch -->
    <div
      v-else-if="electricityMeters.length === 0"
      class="text-center py-8 text-gray-500 text-sm bg-orange-50 rounded-lg border border-orange-200"
    >
      <Zap :size="40" class="mx-auto mb-3 text-orange-400" />
      <p class="font-medium text-orange-800">Belum ada meter listrik untuk cabang ini</p>
      <p class="text-xs mt-2 text-orange-600">
        Admin harus menambahkan meter listrik melalui menu:<br/>
        <strong>Edit Cabang → Meter Listrik → Tambah Meter</strong>
      </p>
    </div>

    <!-- Meter readings form -->
    <div v-else class="space-y-4">
      <template v-for="meter in electricityMeters" :key="meter.id">
        <div
          v-if="meterReadings[meter.id]"
          class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow duration-200"
        >
        <!-- Meter Header -->
        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-100">
          <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
            <Zap :size="20" class="text-yellow-600" />
          </div>
          <div class="flex-1">
            <h4 class="text-sm font-semibold text-gray-900">{{ meter.meter_name }}</h4>
            <div class="flex items-center gap-4 text-xs text-gray-500">
              <span v-if="meter.meter_number">No: {{ meter.meter_number }}</span>
              <span v-if="meter.location" class="flex items-center gap-1">
                <MapPin :size="12" />
                {{ meter.location }}
              </span>
              <span v-if="meter.power_capacity">{{ meter.power_capacity }} VA</span>
            </div>
          </div>
          <div
            v-if="meterReadings[meter.id]?.id"
            class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded"
          >
            Tersimpan
          </div>
        </div>

        <!-- Reading inputs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Nilai Meter -->
          <div>
            <div class="flex justify-between mb-1">
                <label class="block text-sm font-medium text-gray-700">
                Nilai Meter <span class="text-red-500">*</span>
                </label>
                <div class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                    Opening: <span class="font-medium text-gray-900">{{ getOpening(meter.id) }}</span>
                </div>
            </div>
            <div class="relative">
              <Gauge :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                v-model="meterReadings[meter.id].meter_value"
                type="number"
                step="0.01"
                placeholder="0.00"
                :disabled="disabled"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 bg-white"
                :class="{'border-red-500 focus:ring-red-500 focus:border-red-500': meterReadings[meter.id].meter_value && parseFloat(meterReadings[meter.id].meter_value) < getOpening(meter.id)}"
              />
            </div>
            <p v-if="meterReadings[meter.id].meter_value && parseFloat(meterReadings[meter.id].meter_value) < getOpening(meter.id)" class="text-xs text-red-600 mt-1">
                Nilai Meter tidak boleh kurang dari Opening
            </p>
          </div>

          <!-- Foto Meter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Foto Meter <span class="text-red-500">*</span></label>
            <input
              :id="`photo-${meter.id}`"
              type="file"
              accept="image/*"
              @change="(e) => handlePhotoSelect(meter.id, e)"
              :disabled="disabled"
              class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <div v-if="photoPreviews[meter.id]" class="mt-2 relative inline-block">
              <img
                :src="photoPreviews[meter.id]"
                alt="Meter"
                class="w-20 h-20 object-cover rounded border"
              />
              <button
                type="button"
                @click="removePhoto(meter.id)"
                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5"
              >
                <X :size="12" />
              </button>
            </div>
          </div>
        </div>
        </div>
      </template>

      <!-- Save button - Hidden for orchestration -->

      <!-- Error display -->
      <div v-if="readingsError" class="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-600">{{ readingsError }}</p>
      </div>
    </div>
  </div>
</template>
