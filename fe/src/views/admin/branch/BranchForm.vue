<script setup>
import { reactive, onMounted, computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useBranchStore } from "@/stores/branch";
import { useElectricityMeterStore } from "@/stores/electricityMeter";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import {
  ArrowLeft,
  Building,
  MapPin,
  Save,
  ChevronRight,
  Upload,
  Image,
  X,
  Zap,
  Plus,
  Edit,
  Trash2,
  Hash,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";

const route = useRoute();
const router = useRouter();

const branchStore = useBranchStore();
const { error, loading } = storeToRefs(branchStore);
const { createBranch, updateBranch, fetchBranch } = branchStore;

const electricityMeterStore = useElectricityMeterStore();
const { electricityMeters } = storeToRefs(electricityMeterStore);
const {
  getByBranchId,
  createElectricityMeter,
  updateElectricityMeter,
  deleteElectricityMeter,
} = electricityMeterStore;

// Computed
const isEdit = computed(() => route.name === "admin.branch.edit");
const branchId = computed(() => route.params.id);

// Reactive data
const form = reactive({
  code: "",
  name: "",
  address: "",
  logo: null,
});

const logoPreview = ref(null);
const logoFile = ref(null);

// Electricity Meter State
const showMeterModal = ref(false);
const isEditingMeter = ref(false);
const editingMeterId = ref(null);
const meterLoading = ref(false);
const showDeleteMeterModal = ref(false);
const meterToDelete = ref(null);

const meterForm = reactive({
  meter_name: "",
  meter_number: "",
  location: "",
  power_capacity: "",
  is_active: true,
});

const meterError = ref(null);

// Methods
const handleSubmit = async () => {
  try {
    const formData = new FormData();
    formData.append("code", form.code.toUpperCase());
    formData.append("name", form.name.trim());
    formData.append("address", form.address.trim());

    if (logoFile.value) {
      formData.append("logo", logoFile.value);
    }

    if (isEdit.value) {
      await updateBranch(branchId.value, formData);
    } else {
      await createBranch(formData);
    }

    if (branchStore.success) {
      router.push({ name: "admin.branches" });
    }
  } catch (error) {
    console.error("Error submitting form:", error);
  }
};

const loadBranchData = async () => {
  if (isEdit.value && branchId.value) {
    try {
      const branch = await fetchBranch(branchId.value);
      if (branch) {
        form.code = branch.code || "";
        form.name = branch.name;
        form.address = branch.address;
        form.logo = branch.logo;
        logoPreview.value = branch.logo;
      }
    } catch (error) {
      console.error("Error loading branch data:", error);
      router.push({ name: "admin.branches" });
    }
  }
};

const loadElectricityMeters = async () => {
  if (isEdit.value && branchId.value) {
    try {
      await getByBranchId(branchId.value);
    } catch (error) {
      console.error("Error loading electricity meters:", error);
    }
  }
};

const handleLogoUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    // Validate file type
    const allowedTypes = [
      "image/jpeg",
      "image/png",
      "image/jpg",
      "image/gif",
      "image/svg+xml",
    ];
    if (!allowedTypes.includes(file.type)) {
      alert(
        "Format file tidak didukung. Gunakan JPEG, PNG, JPG, GIF, atau SVG."
      );
      return;
    }

    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
      alert("Ukuran file terlalu besar. Maksimal 2MB.");
      return;
    }

    logoFile.value = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removeLogo = () => {
  logoFile.value = null;
  logoPreview.value = null;
  form.logo = null;
};

// Electricity Meter Methods
const openAddMeterModal = () => {
  isEditingMeter.value = false;
  editingMeterId.value = null;
  meterError.value = null;
  resetMeterForm();
  showMeterModal.value = true;
};

const openEditMeterModal = (meter) => {
  isEditingMeter.value = true;
  editingMeterId.value = meter.id;
  meterError.value = null;
  meterForm.meter_name = meter.meter_name;
  meterForm.meter_number = meter.meter_number;
  meterForm.location = meter.location || "";
  meterForm.power_capacity = meter.power_capacity || "";
  meterForm.is_active = meter.is_active;
  showMeterModal.value = true;
};

const closeMeterModal = () => {
  showMeterModal.value = false;
  resetMeterForm();
};

const resetMeterForm = () => {
  meterForm.meter_name = "";
  meterForm.meter_number = "";
  meterForm.location = "";
  meterForm.power_capacity = "";
  meterForm.is_active = true;
};

