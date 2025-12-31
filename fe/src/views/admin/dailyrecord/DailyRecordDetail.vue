<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDailyRecordStore } from "@/stores/dailyRecord";
import { useUtilityReadingStore } from "@/stores/utilityReading";
import { useElectricityReadingStore } from "@/stores/electricityReading";
import FormCard from "@/components/common/FormCard.vue";
import {
  ArrowLeft,
  ChevronRight,
  FileText,
  Building,
  User,
  Calendar,
  Flame,
  Droplet,
  Zap,
  MapPin,
  Gauge,
  Plus,
  Edit,
  Trash2,
  Save,
  X,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

const route = useRoute();
const router = useRouter();

const routePrefix = computed(() => {
  return route.name?.startsWith('app.') ? 'app' : 'admin';
});

const dailyRecordStore = useDailyRecordStore();
const { error, loading } = storeToRefs(dailyRecordStore);
const { fetchDailyRecord } = dailyRecordStore;

const utilityReadingStore = useUtilityReadingStore();
const {
  utilityReadings,
  loading: utilityReadingLoading,
  error: utilityReadingError,
} = storeToRefs(utilityReadingStore);
const { getByDailyRecordId, createUtilityReading, updateUtilityReading, deleteUtilityReading } = utilityReadingStore;

const electricityReadingStore = useElectricityReadingStore();
const { electricityReadings, loading: electricityLoading } = storeToRefs(electricityReadingStore);
const { getByDailyRecordId: getElectricityByDailyRecordId } = electricityReadingStore;

const dailyRecordId = computed(() => route.params.id);
const dailyRecord = ref(null);

// Utility Reading form state
const showUtilityReadingForm = ref(false);
const editingUtilityReading = ref(null);
const showDeleteModal = ref(false);
const utilityReadingToDelete = ref(null);

const utilityReadingForm = reactive({
  category: "",
  sub_type: "",
  location: "",
  meter_value: "",
  photo: null,
  // Fields for Gas category
  stove_type: "",
  gas_type: "",
  // Fields for Electricity category (WBP and LWBP)
  meter_value_wbp: "",
  meter_value_lwbp: "",
});

const categoryOptions = [
  { value: "gas", label: "Gas" },
  { value: "water", label: "Air" },
  // { value: "electricity", label: "Listrik" }, // Removed in favor of Multi-Meter
];

const subTypeOptions = [
  { value: "general", label: "General" },
  { value: "LUBP", label: "LUBP" },
  { value: "UBP", label: "UBP" },
];

const getCategoryIcon = (category) => {
  const icons = {
    gas: Flame,
    water: Droplet,
    electricity: Zap,
  };
  return icons[category] || Gauge;
};

const getCategoryLabel = (category) => {
  const option = categoryOptions.find(opt => opt.value === category);
  return option ? option.label : category;
};

const getSubTypeLabel = (subType) => {
  const option = subTypeOptions.find(opt => opt.value === subType);
  return option ? option.label : subType;
};

const loadDailyRecordData = async () => {
  if (dailyRecordId.value) {
    try {
      dailyRecord.value = await fetchDailyRecord(dailyRecordId.value);
      await loadUtilityReadingsData();
    } catch (error) {
      console.error("Error loading daily record data:", error);
      router.push({ name: `${routePrefix.value}.daily-records` });
    }
  }
};

const loadUtilityReadingsData = async () => {
  if (dailyRecordId.value) {
    try {
      await getByDailyRecordId(dailyRecordId.value);
      await getElectricityByDailyRecordId(dailyRecordId.value);
    } catch (error) {
      console.error("Error loading utility readings:", error);
    }
  }
};

const resetUtilityReadingForm = () => {
  utilityReadingForm.category = "";
  utilityReadingForm.sub_type = "";
  utilityReadingForm.location = "";
  utilityReadingForm.meter_value = "";
  utilityReadingForm.photo = null;
  utilityReadingForm.stove_type = "";
  utilityReadingForm.gas_type = "";
  utilityReadingForm.meter_value_wbp = "";
  utilityReadingForm.meter_value_lwbp = "";
  editingUtilityReading.value = null;
  showUtilityReadingForm.value = false;
};

// Processed Gas & Water Readings
const processedUtilityReadings = computed(() => {
  if (!utilityReadings.value) return [];
  
  const prevReadings = dailyRecord.value?.previous_readings?.utility || [];
  
  return utilityReadings.value.map(reading => {
    const prev = prevReadings.find(p => 
      p.category === reading.category && 
      (p.location === reading.location || (!p.location && !reading.location))
    );
    
    // Default Opening is 0 if no prev record found? Or maybe null? 
    // Usually 0 for first record, but simpler to show '-' if unknown.
    // However, for calculation, 0 is dangerous. Let's use null if no prev record.
    const opening = prev ? parseFloat(prev.meter_value) : 0; 
    const closing = parseFloat(reading.meter_value);
    const usage = closing - opening;
    
    return {
      ...reading,
      opening: prev ? opening : (dailyRecord.value?.previous_readings ? 0 : null), 
      closing: closing,
      usage: usage,
      hasPrev: !!prev
    };
  });
});

// Processed Electricity Readings
const processedElectricityReadings = computed(() => {
  if (!electricityReadings.value) return [];

  const prevReadings = dailyRecord.value?.previous_readings?.electricity || [];
  
  return electricityReadings.value.map(reading => {
    const prev = prevReadings.find(p => 
      p.electricity_meter_id === reading.electricity_meter_id
    );
    
    // WBP
    const wbpOpen = prev ? parseFloat(prev.meter_value_wbp || 0) : 0;
    const wbpClose = parseFloat(reading.meter_value_wbp || 0);
    const wbpUsage = wbpClose - wbpOpen;
    
    // LWBP
    const lwbpOpen = prev ? parseFloat(prev.meter_value_lwbp || 0) : 0;
    const lwbpClose = parseFloat(reading.meter_value_lwbp || 0);
    const lwbpUsage = lwbpClose - lwbpOpen;
    
    const totalUsage = wbpUsage + lwbpUsage;
    
    return {
      ...reading,
      wbp_open: prev ? wbpOpen : (dailyRecord.value?.previous_readings ? 0 : null),
      wbp_close: wbpClose,
      wbp_usage: wbpUsage,
      lwbp_open: prev ? lwbpOpen : (dailyRecord.value?.previous_readings ? 0 : null),
      lwbp_close: lwbpClose,
      lwbp_usage: lwbpUsage,
      total_usage: totalUsage
    };
  });
});

const totalElectricityUsage = computed(() => {
  return processedElectricityReadings.value.reduce((sum, item) => sum + item.total_usage, 0);
});

const openUtilityReadingForm = (utilityReading = null) => {
  if (utilityReading) {
    editingUtilityReading.value = utilityReading;
    utilityReadingForm.category = utilityReading.category?.value || utilityReading.category || "";
    utilityReadingForm.sub_type = utilityReading.sub_type?.value || utilityReading.sub_type || "";
    utilityReadingForm.location = utilityReading.location || "";
    utilityReadingForm.meter_value = utilityReading.meter_value || "";
    utilityReadingForm.stove_type = utilityReading.stove_type || "";
    utilityReadingForm.gas_type = utilityReading.gas_type || "";
    utilityReadingForm.meter_value_wbp = utilityReading.meter_value_wbp || "";
    utilityReadingForm.meter_value_lwbp = utilityReading.meter_value_lwbp || "";
    utilityReadingForm.photo = null; // Reset file input
    photoPreview.value = utilityReading.photo || null; // Show existing photo preview
  } else {
    resetUtilityReadingForm();
  }
  showUtilityReadingForm.value = true;
};



const handleUtilityReadingSubmit = async () => {
  // Validation based on category
  if (!utilityReadingForm.category) {
    return;
  }

  // For electricity, require WBP and LWBP
  if (utilityReadingForm.category === 'electricity') {
    if (!utilityReadingForm.meter_value_wbp || !utilityReadingForm.meter_value_lwbp) {
      return;
    }
  } else {
    // For other categories, require meter_value
    if (!utilityReadingForm.meter_value) {
      return;
    }
  }

  try {
    const payload = {
      daily_record_id: dailyRecordId.value,
      category: utilityReadingForm.category,
      // Sub type: null for gas, otherwise use the value
      sub_type: utilityReadingForm.category === 'gas' ? null : (utilityReadingForm.sub_type || null),
      location: utilityReadingForm.location || null,
      // Meter value: only for non-electricity categories
      meter_value: utilityReadingForm.category === 'electricity' 
        ? null 
        : (utilityReadingForm.meter_value ? parseFloat(utilityReadingForm.meter_value) : null),
      photo: utilityReadingForm.photo, // File object or null
      // Fields for Gas category
      stove_type: utilityReadingForm.stove_type || null,
      gas_type: utilityReadingForm.gas_type || null,
      // Fields for Electricity category (WBP and LWBP)
      meter_value_wbp: utilityReadingForm.meter_value_wbp ? parseFloat(utilityReadingForm.meter_value_wbp) : null,
      meter_value_lwbp: utilityReadingForm.meter_value_lwbp ? parseFloat(utilityReadingForm.meter_value_lwbp) : null,
    };

    if (editingUtilityReading.value) {
      await updateUtilityReading(editingUtilityReading.value.id, payload);
    } else {
      await createUtilityReading(payload, dailyRecordId.value);
    }

    if (!utilityReadingError.value) {
      resetUtilityReadingForm();
      await loadUtilityReadingsData();
    }
  } catch (error) {
    console.error("Error submitting utility reading:", error);
  }
};

const confirmDeleteUtilityReading = (utilityReading) => {
  utilityReadingToDelete.value = utilityReading;
  showDeleteModal.value = true;
};

const handleDeleteUtilityReading = async () => {
  if (utilityReadingToDelete.value) {
    await deleteUtilityReading(utilityReadingToDelete.value.id);
    if (!utilityReadingError.value) {
      showDeleteModal.value = false;
      utilityReadingToDelete.value = null;
      await loadUtilityReadingsData();
    }
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  utilityReadingToDelete.value = null;
};

const photoPreview = ref(null);

const handlePhotoSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    utilityReadingForm.photo = file;
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removePhoto = () => {
  utilityReadingForm.photo = null;
  photoPreview.value = null;
  // Reset file input
  const fileInput = document.getElementById('utility-reading-photo');
  if (fileInput) {
    fileInput.value = '';
  }
};

onMounted(() => {
  loadDailyRecordData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: `${routePrefix}.daily-records` }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Detail Laporan Harian Cabang</h1>
        <p class="text-gray-600 mt-1">
          Informasi lengkap dan pembacaan utilitas
        </p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="{ name: `${routePrefix}.dashboard` }" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink :to="{ name: `${routePrefix}.daily-records` }" class="hover:text-gray-700">
        Data Laporan Harian Cabang
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">Detail</span>
    </nav>
  </div>

  <div v-if="dailyRecord" class="space-y-6">
    <!-- Daily Record Information -->
    <FormCard
      title="Informasi Laporan Harian Cabang"
      subtitle="Detail lengkap laporan harian cabang"
      :icon="FileText"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Branch -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Building :size="16" class="inline mr-2" />
            Cabang
          </label>
          <p class="text-gray-900">{{ dailyRecord.branch?.name || "-" }}</p>
        </div>

        <!-- User (PIC) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            User (PIC)
          </label>
          <p class="text-gray-900">{{ dailyRecord.user?.name || "-" }}</p>
        </div>

        <!-- Total Customers -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            Total Pelanggan
          </label>
          <p class="text-gray-900">{{ dailyRecord.total_customers || "-" }}</p>
        </div>

        <!-- Created Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Tanggal Dibuat
          </label>
          <p class="text-gray-900">
            {{
              new Date(dailyRecord.created_at).toLocaleDateString("id-ID", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
              })
            }}
          </p>
        </div>
      </div>
    </FormCard>

    <!-- Combined Utility Readings Section (Gas, Water, Electricity) -->
    <FormCard
      title="Pembacaan Utilitas"
      subtitle="Data pembacaan meter gas, air, dan listrik"
      :icon="Gauge"
    >
      <div class="mb-4">
        <div class="text-sm text-gray-600">
          Daftar pembacaan meter utilitas untuk laporan harian cabang ini.
        </div>
      </div>

      <!-- Gas & Water Table -->
      <div v-if="processedUtilityReadings.length > 0" class="mb-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <Flame :size="16" class="text-orange-500" />
          <Droplet :size="16" class="text-blue-500" />
          Gas & Air
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50">
                <th class="text-left py-2 px-3 font-medium text-gray-600">Kategori</th>
                <th class="text-left py-2 px-3 font-medium text-gray-600">Lokasi</th>
                <th class="text-left py-2 px-3 font-medium text-gray-600">Detail</th>
                <th class="text-right py-2 px-3 font-medium text-gray-600">Opening</th>
                <th class="text-right py-2 px-3 font-medium text-gray-600">Closing</th>
                <th class="text-right py-2 px-3 font-medium text-gray-600">Pemakaian</th>
                <th class="text-center py-2 px-3 font-medium text-gray-600">Foto</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="reading in processedUtilityReadings"
                :key="reading.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-2 px-3">
                  <div class="flex items-center gap-2">
                    <component
                      :is="getCategoryIcon(reading.category?.value || reading.category)"
                      :size="16"
                      :class="{
                        'text-orange-500': (reading.category?.value || reading.category) === 'gas',
                        'text-blue-500': (reading.category?.value || reading.category) === 'water'
                      }"
                    />
                    <span>{{ getCategoryLabel(reading.category?.value || reading.category) }}</span>
                  </div>
                </td>
                <td class="py-2 px-3 text-gray-600">{{ reading.location || "-" }}</td>
                <td class="py-2 px-3 text-gray-600">
                  <template v-if="(reading.category?.value || reading.category) === 'gas'">
                    <div v-if="reading.stove_type" class="text-xs">Kompor: {{ reading.stove_type }}</div>
                    <div v-if="reading.gas_type" class="text-xs">Gas: {{ reading.gas_type }}</div>
                  </template>
                  <span v-else>-</span>
                </td>
                <td class="py-2 px-3 text-right text-gray-600">
                  {{ reading.opening !== null ? reading.opening : '-' }}
                </td>
                <td class="py-2 px-3 text-right font-medium text-gray-900">
                  {{ reading.closing ?? "-" }}
                </td>
                <td class="py-2 px-3 text-right font-bold text-blue-600">
                  {{ reading.usage !== null ? reading.usage.toFixed(2) : '-' }}
                </td>
                <td class="py-2 px-3 text-center">
                  <a v-if="reading.photo" :href="reading.photo" target="_blank">
                    <img :src="reading.photo" class="w-8 h-8 object-cover rounded mx-auto hover:opacity-80" alt="Foto" />
                  </a>
                  <span v-else class="text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="mb-6 text-center py-4 text-gray-500 text-sm">
        Belum ada pembacaan Gas & Air
      </div>

      <!-- Electricity Table -->
      <div v-if="processedElectricityReadings.length > 0">
        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <Zap :size="16" class="text-yellow-500" />
          Listrik Multi-Meter
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50">
                <th class="text-left py-2 px-3 font-medium text-gray-600">Meter Info</th>
                <th class="text-center py-2 px-3 font-medium text-gray-600 bg-yellow-50">WBP (Akhir - Awal)</th>
                <th class="text-center py-2 px-3 font-medium text-gray-600 bg-blue-50">LWBP (Akhir - Awal)</th>
                <th class="text-right py-2 px-3 font-medium text-gray-600 font-bold">Total Pakai</th>
                <th class="text-center py-2 px-3 font-medium text-gray-600">Foto</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="reading in processedElectricityReadings"
                :key="reading.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-2 px-3">
                  <div class="font-medium text-gray-900">
                    {{ reading.electricity_meter?.meter_name || reading.electricityMeter?.meter_name || '-' }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ reading.electricity_meter?.location || reading.electricityMeter?.location || "-" }}
                  </div>
                </td>
                <!-- WBP Column -->
                <td class="py-2 px-3 bg-yellow-50/30">
                  <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Akhir: {{ reading.wbp_close ?? '-' }}</span>
                    <span>Awal: {{ reading.wbp_open ?? '-' }}</span>
                  </div>
                  <div class="text-right font-medium text-gray-900 border-t border-gray-200 pt-1">
                    = {{ reading.wbp_usage !== null ? reading.wbp_usage.toFixed(2) : '-' }}
                  </div>
                </td>
                <!-- LWBP Column -->
                <td class="py-2 px-3 bg-blue-50/30">
                  <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Akhir: {{ reading.lwbp_close ?? '-' }}</span>
                    <span>Awal: {{ reading.lwbp_open ?? '-' }}</span>
                  </div>
                  <div class="text-right font-medium text-gray-900 border-t border-gray-200 pt-1">
                    = {{ reading.lwbp_usage !== null ? reading.lwbp_usage.toFixed(2) : '-' }}
                  </div>
                </td>
                
                <td class="py-2 px-3 text-right font-bold text-gray-900 text-base">
                  {{ reading.total_usage !== null ? reading.total_usage.toFixed(2) : '-' }}
                </td>

                <td class="py-2 px-3 text-center">
                  <div class="flex justify-center gap-2">
                    <a v-if="reading.photo_wbp" :href="reading.photo_wbp" target="_blank" title="Foto WBP">
                      <img :src="reading.photo_wbp" class="w-8 h-8 object-cover rounded hover:opacity-80" alt="WBP" />
                    </a>
                    <a v-if="reading.photo_lwbp" :href="reading.photo_lwbp" target="_blank" title="Foto LWBP">
                       <img :src="reading.photo_lwbp" class="w-8 h-8 object-cover rounded hover:opacity-80" alt="LWBP" />
                    </a>
                    <span v-if="!reading.photo_wbp && !reading.photo_lwbp" class="text-gray-400">-</span>
                  </div>
                </td>
              </tr>
              <!-- Summary Row -->
              <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                <td colspan="3" class="py-3 px-3 text-right text-gray-700 uppercase tracking-wider">Total Pemakaian Listrik</td>
                <td class="py-3 px-3 text-right text-blue-700 text-lg">
                   {{ totalElectricityUsage.toFixed(2) }}
                </td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="text-center py-4 text-gray-500 text-sm">
        Belum ada data listrik
      </div>
    </FormCard>

    <!-- Action Buttons -->
    <div
      class="flex justify-between items-center pt-6 border-t border-gray-200"
    >
      <RouterLink
        :to="{ name: `${routePrefix}.daily-records` }"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
      >
        Kembali ke List
      </RouterLink>
      <RouterLink
        v-if="can('daily-record-edit')"
        :to="{ name: `${routePrefix}.daily-record.edit`, params: { id: dailyRecordId } }"
        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md font-medium"
      >
        Edit Laporan Harian Cabang
      </RouterLink>
    </div>
  </div>

  <!-- Loading State -->
  <div v-else-if="loading" class="flex justify-center items-center py-12">
    <div
      class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
    ></div>
  </div>

  <!-- Error State -->
  <div v-else-if="error" class="text-center py-12 text-red-600">
    <p>{{ error }}</p>
  </div>


</template>

