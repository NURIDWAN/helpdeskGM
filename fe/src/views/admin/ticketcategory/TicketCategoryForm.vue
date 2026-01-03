<script setup>
import { reactive, ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useTicketCategoryStore } from "@/stores/ticketCategory";
import { useToastStore } from "@/stores/toast";
import { storeToRefs } from "pinia";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import {
  ArrowLeft,
  Save,
  ChevronRight,
  Tag,
  Palette,
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();

const categoryStore = useTicketCategoryStore();
const { loading, error } = storeToRefs(categoryStore);
const toast = useToastStore();

const isEdit = computed(() => route.name === "admin.ticket-category.edit");
const categoryId = computed(() => route.params.id);

const form = reactive({
  name: "",
  description: "",
  icon: "",
  color: "#3B82F6",
  is_active: true,
  sort_order: 0,
});

const colorOptions = [
  { value: "#3B82F6", label: "Biru" },
  { value: "#EF4444", label: "Merah" },
  { value: "#22C55E", label: "Hijau" },
  { value: "#F59E0B", label: "Kuning" },
  { value: "#8B5CF6", label: "Ungu" },
  { value: "#06B6D4", label: "Cyan" },
  { value: "#EC4899", label: "Pink" },
  { value: "#78716C", label: "Abu" },
];

const iconOptions = [
  { value: "Zap", label: "Zap (Listrik)" },
  { value: "Droplet", label: "Droplet (Air)" },
  { value: "Wind", label: "Wind (AC)" },
  { value: "Wifi", label: "Wifi (IT)" },
  { value: "Shield", label: "Shield (Keamanan)" },
  { value: "Sparkles", label: "Sparkles (Kebersihan)" },
  { value: "Building", label: "Building (Bangunan)" },
  { value: "Armchair", label: "Armchair (Furnitur)" },
  { value: "Tag", label: "Tag (Umum)" },
  { value: "MoreHorizontal", label: "More (Lainnya)" },
];

const loadCategory = async () => {
  if (isEdit.value && categoryId.value) {
    try {
      const category = await categoryStore.fetchCategory(categoryId.value);
      if (category) {
        form.name = category.name || "";
        form.description = category.description || "";
        form.icon = category.icon || "";
        form.color = category.color || "#3B82F6";
        form.is_active = category.is_active ?? true;
        form.sort_order = category.sort_order || 0;
      }
    } catch (error) {
      console.error("Error loading category:", error);
      router.push({ name: "admin.ticket-categories" });
    }
  }
};

const handleSubmit = async () => {
  try {
    if (isEdit.value) {
      await categoryStore.updateCategory(categoryId.value, form);
      toast.success("Kategori berhasil diperbarui");
    } else {
      await categoryStore.createCategory(form);
      toast.success("Kategori berhasil dibuat");
    }
    router.push({ name: "admin.ticket-categories" });
  } catch (error) {
    toast.error("Gagal menyimpan kategori");
  }
};

onMounted(() => {
  loadCategory();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.ticket-categories' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          {{ isEdit ? "Edit Kategori Tiket" : "Tambah Kategori Tiket" }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{ isEdit ? "Ubah informasi kategori" : "Buat kategori baru untuk tiket" }}
        </p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="{ name: 'admin.dashboard' }" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink :to="{ name: 'admin.ticket-categories' }" class="hover:text-gray-700">
        Kategori Tiket
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">{{ isEdit ? "Edit" : "Tambah" }}</span>
    </nav>
  </div>

  <!-- Form Card -->
  <FormCard
    title="Informasi Kategori"
    subtitle="Lengkapi data kategori tiket"
    :icon="Tag"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Name -->
        <div>
          <FormField
            v-model="form.name"
            id="name"
            name="name"
            label="Nama Kategori"
            :label-icon="Tag"
            :error="error?.name?.join(', ')"
            :required="true"
            placeholder="Masukkan nama kategori"
          />
        </div>

        <!-- Icon -->
        <div>
          <FormField
            v-model="form.icon"
            id="icon"
            name="icon"
            label="Icon"
            type="select"
            placeholder="Pilih icon"
            :options="iconOptions"
          />
        </div>

        <!-- Description -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.description"
            id="description"
            name="description"
            label="Deskripsi"
            type="text"
            placeholder="Deskripsi singkat kategori"
          />
        </div>

        <!-- Color -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Palette :size="16" class="inline mr-2" />
            Warna
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in colorOptions"
              :key="color.value"
              type="button"
              @click="form.color = color.value"
              :class="[
                'w-8 h-8 rounded-full border-2 transition-all',
                form.color === color.value
                  ? 'border-gray-900 scale-110'
                  : 'border-transparent hover:scale-105',
              ]"
              :style="{ backgroundColor: color.value }"
              :title="color.label"
            ></button>
          </div>
        </div>

        <!-- Sort Order -->
        <div>
          <FormField
            v-model="form.sort_order"
            id="sort_order"
            name="sort_order"
            label="Urutan"
            type="number"
            placeholder="0"
          />
        </div>

        <!-- Is Active -->
        <div class="lg:col-span-2">
          <label class="flex items-center gap-3 cursor-pointer">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
            />
            <span class="text-sm font-medium text-gray-700">Aktif</span>
          </label>
          <p class="text-xs text-gray-500 mt-1">
            Kategori nonaktif tidak akan muncul di pilihan tiket
          </p>
        </div>
      </div>

      <!-- Preview -->
      <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <p class="text-sm font-medium text-gray-700 mb-2">Preview:</p>
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center"
            :style="{ backgroundColor: form.color + '20' }"
          >
            <Tag :size="20" :style="{ color: form.color }" />
          </div>
          <div>
            <span class="font-medium text-gray-900">{{ form.name || 'Nama Kategori' }}</span>
            <p class="text-sm text-gray-500">{{ form.description || 'Deskripsi kategori' }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-between items-center pt-8 mt-8 border-t border-gray-200">
        <div class="text-sm text-gray-500">
          <span class="text-red-500">*</span> Wajib diisi
        </div>
        <div class="flex gap-3">
          <RouterLink
            :to="{ name: 'admin.ticket-categories' }"
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
            {{ loading ? "Menyimpan..." : isEdit ? "Update" : "Simpan" }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>
</template>
