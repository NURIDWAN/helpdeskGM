<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDailyRecordStore } from "@/stores/dailyRecord";
import { useUtilityReadingStore } from "@/stores/utilityReading";
import { useElectricityReadingStore } from "@/stores/electricityReading";
import { useBranchStore } from "@/stores/branch";
import { useUserStore } from "@/stores/user";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import {
  ArrowLeft,
  Save,
  ChevronRight,
  FileText,
  Building,
  Users,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";

import MultiMeterElectricityForm from "@/components/dailyrecord/MultiMeterElectricityForm.vue";
import GasWaterUtilityForm from "@/components/dailyrecord/GasWaterUtilityForm.vue";

const route = useRoute();
const router = useRouter();

const routePrefix = computed(() => {
  return route.name?.startsWith("app.") ? "app" : "admin";
});

// Check if user has branch (for auto-setting branch_id)
const userHasBranch = computed(() => {
  return currentUser.value?.branch?.id != null;
});

// Check if current user has role "user"
const isUser = computed(() => {
  return (currentUser.value?.roles || []).includes("user");
});

const dailyRecordStore = useDailyRecordStore();
const { error, loading } = storeToRefs(dailyRecordStore);
const { createDailyRecord, updateDailyRecord, fetchDailyRecord } =
  dailyRecordStore;

const branchStore = useBranchStore();
const { branches } = storeToRefs(branchStore);
const { fetchBranches } = branchStore;

const userStore = useUserStore();
const { users } = storeToRefs(userStore);
const { fetchUsers } = userStore;

const authStore = useAuthStore();
const { user: currentUser } = storeToRefs(authStore);

const utilityReadingStore = useUtilityReadingStore();
const {
  utilityReadings,
  loading: utilityReadingLoading,
  error: utilityReadingError,
} = storeToRefs(utilityReadingStore);
const {
  getByDailyRecordId,
  createUtilityReading,
  updateUtilityReading,
  deleteUtilityReading,
} = utilityReadingStore;

const electricityReadingStore = useElectricityReadingStore();
const { electricityReadings } = storeToRefs(electricityReadingStore);

const toast = useToastStore();

const isEdit = computed(
  () => route.name === `${routePrefix.value}.daily-record.edit`
);
const dailyRecordId = computed(() => route.params.id);

const form = reactive({
  branch_id: "",
  user_id: "",
  total_customers: "",
});

const createdDailyRecordId = ref(null);
const gasWaterFormRef = ref(null);
const electricityFormRef = ref(null);

// Validation for duplicate record
const checkDuplicateRecord = async (branchId) => {
  if (isEdit.value || !branchId) return false;

  try {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;
    const response = await api.get('/daily-records', {
       params: {
         branch_id: branchId,
         start_date: today,
         end_date: today,
         row_per_page: 1
       }
    });

    if (response.data.data && response.data.data.length > 0) {
       return true;
    }
  } catch (e) {
    console.error("Failed to check duplicate", e);
  }
  return false;
};

// Watch branch_id to warn user immediately
import { watch } from "vue";
import { axiosInstance as api } from "@/plugins/axios"; 

watch(() => form.branch_id, async (newBranchId) => {
  if (!newBranchId) return;

  const isDuplicate = await checkDuplicateRecord(newBranchId);
  if (isDuplicate) {
       toast.warning("Peringatan: Laporan harian untuk cabang ini hari ini SUDAH ADA. Anda tidak dapat membuat laporan ganda.", {
         timeout: 5000
       });
  }

  // If creating new record, fetch previous readings for validation
  if (!isEdit.value) {
      try {
          const response = await api.get('/daily-records/previous-readings', {
              params: { branch_id: newBranchId }
          });
          if (response.data.success) {
              if (!dailyRecordData.value) dailyRecordData.value = {};
              dailyRecordData.value.previous_readings = response.data.data;
          }
      } catch (e) {
          console.error("Failed to fetch previous readings", e);
      }
  }
});

const handleSubmit = async () => {
  loading.value = true;
  try {
    
    // Check duplicate before proceeding
    if (!isEdit.value && form.branch_id) {
       const isDuplicate = await checkDuplicateRecord(form.branch_id);
       if (isDuplicate) {
          toast.error("Gagal: Laporan harian untuk cabang ini sudah ada untuk hari ini. Silakan edit laporan yang sudah ada.");
          loading.value = false;
          return;
       }
    }

    const payload = {
      branch_id: form.branch_id,
      user_id: form.user_id || null,
      total_customers: form.total_customers
        ? parseInt(form.total_customers)
        : null,
    };

    // --- VALIDATION STEP ---
    const validationErrors = [];
    
    // Check Gas & Water
    if (gasWaterFormRef.value) {
      const gwErrors = gasWaterFormRef.value.validate();
      if (gwErrors && gwErrors.length > 0) {
        validationErrors.push(...gwErrors);
      }
    } else {
      // If ref is missing for some reason, we should probably warn or skip? 
      // It should be there.
      console.warn("GasWater form ref invalid");
    }
    
    // Check Electricity
    if (electricityFormRef.value) {
      const elecErrors = electricityFormRef.value.validate();
      if (elecErrors && elecErrors.length > 0) {
        validationErrors.push(...elecErrors);
      }
    } else {
        console.warn("Electricity form ref invalid");
    }

    if (validationErrors.length > 0) {
      toast.error(`Mohon lengkapi data: ${validationErrors.join(", ")}`);
      loading.value = false;
      return; 
    }
    // -----------------------

    let recordId = dailyRecordId.value;

    // 1. Save/Update Daily Record Header
    if (isEdit.value) {
      await updateDailyRecord(recordId, payload);
      if (error.value) throw new Error("Gagal mengupdate laporan harian");
    } else {
      // Create new if not exists (or update if already created in this session - logically shouldn't happen with full reload but good safety)
      // Actually for single page new:
      const dailyRecord = await createDailyRecord(payload);
      if (error.value || !dailyRecord) throw new Error("Gagal membuat laporan harian");
      recordId = dailyRecord.id;
      createdDailyRecordId.value = recordId; // Updates props for children
    }

    // 2. Save Children (Gas, Water, Electricity)
    // We need to wait for reactive props to propagate or pass ID directly.
    // Passing ID directly is safer.
    
    // Save Gas & Water
    if (gasWaterFormRef.value) {
        await gasWaterFormRef.value.saveGas(recordId);
        await gasWaterFormRef.value.saveWater(recordId);
    }

    // Save Electricity
    if (electricityFormRef.value) {
        await electricityFormRef.value.handleSave(recordId);
    }

    toast.success("Seluruh laporan harian berhasil disimpan");
    router.push({ name: `${routePrefix.value}.daily-records` });

  } catch (err) {
    console.error("Error submitting form:", err);
    toast.error(err.message || "Terjadi kesalahan saat menyimpan laporan");
  } finally {
    loading.value = false;
  }
};

const dailyRecordData = ref(null);

const loadDailyRecordData = async () => {
  if (isEdit.value && dailyRecordId.value) {
    try {
      const dailyRecord = await fetchDailyRecord(dailyRecordId.value);
      dailyRecordData.value = dailyRecord; // Store for children props

      if (dailyRecord) {
        form.branch_id = dailyRecord.branch?.id || "";
        form.user_id = dailyRecord.user?.id || "";
        form.total_customers = dailyRecord.total_customers || "";
        // Load utility readings after loading daily record
        await loadUtilityReadingsData();
      }
    } catch (error) {
      console.error("Error loading daily record data:", error);
      router.push({ name: `${routePrefix.value}.daily-records` });
    }
  } else {
    // For new record, set branch_id and user_id from current user
    if (isUser.value) {
      // If user role, auto-set branch and user from current user
      if (currentUser.value?.branch?.id) {
        form.branch_id = String(currentUser.value.branch.id);
      }
      if (currentUser.value?.id) {
        form.user_id = String(currentUser.value.id);
      }
    } else if (userHasBranch.value && !form.branch_id) {
      // For other roles, only set branch if user has branch
      form.branch_id = String(currentUser.value.branch.id);
    }
    
    // Auto-select current admin for PIC
    if (!isUser.value && !form.user_id && currentUser.value) {
         form.user_id = String(currentUser.value.id);
    }
    
    // Try to fetch previous record for new entry to show Opening data?
    // Current logic relies on 'fetchDailyRecord' which is by ID.
    // For new record, we might not have 'previous_readings' unless we fetch them separately based on branch?
    // Actually, backend calculates opening dynamically on display.
    // BUT frontend validation needs it on ENTRY.
    // If it's a new record, 'loadDailyRecordData' doesn't fetch any record.
    // So 'dailyRecordData' will be null.
    // We need to fetch 'previous' data for this branch if we want to validate 'Opening'.
    // However, existing backend 'show' returns previous data attached to current record.
    // For NEW record, we have no current record.
    // We might need a new endpoint or reused endpoint to get 'latest record for branch' to get closing values.
    // Wait, let's keep it simple. If simple edit, it works.
    // For new record, we might need to handle it.
    // Let's first ensure 'dailyRecordData' is set in Edit mode.
  }
};

const loadBranchesData = async () => {
  try {
    await fetchBranches();
  } catch (error) {
    console.error("Error loading branches:", error);
  }
};

const userOptions = computed(() => {
  const options = users.value.map((u) => ({ 
    value: String(u.id), 
    label: `${u.name}${u.branch?.name ? ' - ' + u.branch.name : ''}` 
  }));
  
  // Add current user (Admin) to the list if not already present
  if (currentUser.value && !options.find(o => o.value === String(currentUser.value.id))) {
      // Prepend current user
      const currentUserLabel = `${currentUser.value.name}${currentUser.value.branch?.name ? ' - ' + currentUser.value.branch.name : ''}`;
      options.unshift({ value: String(currentUser.value.id), label: currentUserLabel });
  }
  
  return options;
});

const loadUsersData = async () => {
  try {
    // Only fetch users with role 'user' (User Outlet)
    // The current admin user will be added manually in userOptions
    await fetchUsers({ roles: ['user'] });
  } catch (error) {
    console.error("Error loading users:", error);
  }
};

const loadUtilityReadingsData = async () => {
  const recordId = isEdit.value
    ? dailyRecordId.value
    : createdDailyRecordId.value;
  if (recordId) {
    try {
      await getByDailyRecordId(recordId);
    } catch (error) {
      console.error("Error loading utility readings:", error);
    }
  }
};



onMounted(() => {
  loadBranchesData();
  loadUsersData();
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
        <h1 class="text-3xl font-bold text-gray-900">
          {{
            isEdit
              ? "Edit Laporan Harian Cabang"
              : "Tambah Laporan Harian Cabang Baru"
          }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi laporan harian cabang yang sudah ada"
              : "Tambahkan laporan harian cabang baru ke dalam sistem"
          }}
        </p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink
        :to="{ name: `${routePrefix}.dashboard` }"
        class="hover:text-gray-700"
      >
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink
        :to="{ name: `${routePrefix}.daily-records` }"
        class="hover:text-gray-700"
      >
        Data Laporan Harian Cabang
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Form Card -->
  <FormCard
    title="Informasi Laporan Harian Cabang"
    subtitle="Lengkapi data laporan harian cabang dengan benar"
    :icon="FileText"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Branch - Always show, disabled if user has branch (unless has edit permission) or is user role -->
        <div>
          <FormField
            v-model="form.branch_id"
            id="branch_id"
            name="branch_id"
            label="Cabang"
            :label-icon="Building"
            :error="error?.branch_id?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih cabang"
            :options="
              branches.map((b) => ({ value: String(b.id), label: b.name }))
            "
            :disabled="(userHasBranch && !can('daily-record-edit')) || isUser"
          />
        </div>

        <!-- User (PIC) - Only for Admin, hide for user role -->
        <div v-if="can('daily-record-edit') && !isUser">
          <FormField
            v-model="form.user_id"
            id="user_id"
            name="user_id"
            label="User (PIC)"
            :label-icon="Users"
            :error="error?.user_id?.join(', ')"
            type="select"
            placeholder="Pilih user (PIC)"
            :options="userOptions"
          />
        </div>
        <!-- Show user info if user role -->
        <div v-else-if="isUser && currentUser">
          <FormField
            :model-value="currentUser.name"
            id="user_display"
            name="user_display"
            label="User (PIC)"
            :label-icon="Users"
            type="text"
            disabled
          />
        </div>

        <!-- Total Customers -->
        <div>
          <FormField
            v-model="form.total_customers"
            id="total_customers"
            name="total_customers"
            label="Total Pelanggan"
            :label-icon="Users"
            :error="error?.total_customers?.join(', ')"
            placeholder="Masukkan total pelanggan"
            type="number"
          />
        </div>
      </div>

      <!-- Gas & Water Utility Readings (Inline) -->
      <!-- Always show form, pass ID if exists. If new, ID is null initially. -->
      <!-- Gas & Water Utility Readings (Inline) -->
      <!-- Always show form, pass ID if exists. If new, ID is null initially. -->
      <GasWaterUtilityForm
        ref="gasWaterFormRef"
        :daily-record-id="isEdit ? dailyRecordId : createdDailyRecordId"
        :disabled="loading"
        :previous-readings="dailyRecordData?.previous_readings"
        @saved="loadUtilityReadingsData"
      />

      <!-- Multi-Meter Electricity Section -->
      <MultiMeterElectricityForm
        ref="electricityFormRef"
        :branch-id="form.branch_id"
        :daily-record-id="isEdit ? dailyRecordId : createdDailyRecordId"
        :previous-readings="dailyRecordData?.previous_readings"
        @saved="loadUtilityReadingsData"
      />

      <div
        class="flex justify-between items-center pt-8 mt-8 border-t border-gray-200"
      >
        <div class="text-sm text-gray-500">
          <span class="text-red-500">*</span> Wajib diisi
        </div>
        <div class="flex gap-3">
          <RouterLink
            :to="{ name: `${routePrefix}.daily-records` }"
            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
          >
            Batal
          </RouterLink>
          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 font-medium transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <div
              v-if="loading"
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
            ></div>
            <Save v-if="!loading" :size="16" />
            {{
              loading
                ? "Menyimpan Semuanya..."
                : isEdit
                ? "Update Laporan Harian"
                : "Simpan Laporan Harian"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>


</template>