const handleMeterSubmit = async () => {
  meterLoading.value = true;
  meterError.value = null;

  try {
    const payload = {
      branch_id: branchId.value,
      meter_name: meterForm.meter_name,
      meter_number: meterForm.meter_number,
      location: meterForm.location || null,
      power_capacity: meterForm.power_capacity || null,
      is_active: meterForm.is_active,
    };

    if (isEditingMeter.value) {
      await updateElectricityMeter(editingMeterId.value, payload);
    } else {
      await createElectricityMeter(payload);
    }

    closeMeterModal();
    await loadElectricityMeters();
  } catch (error) {
    console.error("Error saving meter:", error);
    meterError.value = electricityMeterStore.error;
  } finally {
    meterLoading.value = false;
  }
};

const confirmDeleteMeter = (meter) => {
  meterToDelete.value = meter;
  showDeleteMeterModal.value = true;
};

const handleDeleteMeter = async () => {
  if (meterToDelete.value) {
    meterLoading.value = true;
    try {
      await deleteElectricityMeter(meterToDelete.value.id);
      showDeleteMeterModal.value = false;
      meterToDelete.value = null;
      await loadElectricityMeters();
    } catch (error) {
      console.error("Error deleting meter:", error);
    } finally {
      meterLoading.value = false;
    }
  }
};

const closeDeleteMeterModal = () => {
  showDeleteMeterModal.value = false;
  meterToDelete.value = null;
};

