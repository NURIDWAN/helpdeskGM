<script setup>
import { reactive, onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useWorkOrderStore } from "@/stores/workOrder";
import FormCard from "@/components/common/FormCard.vue";
import {
  ArrowLeft,
  ChevronRight,
  ClipboardList,
  MessageSquare,
  Building,
  User,
  Calendar,
  Download,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();

const workOrderStore = useWorkOrderStore();
const { error, loading } = storeToRefs(workOrderStore);
const { fetchWorkOrder, downloadPDF, updateWorkOrder } = workOrderStore;

// Auth
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const isStaff = computed(() => (user.value?.roles || []).includes("staff"));

const workOrderId = computed(() => route.params.id);
const workOrder = ref(null);

// Status update state (for staff)
const statusForm = reactive({
  status: "",
});

const initializeStatusForm = () => {
  if (workOrder.value?.status) {
    statusForm.status = workOrder.value.status;
  }
};

// Success dialog state
const showStatusDialog = ref(false);

const loadWorkOrderData = async () => {
  if (workOrderId.value) {
    try {
      workOrder.value = await fetchWorkOrder(workOrderId.value);
      initializeStatusForm();
    } catch (error) {
      console.error("Error loading work order data:", error);
      router.push({ name: "admin.workorders" });
    }
  }
};

const getStatusColor = (status) => {
  const colors = {
    pending: "bg-yellow-100 text-yellow-800",
    in_progress: "bg-blue-100 text-blue-800",
    done: "bg-green-100 text-green-800",
  };
  return colors[status] || "bg-gray-100 text-gray-800";
};

const handleStatusUpdate = async () => {
  if (!workOrderId.value || !statusForm.status) return;
  try {
    await updateWorkOrder(workOrderId.value, { status: statusForm.status });
    // reflect locally
    workOrder.value.status = statusForm.status;
    showStatusDialog.value = true;
  } catch (e) {
    // no-op, store handles error
  }
};

const handleDownloadPDF = async () => {
  if (!workOrder.value) return;
  await downloadPDF(workOrderId.value);
};

onMounted(() => {
  loadWorkOrderData();
});
</script>

<template>
  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center gap-4 mb-4">
      <RouterLink
        :to="{ name: 'admin.workorders' }"
        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors duration-200"
      >
        <ArrowLeft :size="20" />
      </RouterLink>
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          Detail Surat Perintah Kerja
        </h1>
        <p class="text-gray-600 mt-1">Informasi lengkap surat perintah kerja</p>
      </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-gray-500">
      <RouterLink :to="{ name: 'admin.dashboard' }" class="hover:text-gray-700">
        Dashboard
      </RouterLink>
      <ChevronRight :size="16" />
      <RouterLink
        :to="{ name: 'admin.workorders' }"
        class="hover:text-gray-700"
      >
        Data Surat Perintah Kerja
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">Detail</span>
    </nav>
  </div>

  <div v-if="workOrder" class="space-y-6">
    <!-- Work Order Information -->
    <FormCard
      title="Informasi Surat Perintah Kerja"
      subtitle="Detail lengkap surat perintah kerja"
      :icon="ClipboardList"
    >
      <!-- Status Update Success Dialog -->
      <div
        v-if="showStatusDialog"
        class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 flex items-center justify-between"
      >
        <div class="text-sm text-green-800">
          Status SPK berhasil diperbarui menjadi
          <span class="font-semibold">{{
            statusForm.status.replace("_", " ").toUpperCase()
          }}</span>
        </div>
        <button
          class="text-sm text-green-700 hover:underline"
          @click="showStatusDialog = false"
        >
          Tutup
        </button>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Number -->
        <div class="lg:col-span-2">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-semibold text-gray-900 mb-2">
                {{ workOrder.number }}
              </h2>
              <p class="text-gray-600 leading-relaxed">
                {{ workOrder.description }}
              </p>
            </div>
            <div class="flex gap-2">
              <span
                :class="getStatusColor(workOrder.status)"
                class="px-3 py-1 rounded-full text-sm font-medium"
              >
                {{ workOrder.status.replace("_", " ").toUpperCase() }}
              </span>
            </div>
          </div>
        </div>



        <!-- Ticket -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Ticket
          </label>
          <p class="text-gray-900">
            <span v-if="workOrder.ticket">
              {{ workOrder.ticket.code }} - {{ workOrder.ticket.title }}
            </span>
            <span v-else class="text-gray-500 italic">
              SPK Standalone (Tidak terkait ticket)
            </span>
          </p>
        </div>

        <!-- Assigned To -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            Teknisi
          </label>
          <p class="text-gray-900">
            {{ workOrder.assigned_user?.name || "Belum di-assign" }}
          </p>
        </div>

        <!-- Created Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Tanggal Dibuat
          </label>
          <p class="text-gray-900">
            {{
              new Date(workOrder.created_at).toLocaleDateString("id-ID", {
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "2-digit",
                minute: "2-digit",
              })
            }}
          </p>
        </div>

        <!-- Updated Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Terakhir Diupdate
          </label>
          <p class="text-gray-900">
            {{
              new Date(workOrder.updated_at).toLocaleDateString("id-ID", {
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

    <!-- Document Information Section -->
    <FormCard
      title="Informasi Dokumen SPK"
      subtitle="Detail lengkap dokumen surat perintah kerja"
      :icon="ClipboardList"
    >
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Damage Unit -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Building :size="16" class="inline mr-2" />
            Unit Kerusakan
          </label>
          <p class="text-gray-900">
            {{ workOrder.damage_unit || "-" }}
          </p>
        </div>

        <!-- Contact Person -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            Contact Person
          </label>
          <p class="text-gray-900">
            {{ workOrder.contact_person || "-" }}
          </p>
        </div>

        <!-- Contact Phone -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <User :size="16" class="inline mr-2" />
            Nomor Telepon/HP
          </label>
          <p class="text-gray-900">
            {{ workOrder.contact_phone || "-" }}
          </p>
        </div>

        <!-- Product Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Jenis Produk
          </label>
          <p class="text-gray-900">
            {{ workOrder.product_type || "-" }}
          </p>
        </div>

        <!-- Brand -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Merk
          </label>
          <p class="text-gray-900">
            {{ workOrder.brand || "-" }}
          </p>
        </div>

        <!-- Model -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Tipe
          </label>
          <p class="text-gray-900">
            {{ workOrder.model || "-" }}
          </p>
        </div>

        <!-- Serial Number -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ClipboardList :size="16" class="inline mr-2" />
            Nomor Seri
          </label>
          <p class="text-gray-900">
            {{ workOrder.serial_number || "-" }}
          </p>
        </div>

        <!-- Purchase Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <Calendar :size="16" class="inline mr-2" />
            Tanggal Pembelian
          </label>
          <p class="text-gray-900">
            {{
              workOrder.purchase_date
                ? new Date(workOrder.purchase_date).toLocaleDateString(
                    "id-ID",
                    {
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                    }
                  )
                : "-"
            }}
          </p>
        </div>
      </div>
    </FormCard>

    <!-- Action Buttons -->
    <div
      class="flex justify-between items-center pt-6 border-t border-gray-200"
    >
      <RouterLink
        :to="{ name: 'admin.workorders' }"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200 font-medium"
      >
        Kembali ke List
      </RouterLink>
      <div class="flex gap-3">
        <button
          @click="handleDownloadPDF"
          class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-200 shadow-sm hover:shadow-md font-medium flex items-center gap-2"
        >
          <Download :size="16" />
          Download SPK
        </button>
        <RouterLink
          v-if="can('workorder-edit')"
          :to="{ name: 'admin.workorder.edit', params: { id: workOrderId } }"
          class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md font-medium"
        >
          Edit Surat Perintah Kerja
        </RouterLink>
      </div>
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
