<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import {
  Activity,
  Home,
  Tag,
  Building,
  Users,
  LogOut,
  ChevronDown,
  File,
  BarChart3,
  ClipboardList,
  FileText,
  Settings,
  X,
  Calendar,
  FileSpreadsheet,
  Shield,
  MessageSquare,
} from "lucide-vue-next";
import { can } from "@/helpers/permissionHelper";

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close"]);

const authStore = useAuthStore();
const { logout } = authStore;

const isMasterDataOpen = ref(false);
const isOperationsOpen = ref(false);
const isManagementOpen = ref(false);

const handleLogout = async () => {
  await logout();
};

const toggleMasterData = () => {
  isMasterDataOpen.value = !isMasterDataOpen.value;
};

const toggleOperations = () => {
  isOperationsOpen.value = !isOperationsOpen.value;
};

const toggleManagement = () => {
  isManagementOpen.value = !isManagementOpen.value;
};

const closeSidebar = () => {
  emit("close");
};
</script>

<template>
  <aside
    :class="[
      'w-64 bg-white shadow-lg transition-transform duration-300 ease-in-out',
      'fixed inset-y-0 left-0 z-50',
      'lg:relative lg:translate-x-0 lg:z-auto lg:h-full',
      isOpen ? 'translate-x-0' : '-translate-x-full',
    ]"
  >
    <!-- Mobile Close Button -->
    <div class="lg:hidden flex justify-end p-4 border-b border-gray-100">
      <button
        @click="closeSidebar"
        class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg"
      >
        <X :size="24" />
      </button>
    </div>

    <div class="p-4 lg:p-6 border-b border-gray-100">
      <h1 class="text-xl lg:text-2xl font-bold text-blue-600 flex items-center">
        <Activity :size="24" class="lg:w-8 lg:h-8 mr-2" />
        <span class="hidden sm:block">GA Maintenance</span>
        <span class="sm:hidden">TEAM GA</span>
      </h1>
    </div>
    <nav class="mt-4 lg:mt-6">
      <!-- Dashboard -->
      <div class="mb-4 lg:mb-6">
        <RouterLink
          :to="{ name: 'admin.dashboard' }"
          @click="closeSidebar"
          class="flex items-center px-4 lg:px-6 py-3 text-gray-600 hover:bg-gray-50 hover:border-l-4 hover:border-gray-200"
          :class="{
            'bg-blue-50 border-l-4 border-blue-600':
              $route.name === 'admin.dashboard',
          }"
        >
          <BarChart3 :size="18" class="lg:w-5 lg:h-5 mr-3" />
          <span class="text-sm lg:text-base">Dashboard</span>
        </RouterLink>
      </div>

      <!-- Master Data Section -->
      <div
        class="mb-4 lg:mb-6"
        v-if="can('branch-menu') || can('job-template-menu') || can('whatsapp-setting-menu') || can('ticket-category-menu')"
      >
        <div class="px-4 lg:px-6 py-2">
          <h3
            class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
          >
            Data Master
          </h3>
        </div>

        <button
          @click="toggleMasterData"
          class="flex items-center justify-between w-full px-4 lg:px-6 py-3 text-gray-600 hover:bg-gray-50 hover:border-l-4 hover:border-gray-200"
          :class="{
            'bg-blue-50 border-l-4 border-blue-600':
              $route.name?.startsWith('admin.branch') ||
              $route.name?.startsWith('admin.job-template') ||
              $route.name?.startsWith('admin.ticket-categor') ||
              $route.name?.startsWith('admin.whatsapp'),
          }"
          v-if="can('branch-menu') || can('job-template-menu') || can('whatsapp-setting-menu') || can('ticket-category-menu')"
        >
          <div class="flex items-center">
            <Settings :size="18" class="lg:w-5 lg:h-5 mr-3" />
            <span class="text-sm lg:text-base">Konfigurasi</span>
          </div>
          <ChevronDown
            :size="16"
            :class="{ 'rotate-180': isMasterDataOpen }"
            class="transition-transform duration-200"
          />
        </button>

        <!-- Master Data Submenu -->
        <div v-show="isMasterDataOpen" class="bg-gray-50">
          <RouterLink
            v-if="can('branch-menu')"
            :to="{ name: 'admin.branches' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.branch'),
            }"
          >
            <Building :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Data Cabang</span>
          </RouterLink>
          <RouterLink
            v-if="can('job-template-menu')"
            :to="{ name: 'admin.job-templates' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.job-template'),
            }"
          >
            <Tag :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Template Job</span>
          </RouterLink>
          <RouterLink
            v-if="can('ticket-category-menu')"
            :to="{ name: 'admin.ticket-categories' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.ticket-categor'),
            }"
          >
            <Tag :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Kategori Tiket</span>
          </RouterLink>
          <RouterLink
            v-if="can('whatsapp-setting-menu')"
            :to="{ name: 'admin.whatsapp-settings' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.whatsapp'),
            }"
          >
            <MessageSquare :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Pengaturan WhatsApp</span>
          </RouterLink>
        </div>
      </div>

      <!-- Operations Section -->
      <div
        class="mb-4 lg:mb-6"
        v-if="
          can('ticket-menu') ||
          can('work-order-menu') ||
          can('work-report-menu') ||
          can('daily-record-menu')
        "
      >
        <div class="px-4 lg:px-6 py-2">
          <h3
            class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
          >
            Operasional
          </h3>
        </div>

        <button
          @click="toggleOperations"
          class="flex items-center justify-between w-full px-4 lg:px-6 py-3 text-gray-600 hover:bg-gray-50 hover:border-l-4 hover:border-gray-200"
          :class="{
            'bg-blue-50 border-l-4 border-blue-600':
              $route.name?.startsWith('admin.ticket') ||
              $route.name?.startsWith('admin.workorder') ||
              $route.name?.startsWith('admin.workreport') ||
              $route.name?.startsWith('admin.daily-record'),
          }"
          v-if="
            can('ticket-menu') ||
            can('work-order-menu') ||
            can('work-report-menu') ||
            can('daily-record-menu')
          "
        >
          <div class="flex items-center">
            <Activity :size="18" class="lg:w-5 lg:h-5 mr-3" />
            <span class="text-sm lg:text-base">Manajemen Kerja</span>
          </div>
          <ChevronDown
            :size="16"
            :class="{ 'rotate-180': isOperationsOpen }"
            class="transition-transform duration-200"
          />
        </button>

        <!-- Operations Submenu -->
        <div v-show="isOperationsOpen" class="bg-gray-50">
          <RouterLink
            v-if="can('ticket-menu')"
            :to="{ name: 'admin.tickets' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.ticket'),
            }"
          >
            <Tag :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Data Tiket</span>
          </RouterLink>
          <RouterLink
            v-if="can('work-order-menu')"
            :to="{ name: 'admin.workorders' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.workorder'),
            }"
          >
            <ClipboardList :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Surat Perintah Kerja</span>
          </RouterLink>
          <RouterLink
            v-if="can('work-report-menu')"
            :to="{ name: 'admin.workreports' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.workreport'),
            }"
          >
            <FileText :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Laporan Pekerjaan</span>
          </RouterLink>
          <RouterLink
            v-if="can('daily-record-menu')"
            :to="{ name: 'admin.daily-records' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.daily-record') &&
                $route.name !== 'admin.daily-usage-report',
            }"
          >
            <Calendar :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Laporan Harian Cabang</span>
          </RouterLink>
          <RouterLink
            v-if="can('daily-record-menu')"
            :to="{ name: 'admin.daily-usage-report' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name === 'admin.daily-usage-report',
            }"
          >
            <FileSpreadsheet :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Laporan Daily Usage</span>
          </RouterLink>
        </div>
      </div>

      <!-- Management Section -->
      <div class="mb-4 lg:mb-6" v-if="can('user-menu') || can('role-menu') || can('user-activity-menu')">
        <div class="px-4 lg:px-6 py-2">
          <h3
            class="text-xs font-semibold text-gray-500 uppercase tracking-wider"
          >
            Manajemen
          </h3>
        </div>

        <button
          @click="toggleManagement"
          class="flex items-center justify-between w-full px-4 lg:px-6 py-3 text-gray-600 hover:bg-gray-50 hover:border-l-4 hover:border-gray-200"
          :class="{
            'bg-blue-50 border-l-4 border-blue-600':
              $route.name?.startsWith('admin.user') ||
              $route.name?.startsWith('admin.role'),
          }"
          v-if="can('user-menu') || can('role-menu') || can('user-activity-menu')"
        >
          <div class="flex items-center">
            <Users :size="18" class="lg:w-5 lg:h-5 mr-3" />
            <span class="text-sm lg:text-base">Pengguna</span>
          </div>
          <ChevronDown
            :size="16"
            :class="{ 'rotate-180': isManagementOpen }"
            class="transition-transform duration-200"
          />
        </button>

        <!-- Management Submenu -->
        <div v-show="isManagementOpen" class="bg-gray-50">
          <RouterLink
            v-if="can('user-menu')"
            :to="{ name: 'admin.users' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name === 'admin.users' || $route.name?.startsWith('admin.user.'),
            }"
          >
            <Users :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Data User</span>
          </RouterLink>
          <RouterLink
            v-if="can('role-menu')"
            :to="{ name: 'admin.roles' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name?.startsWith('admin.role'),
            }"
          >
            <Shield :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Data Role</span>
          </RouterLink>
          <RouterLink
            v-if="can('user-activity-menu')"
            :to="{ name: 'admin.user-activity' }"
            @click="closeSidebar"
            class="flex items-center px-8 lg:px-12 py-2 text-gray-600 hover:bg-gray-100 hover:border-l-4 hover:border-gray-300"
            :class="{
              'bg-blue-50 border-l-4 border-blue-600':
                $route.name === 'admin.user-activity',
            }"
          >
            <Activity :size="16" class="mr-3" />
            <span class="text-sm lg:text-base">Monitoring Aktivitas</span>
          </RouterLink>
        </div>
      </div>
    </nav>
  </aside>
</template>
