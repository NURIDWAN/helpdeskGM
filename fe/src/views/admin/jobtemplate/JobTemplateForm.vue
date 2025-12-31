<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useJobTemplateStore } from "@/stores/jobTemplate";
import { useBranchStore } from "@/stores/branch";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import {
  ArrowLeft,
  FileText,
  Save,
  ChevronRight,
  Building,
  Check,
  X,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";

const route = useRoute();
const router = useRouter();

const jobTemplateStore = useJobTemplateStore();
const branchStore = useBranchStore();
const { error, loading, branches, frequencies } = storeToRefs(jobTemplateStore);
const {
  createJobTemplate,
  updateJobTemplate,
  fetchJobTemplate,
  fetchBranches,
} = jobTemplateStore;
const { branches: availableBranches } = storeToRefs(branchStore);
const { fetchBranches: fetchAvailableBranches } = branchStore;

// Computed
const isEdit = computed(() => route.name === "admin.job-template.edit");
const jobTemplateId = computed(() => route.params.id);

// Reactive data
const form = reactive({
  name: "",
  description: "",
  frequency: "",
  is_active: true,
  branches: [],
});

const selectedBranches = ref([]);

// Methods
const handleSubmit = async () => {
  try {
    const payload = {
      name: form.name.trim(),
      description: form.description.trim(),
      frequency: form.frequency.trim(),
      is_active: form.is_active,
      branches: selectedBranches.value.map((branchId) => ({
        branch_id: branchId,
      })),
    };

    if (isEdit.value) {
      await updateJobTemplate(jobTemplateId.value, payload);
    } else {
      await createJobTemplate(payload);
    }

    if (jobTemplateStore.success) {
      router.push({ name: "admin.job-templates" });
    }
  } catch (error) {
    console.error("Error submitting form:", error);
  }
};

const loadJobTemplateData = async () => {
  if (isEdit.value && jobTemplateId.value) {
    try {
      const jobTemplate = await fetchJobTemplate(jobTemplateId.value);
      if (jobTemplate) {
        form.name = jobTemplate.name;
        form.description = jobTemplate.description;
        form.frequency = jobTemplate.frequency;
        form.is_active = jobTemplate.is_active;

        // Set selected branches
        selectedBranches.value = jobTemplate.branches
          ? jobTemplate.branches.map((b) => b.id)
          : [];
      }
    } catch (error) {
      console.error("Error loading job template data:", error);
      router.push({ name: "admin.job-templates" });
    }
  }
};

const toggleBranch = (branchId) => {
  const index = selectedBranches.value.indexOf(branchId);
  if (index > -1) {
    selectedBranches.value.splice(index, 1);
  } else {
    selectedBranches.value.push(branchId);
  }
};

const isBranchSelected = (branchId) => {
  return selectedBranches.value.includes(branchId);
};

// Lifecycle
onMounted(async () => {
  await fetchAvailableBranches();
  await loadJobTemplateData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.job-templates' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEdit ? "Edit Template Job" : "Tambah Template Job Baru" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi template job yang sudah ada"
              : "Tambahkan template job baru ke dalam sistem"
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
      <RouterLink
        :to="{ name: 'admin.job-templates' }"
        class="hover:text-gray-700"
      >
        Template Job
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Form Card -->
  <FormCard
    title="Informasi Template Job"
    subtitle="Lengkapi data template job dengan benar"
    :icon="FileText"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Name Field -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.name"
            id="name"
            name="name"
            label="Nama Template"
            :label-icon="FileText"
            placeholder="Contoh: Maintenance Harian"
            :error="error?.name?.join(', ')"
          />
        </div>

        <!-- Description Field -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.description"
            id="description"
            name="description"
            label="Deskripsi"
            :label-icon="FileText"
            placeholder="Jelaskan detail template job ini"
            :error="error?.description?.join(', ')"
            :helper-text="`${form.description.length}/1000 karakter`"
            :required="true"
            type="textarea"
            :rows="4"
          />
        </div>

        <!-- Frequency Field -->
        <div>
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              <div class="flex items-center gap-2">
                <FileText :size="16" class="text-gray-500" />
                Frekuensi
              </div>
            </label>

            <select
              v-model="form.frequency"
              id="frequency"
              name="frequency"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              :class="{ 'border-red-500': error?.frequency }"
            >
              <option value="">Pilih frekuensi</option>
              <option
                v-for="frequency in frequencies"
                :key="frequency.value"
                :value="frequency.value"
              >
                {{ frequency.label }}
              </option>
            </select>

            <!-- Error Display -->
            <div v-if="error?.frequency" class="text-sm text-red-600">
              {{ error.frequency.join(", ") }}
            </div>
          </div>
        </div>

        <!-- Status Field -->
        <div>
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              <div class="flex items-center gap-2">
                <FileText :size="16" class="text-gray-500" />
                Status Template
              </div>
            </label>

            <div class="flex items-center gap-4">
              <label class="flex items-center">
                <input
                  v-model="form.is_active"
                  type="radio"
                  :value="true"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Aktif</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="form.is_active"
                  type="radio"
                  :value="false"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Tidak Aktif</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Branches Selection -->
        <div class="lg:col-span-2">
          <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              <div class="flex items-center gap-2">
                <Building :size="16" class="text-gray-500" />
                Pilih Cabang
              </div>
            </label>

            <div
              v-if="availableBranches.length === 0"
              class="text-sm text-gray-500"
            >
              Belum ada cabang yang tersedia
            </div>

            <div
              v-else
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"
            >
              <div
                v-for="branch in availableBranches"
                :key="branch.id"
                @click="toggleBranch(branch.id)"
                :class="[
                  'relative p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                  isBranchSelected(branch.id)
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300',
                ]"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div
                      v-if="branch.logo"
                      class="w-8 h-8 rounded-lg overflow-hidden border border-gray-200 flex-shrink-0"
                    >
                      <img
                        :src="branch.logo"
                        :alt="branch.name + ' logo'"
                        class="w-full h-full object-cover"
                      />
                    </div>
                    <div
                      v-else
                      class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0"
                    >
                      <Building :size="16" class="text-gray-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="font-medium text-gray-900 truncate">
                        {{ branch.name }}
                      </div>
                      <div class="text-sm text-gray-500 truncate">
                        {{ branch.address }}
                      </div>
                    </div>
                  </div>
                  <div
                    :class="[
                      'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                      isBranchSelected(branch.id)
                        ? 'border-blue-500 bg-blue-500'
                        : 'border-gray-300',
                    ]"
                  >
                    <Check
                      v-if="isBranchSelected(branch.id)"
                      :size="12"
                      class="text-white"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Error Display -->
            <div v-if="error?.branches" class="text-sm text-red-600">
              {{ error.branches.join(", ") }}
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
            :to="{ name: 'admin.job-templates' }"
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
                ? "Update Template"
                : "Simpan Template"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>
</template>
