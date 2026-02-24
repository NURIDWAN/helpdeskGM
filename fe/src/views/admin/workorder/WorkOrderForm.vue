<script setup>
import { reactive, onMounted, computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useWorkOrderStore } from "@/stores/workOrder";
import { useTicketStore } from "@/stores/ticket";
import { useBranchStore } from "@/stores/branch";
import FormCard from "@/components/common/FormCard.vue";
import FormField from "@/components/common/FormField.vue";
import {
  ArrowLeft,
  Save,
  ChevronRight,
  ClipboardList,
  MessageSquare,
  CheckCircle2,
  User,
  Building,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import { useUserStore } from "@/stores/user";

const route = useRoute();
const router = useRouter();

const workOrderStore = useWorkOrderStore();
const { error, loading } = storeToRefs(workOrderStore);
const { createWorkOrder, updateWorkOrder, fetchWorkOrder } = workOrderStore;

const ticketStore = useTicketStore();
const { tickets } = storeToRefs(ticketStore);
const { fetchTickets } = ticketStore;

const branchStore = useBranchStore();
const { branches } = storeToRefs(branchStore);
const { fetchBranches } = branchStore;

const userStore = useUserStore();
const { fetchUsers } = userStore;

const staffUsers = ref([]);
const contactPersonUsers = ref([]);

const isEdit = computed(() => route.name === "admin.workorder.edit");
const workOrderId = computed(() => route.params.id);

const form = reactive({
  ticket_id: null,
  branch_id: null,
  assigned_staff: [], // Changed to array for multi-select
  description: null,
  status: "pending",
  damage_unit: null,
  contact_person: null,
  contact_phone: null,
  product_type: null,
  brand: null,
  model: null,
  serial_number: null,
  purchase_date: null,
});

// Computed to check if standalone mode (no ticket selected)
const isStandalone = computed(() => !form.ticket_id);

const statuses = [
  { value: "pending", label: "Pending" },
  { value: "in_progress", label: "In Progress" },
  { value: "done", label: "Done" },
];

const handleSubmit = async () => {
  try {
    const payload = {
      ticket_id: form.ticket_id || null,
      branch_id: form.ticket_id ? null : (form.branch_id || null), // Only send branch_id if standalone
      assigned_staff: form.assigned_staff.map(id => Number(id)), // Send as array
      description: form.description || null,
      status: form.status,
      damage_unit: form.damage_unit || null,
      contact_person: form.contact_person || null,
      contact_phone: form.contact_phone || null,
      product_type: form.product_type || null,
      brand: form.brand || null,
      model: form.model || null,
      serial_number: form.serial_number || null,
      purchase_date: form.purchase_date || null,
    };

    if (isEdit.value) {
      await updateWorkOrder(workOrderId.value, payload);
    } else {
      await createWorkOrder(payload);
    }

    if (!error.value) {
      router.push({ name: "admin.workorders" });
    }
  } catch (error) {
    console.error("Error submitting form:", error);
  }
};

const loadWorkOrderData = async () => {
  if (isEdit.value && workOrderId.value) {
    try {
      const workOrder = await fetchWorkOrder(workOrderId.value);
      if (workOrder) {
        form.ticket_id = workOrder.ticket_id;
        form.branch_id = workOrder.branch_id;
        // Load assigned_staff from response (array of user objects or IDs)
        if (workOrder.assigned_staff && Array.isArray(workOrder.assigned_staff)) {
          form.assigned_staff = workOrder.assigned_staff.map(s => String(s.id || s));
        } else if (workOrder.assigned_to) {
          // Fallback for old data
          form.assigned_staff = [String(workOrder.assigned_to)];
        }
        form.description = workOrder.description;
        form.status = workOrder.status;
        form.damage_unit = workOrder.damage_unit;
        form.contact_person = workOrder.contact_person;
        form.contact_phone = workOrder.contact_phone;
        form.product_type = workOrder.product_type;
        form.brand = workOrder.brand;
        form.model = workOrder.model;
        form.serial_number = workOrder.serial_number;
        form.purchase_date = workOrder.purchase_date;
      }
    } catch (error) {
      console.error("Error loading work order data:", error);
      router.push({ name: "admin.workorders" });
    }
  }
};

const loadTicketsData = async () => {
  try {
    await fetchTickets();
  } catch (error) {
    console.error("Error loading tickets:", error);
  }
};

const loadBranchesData = async () => {
  try {
    await fetchBranches();
  } catch (error) {
    console.error("Error loading branches:", error);
  }
};

// Computed property to filter tickets without work orders
const availableTickets = computed(() => {
  return tickets.value.filter(ticket => !ticket.work_order && ticket.status !== 'closed');
});

const loadUsersData = async () => {
  try {
    // Fetch staff for assigned_to dropdown
    const staffResponse = await fetchUsers({
      roles: ["staff"],
    });
    staffUsers.value = staffResponse || [];

    // Fetch users for contact_person dropdown
    const userResponse = await fetchUsers({
      roles: ["user"],
    });
    contactPersonUsers.value = userResponse || [];
  } catch (error) {
    console.error("Error loading users:", error);
  }
};

// Auto-fill contact phone when contact person is selected
watch(
  () => form.contact_person,
  (newContactPersonName) => {
    if (newContactPersonName) {
      const selectedUser = contactPersonUsers.value.find(
        (u) => u.name === newContactPersonName
      );
      if (selectedUser && selectedUser.phone_number) {
        form.contact_phone = selectedUser.phone_number;
      }
    }
  }
);

const selectedTicket = computed(() => {
  if (!form.ticket_id) return null;
  return tickets.value.find((t) => String(t.id) === String(form.ticket_id));
});

// Computed property to sort staff - prioritize ticket's or selected branch staff at top
const sortedStaffUsers = computed(() => {
  let branchId = null;
  
  if (selectedTicket.value && selectedTicket.value.branch_id) {
    branchId = selectedTicket.value.branch_id;
  } else if (form.branch_id) {
    branchId = form.branch_id;
  }
  
  if (!branchId) {
    // If no branch context, show all staff as-is
    return staffUsers.value;
  }
  
  // Sort staff: same branch first, then others
  const sorted = [...staffUsers.value].sort((a, b) => {
    const aMatch = String(a.branch_id) === String(branchId) ? 0 : 1;
    const bMatch = String(b.branch_id) === String(branchId) ? 0 : 1;
    return aMatch - bMatch;
  });
  
  return sorted;
});

// Function to auto-select all staff from ticket's or selected branch
const autoSelectBranchStaff = () => {
  if (isEdit.value) return; // Don't auto-select when editing
  if (staffUsers.value.length === 0) return;
  
  let branchId = null;
  
  if (form.ticket_id) {
    const ticket = tickets.value.find((t) => String(t.id) === String(form.ticket_id));
    if (ticket) {
      branchId = ticket.branch_id || ticket.branch?.id;
    }
  } else if (form.branch_id) {
    branchId = form.branch_id;
  }
  
  if (!branchId) return;
  
  // Find ALL staff from same branch (compare as strings to avoid type mismatch)
  const branchStaff = staffUsers.value.filter(
    (u) => String(u.branch_id) === String(branchId)
  );
  
  if (branchStaff.length > 0) {
    // Auto-select ALL staff from branch
    form.assigned_staff = branchStaff.map(s => String(s.id));
  }
};

// Watch ticket selection to auto-assign ALL staff from same branch
watch(
  () => form.ticket_id,
  (newTicketId) => {
    if (newTicketId && !isEdit.value) {
      autoSelectBranchStaff();
    }
  }
);

// Watch branch selection for standalone SPK to auto-assign staff
watch(
  () => form.branch_id,
  (newBranchId) => {
    if (newBranchId && !form.ticket_id && !isEdit.value) {
      autoSelectBranchStaff();
    }
  }
);

// Also watch staffUsers - when they load, check if we need to auto-select
watch(
  () => staffUsers.value.length,
  (newLength) => {
    if (newLength > 0 && form.ticket_id && !isEdit.value && form.assigned_staff.length === 0) {
      autoSelectBranchStaff();
    }
  }
);

const getTicketLabel = (t) => {
  const titlePart = t.title || `[${t.category?.name || 'Tanpa Kategori'}]`;
  return `${titlePart} - ${t.code}`;
};

onMounted(() => {
  loadTicketsData();
  loadBranchesData();
  loadWorkOrderData();
  loadUsersData();
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
          {{
            isEdit
              ? "Edit Surat Perintah Kerja"
              : "Tambah Surat Perintah Kerja Baru"
          }}
        </h1>
        <p class="text-gray-600 mt-1">
          {{
            isEdit
              ? "Ubah informasi surat perintah kerja yang sudah ada"
              : "Tambahkan surat perintah kerja baru ke dalam sistem"
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
        :to="{ name: 'admin.workorders' }"
        class="hover:text-gray-700"
      >
        Data Surat Perintah Kerja
      </RouterLink>
      <ChevronRight :size="16" />
      <span class="text-gray-900 font-medium">
        {{ isEdit ? "Edit" : "Tambah Baru" }}
      </span>
    </nav>
  </div>

  <!-- Form Card -->
  <FormCard
    title="Informasi Surat Perintah Kerja"
    subtitle="Lengkapi data surat perintah kerja dengan benar"
    :icon="ClipboardList"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Ticket -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.ticket_id"
            id="ticket_id"
            name="ticket_id"
            label="Ticket (Opsional)"
            :label-icon="ClipboardList"
            :error="error?.ticket_id?.join(', ')"
            :required="false"
            type="select"
            placeholder="Pilih ticket (kosongkan jika SPK standalone)"
            :options="[
              { value: '', label: '-- Tidak ada ticket (SPK Standalone) --' },
              ...availableTickets.map((t) => ({
                value: String(t.id),
                label: getTicketLabel(t),
              })),
            ]"
            :disabled="isEdit"
          />
        </div>

        <!-- Branch for Standalone SPK -->
        <div v-if="isStandalone && !isEdit" class="lg:col-span-2">
          <FormField
            v-model="form.branch_id"
            id="branch_id"
            name="branch_id"
            label="Cabang"
            :label-icon="Building"
            :error="error?.branch_id?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih cabang untuk SPK standalone"
            :options="
              branches.map((b) => ({
                value: String(b.id),
                label: b.name,
              }))
            "
          />
          <p class="mt-1 text-xs text-gray-500">
            Wajib dipilih untuk SPK tanpa ticket. Kode cabang akan digunakan dalam nomor SPK.
          </p>
        </div>

        <!-- Ticket Detail Preview -->
        <div v-if="selectedTicket" class="lg:col-span-2 -mt-4 mb-2">
          <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-2">
              <ClipboardList :size="16" />
              Detail Tiket
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 text-sm text-blue-800">
              <div class="flex gap-2">
                <span class="font-medium min-w-[80px]">Kategori:</span>
                <span>{{ selectedTicket.category?.name || "-" }}</span>
              </div>
              <div class="flex gap-2">
                <span class="font-medium min-w-[80px]">Pelapor:</span>
                <span>{{ selectedTicket.user?.name || "-" }}</span>
              </div>
              <div class="flex gap-2">
                <span class="font-medium min-w-[80px]">Cabang:</span>
                <span>{{ selectedTicket.branch?.name || "-" }}</span>
              </div>
              <div class="flex gap-2">
                <span class="font-medium min-w-[80px]">Prioritas:</span>
                <span class="capitalize">{{ selectedTicket.priority || "-" }}</span>
              </div>
              <div class="col-span-1 md:col-span-2 flex gap-2 mt-1 pt-2 border-t border-blue-200">
                <span class="font-medium min-w-[80px]">Deskripsi:</span>
                <span class="italic text-blue-900">{{ selectedTicket.description || "-" }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Assigned Staff (Multi-Select) -->
        <div class="lg:col-span-2">
          <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-3">
            <User :size="16" class="text-gray-500" />
            Assign ke Teknisi
          </label>
          <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 max-h-60 overflow-y-auto">
            <div v-if="sortedStaffUsers.length === 0" class="text-gray-500 text-sm">
              Tidak ada staff tersedia
            </div>
            <div v-else class="space-y-2">
              <label
                v-for="staff in sortedStaffUsers"
                :key="staff.id"
                class="flex items-center gap-3 p-2 rounded-lg hover:bg-white cursor-pointer transition-colors"
                :class="{
                  'bg-blue-50 border border-blue-200': form.assigned_staff.includes(String(staff.id)),
                  'bg-transparent': !form.assigned_staff.includes(String(staff.id))
                }"
              >
                <input
                  type="checkbox"
                  :value="String(staff.id)"
                  v-model="form.assigned_staff"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <div class="flex-1">
                  <span class="text-sm font-medium text-gray-900">{{ staff.name }}</span>
                  <span v-if="staff.branch" class="text-xs text-gray-500 ml-2">({{ staff.branch.name }})</span>
                </div>
              </label>
            </div>
          </div>
          <p v-if="error?.assigned_staff" class="mt-1 text-sm text-red-600">
            {{ error.assigned_staff.join(', ') }}
          </p>
          <p class="mt-2 text-xs text-gray-500">
            {{ form.assigned_staff.length }} teknisi dipilih
          </p>
        </div>

        <!-- Status -->
        <div>
          <FormField
            v-model="form.status"
            id="status"
            name="status"
            label="Status"
            :label-icon="CheckCircle2"
            :error="error?.status?.join(', ')"
            :required="true"
            type="select"
            placeholder="Pilih status"
            :options="statuses"
          />
        </div>

        <!-- Description -->
        <div class="lg:col-span-2">
          <FormField
            v-model="form.description"
            id="description"
            name="description"
            label="Deskripsi"
            :label-icon="MessageSquare"
            placeholder="Jelaskan detail surat perintah kerja"
            :error="error?.description?.join(', ')"
            type="textarea"
            :rows="4"
          />
        </div>
      </div>

      <!-- Document Information Section -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          Informasi Dokumen SPK
        </h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Damage Unit -->
          <div>
            <FormField
              v-model="form.damage_unit"
              id="damage_unit"
              name="damage_unit"
              label="Unit Kerusakan"
              placeholder="Masukkan unit yang rusak"
              :error="error?.damage_unit?.join(', ')"
              type="text"
            />
          </div>

          <!-- Contact Person -->
          <div>
            <FormField
              v-model="form.contact_person"
              id="contact_person"
              name="contact_person"
              label="Contact Person"
              placeholder="Pilih contact person"
              :error="error?.contact_person?.join(', ')"
              type="select"
              :options="[
                { value: '', label: '-- Pilih Contact Person --' },
                ...contactPersonUsers.map((u) => ({
                  value: u.name,
                  label: u.name,
                })),
              ]"
            />
          </div>

          <!-- Contact Phone -->
          <div>
            <FormField
              v-model="form.contact_phone"
              id="contact_phone"
              name="contact_phone"
              label="Nomor Telepon/HP"
              placeholder="Nomor telepon contact person"
              :error="error?.contact_phone?.join(', ')"
              type="tel"
            />
          </div>

          <!-- Product Type -->
          <div>
            <FormField
              v-model="form.product_type"
              id="product_type"
              name="product_type"
              label="Jenis Produk"
              placeholder="Jenis produk yang diperbaiki"
              :error="error?.product_type?.join(', ')"
              type="text"
            />
          </div>

          <!-- Brand -->
          <div>
            <FormField
              v-model="form.brand"
              id="brand"
              name="brand"
              label="Merk"
              placeholder="Merk produk"
              :error="error?.brand?.join(', ')"
              type="text"
            />
          </div>

          <!-- Model -->
          <div>
            <FormField
              v-model="form.model"
              id="model"
              name="model"
              label="Tipe"
              placeholder="Tipe/model produk"
              :error="error?.model?.join(', ')"
              type="text"
            />
          </div>

          <!-- Serial Number -->
          <div>
            <FormField
              v-model="form.serial_number"
              id="serial_number"
              name="serial_number"
              label="Nomor Seri"
              placeholder="Nomor seri produk"
              :error="error?.serial_number?.join(', ')"
              type="text"
            />
          </div>

          <!-- Purchase Date -->
          <div>
            <FormField
              v-model="form.purchase_date"
              id="purchase_date"
              name="purchase_date"
              label="Tanggal Pembelian"
              placeholder="Pilih tanggal pembelian"
              :error="error?.purchase_date?.join(', ')"
              type="date"
            />
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
            :to="{ name: 'admin.workorders' }"
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
              loading ? "Menyimpan..." : isEdit ? "Update SPK" : "Simpan SPK"
            }}
          </button>
        </div>
      </div>
    </form>
  </FormCard>
</template>
