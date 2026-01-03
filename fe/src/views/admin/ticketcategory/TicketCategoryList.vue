<script setup>
import { ref, onMounted, computed } from "vue";
import { useTicketCategoryStore } from "@/stores/ticketCategory";
import { useToastStore } from "@/stores/toast";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import {
  Plus,
  Edit,
  Trash2,
  Search,
  Tag,
  ChevronRight,
} from "lucide-vue-next";

const categoryStore = useTicketCategoryStore();
const { categories, loading, meta } = storeToRefs(categoryStore);
const toast = useToastStore();

const search = ref("");
const currentPage = ref(1);
const perPage = ref(10);
const showDeleteModal = ref(false);
const categoryToDelete = ref(null);

const loadCategories = async () => {
  await categoryStore.fetchCategories({
    search: search.value,
    page: currentPage.value,
    row_per_page: perPage.value,
  });
};

const handleSearch = () => {
  currentPage.value = 1;
  loadCategories();
};

const handleDelete = (category) => {
  categoryToDelete.value = category;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  try {
    await categoryStore.deleteCategory(categoryToDelete.value.id);
    toast.success("Kategori berhasil dihapus");
    loadCategories();
  } catch (error) {
    toast.error(error.response?.data?.message || "Gagal menghapus kategori");
  } finally {
    showDeleteModal.value = false;
    categoryToDelete.value = null;
  }
};

const changePage = (page) => {
  currentPage.value = page;
  loadCategories();
};

onMounted(() => {
  loadCategories();
});
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Kategori Tiket</h1>
          <p class="text-gray-600 mt-1">
            Kelola kategori untuk mengklasifikasikan tiket
          </p>
        </div>
        <RouterLink
          v-if="can('ticket-category-create')"
          :to="{ name: 'admin.ticket-category.create' }"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Plus :size="20" />
          Tambah Kategori
        </RouterLink>
      </div>

      <!-- Breadcrumb -->
      <nav class="flex items-center space-x-2 text-sm text-gray-500">
        <RouterLink :to="{ name: 'admin.dashboard' }" class="hover:text-gray-700">
          Dashboard
        </RouterLink>
        <ChevronRight :size="16" />
        <span class="text-gray-900 font-medium">Kategori Tiket</span>
      </nav>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
      <div class="flex items-center gap-4">
        <div class="relative flex-1">
          <Search
            :size="20"
            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="search"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Cari kategori..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <button
          @click="handleSearch"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Cari
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
      </div>

      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">
              Nama
            </th>
            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">
              Deskripsi
            </th>
            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">
              Warna
            </th>
            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">
              Status
            </th>
            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="category in categories"
            :key="category.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-lg flex items-center justify-center"
                  :style="{ backgroundColor: category.color + '20' }"
                >
                  <Tag :size="16" :style="{ color: category.color }" />
                </div>
                <span class="font-medium text-gray-900">{{ category.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-gray-600 text-sm">
              {{ category.description || '-' }}
            </td>
            <td class="px-6 py-4 text-center">
              <div
                class="inline-block w-6 h-6 rounded-full border-2 border-white shadow"
                :style="{ backgroundColor: category.color }"
              ></div>
            </td>
            <td class="px-6 py-4 text-center">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  category.is_active
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-100 text-gray-600',
                ]"
              >
                {{ category.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-center gap-2">
                <RouterLink
                  v-if="can('ticket-category-edit')"
                  :to="{ name: 'admin.ticket-category.edit', params: { id: category.id } }"
                  class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                >
                  <Edit :size="18" />
                </RouterLink>
                <button
                  v-if="can('ticket-category-delete')"
                  @click="handleDelete(category)"
                  class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                >
                  <Trash2 :size="18" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="categories.length === 0">
            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
              Tidak ada kategori ditemukan
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="meta && meta.last_page > 1"
        class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
      >
        <p class="text-sm text-gray-600">
          Menampilkan {{ categories.length }} dari {{ meta.total }} kategori
        </p>
        <div class="flex gap-2">
          <button
            v-for="page in meta.last_page"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 rounded-lg text-sm',
              page === currentPage
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Hapus Kategori</h3>
        <p class="text-gray-600 mb-6">
          Apakah Anda yakin ingin menghapus kategori "{{ categoryToDelete?.name }}"?
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Batal
          </button>
          <button
            @click="confirmDelete"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