// Lifecycle
onMounted(() => {
  loadBranchData();
  loadElectricityMeters();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.branches' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEdit ? "Edit Cabang" : "Tambah Cabang Baru" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi cabang yang sudah ada"
              : "Tambahkan cabang baru ke dalam sistem"
          }}
        </p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="{ name: 'admin.dashboard' }" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink :to="{ name: 'admin.branches' }" class="hover:text-gray-700">
        Data Cabang
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Form Card - Branch Info -->
  <FormCard
    title="Informasi Cabang"
    subtitle="Lengkapi data cabang dengan benar"
    :icon="Building"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Code Field -->
        <div class="">
          <FormField
            v-model="form.code"
            id="code"
            name="code"
            label="Kode Cabang"
            :label-icon="Hash"
            placeholder="Contoh: BDG1"
            :error="error?.code?.join(', ')"
            :maxlength="4"
            helper-text="Kode unik 4 karakter"
            :required="true"
            @input="form.code = form.code.toUpperCase()"
          />
        </div>

        <!-- Name Field -->
        <div class="">
          <FormField
            v-model="form.name"
            id="name"
            name="name"
            label="Nama Cabang"
            :label-icon="Building"
            placeholder="Contoh: Cabang Jakarta Pusat"
            :error="error?.name?.join(', ')"
          />
        </div>

        <!-- Address Field -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.address"
            id="address"
            name="address"
            label="Alamat Lengkap"
            :label-icon="MapPin"
            placeholder="Masukkan alamat lengkap termasuk jalan, kota, dan kode pos"
            :error="error?.address?.join(', ')"
            :helper-text="`${form.address.length}/500 karakter`"
            :required="true"
            type="textarea"
            :rows="4"
          />
        </div>

        <!-- Logo Field -->
        <div class="lg:col-span-2">
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              <div class="flex items-center gap-2">
                <Image :size="16" class="text-gray-500" />
                Logo Cabang
              </div>
            </label>

            <!-- Logo Preview -->
            <div v-if="logoPreview" class="relative inline-block">
              <img
                :src="logoPreview"
                alt="Logo preview"
                class="w-32 h-32 object-cover rounded-lg border border-gray-200 shadow-sm"
              />
              <button
                type="button"
                @click="removeLogo"
                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
              >
                <X :size="12" />
              </button>
            </div>

            <!-- Upload Area -->
            <div
              v-if="!logoPreview"
              class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
            >
              <Upload :size="32" class="mx-auto text-gray-400 mb-2" />
              <p class="text-sm text-gray-600 mb-2">
                Klik untuk upload logo cabang
              </p>
              <p class="text-xs text-gray-500">
                Format: JPEG, PNG, JPG, GIF, SVG (Maks. 2MB)
              </p>
            </div>

            <!-- File Input -->
            <input
              type="file"
              ref="logoInput"
              @change="handleLogoUpload"
              accept="image/*"
              class="hidden"
            />

            <!-- Upload Button -->
            <button
              type="button"
              @click="$refs.logoInput.click()"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
              {{ logoPreview ? "Ganti Logo" : "Pilih Logo" }}
            </button>

            <!-- Error Display -->
            <div v-if="error?.logo" class="text-sm text-red-600">
              {{ error.logo.join(", ") }}
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div
        class="flex justify-between items-center pt-8 mt-8 border-t border-gray-200"
      >
        <div class="text-sm text-gray-500">
          <span class="text-red-500">*</span> Wajib diisi
        </div>
        <div class="flex gap-3">
          <RouterLink
            :to="{ name: 'admin.branches' }"
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
                ? "Menyimpan..."
                : isEdit
                ? "Update Cabang"
                : "Simpan Cabang"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>

  <!-- Electricity Meters Section (Edit Mode Only) -->
  <FormCard
    v-if="isEdit"
    title="Meter Listrik"
    subtitle="Kelola daftar meter listrik untuk cabang ini"
    :icon="Zap"
    class="mt-6"
  >
    <div class="space-y-4">
      <!-- Add Meter Button -->
      <div class="flex justify-end">
        <button
          @click="openAddMeterModal"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
        >
          <Plus :size="16" class="mr-2" />
          Tambah Meter
        </button>
      </div>

      <!-- Meters Table -->
      <div v-if="electricityMeters.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Nama Meter
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Nomor Meter
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Lokasi
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Kapasitas (kVA)
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Status
              </th>
              <th
                class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="meter in electricityMeters" :key="meter.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                {{ meter.meter_name }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ meter.meter_number }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ meter.location || "-" }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                {{ meter.power_capacity || "-" }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  :class="
                    meter.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-800'
                  "
                  class="px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ meter.is_active ? "Aktif" : "Nonaktif" }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-2">
                  <button
                    @click="openEditMeterModal(meter)"
                    class="text-blue-600 hover:text-blue-900 p-1"
                    title="Edit"
                  >
                    <Edit :size="16" />
                  </button>
                  <button
                    @click="confirmDeleteMeter(meter)"
                    class="text-red-600 hover:text-red-900 p-1"
                    title="Hapus"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg"
      >
        <Zap :size="32" class="mx-auto mb-2 text-gray-400" />
        <p>Belum ada meter listrik untuk cabang ini</p>
        <p class="text-sm">Klik tombol "Tambah Meter" untuk menambahkan</p>
      </div>
    </div>
  </FormCard>

  <!-- Meter Modal -->
  <div
    v-if="showMeterModal"
    class="fixed inset-0 z-50 flex items-center justify-center"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
  >
    <!-- Background overlay -->
    <div
      class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
      @click="closeMeterModal"
    ></div>

    <!-- Modal panel -->
    <div
      class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-full max-w-lg mx-4"
    >
      <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="flex items-center gap-3 mb-4">
          <div
            class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center"
          >
            <Zap :size="20" class="text-yellow-600" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ isEditingMeter ? "Edit Meter Listrik" : "Tambah Meter Listrik" }}
          </h3>
        </div>

        <form @submit.prevent="handleMeterSubmit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Nama Meter <span class="text-gray-400 text-xs">(Otomatis)</span></label
            >
            <input
              :value="isEditingMeter ? meterForm.meter_name : `Meter ${electricityMeters.length + 1}`"
              type="text"
              disabled
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed"
            />
            <p class="mt-1 text-xs text-gray-500">Nama meter diisi otomatis oleh sistem</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Nomor Meter <span class="text-red-500">*</span></label
            >
            <input
              v-model="meterForm.meter_number"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Contoh: PLN-12345678"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Lokasi</label
            >
            <input
              v-model="meterForm.location"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Contoh: Ruang Genset"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"
              >Kapasitas Daya (kVA)</label
            >
            <input
              v-model="meterForm.power_capacity"
              type="number"
              step="0.01"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Contoh: 100.00"
            />
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="meterForm.is_active"
              type="checkbox"
              id="is_active"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label for="is_active" class="text-sm text-gray-700"
              >Meter Aktif</label
            >
          </div>

          <!-- Error Display -->
          <div
            v-if="meterError"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"
          >
            {{ typeof meterError === 'string' ? meterError : 'Terjadi kesalahan saat menyimpan meter' }}
          </div>
        </form>
      </div>

      <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-row-reverse gap-3">
        <button
          type="button"
          @click="handleMeterSubmit"
          :disabled="meterLoading"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          <div
            v-if="meterLoading"
            class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
          ></div>
          <Save v-if="!meterLoading" :size="16" />
          {{ meterLoading ? "Menyimpan..." : "Simpan" }}
        </button>
        <button
          type="button"
          @click="closeMeterModal"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Batal
        </button>
      </div>
    </div>
  </div>

  <!-- Delete Meter Confirmation Modal -->
  <ConfirmationModal
    :show="showDeleteMeterModal"
    title="Konfirmasi Hapus Meter"
    :message="`Apakah Anda yakin ingin menghapus meter **${meterToDelete?.meter_name}**?`"
    subtitle="Tindakan ini tidak dapat dibatalkan."
    confirm-text="Hapus"
    cancel-text="Batal"
    loading-text="Menghapus..."
    :loading="meterLoading"
    type="danger"
    @close="closeDeleteMeterModal"
    @confirm="handleDeleteMeter"
  />
</template>
