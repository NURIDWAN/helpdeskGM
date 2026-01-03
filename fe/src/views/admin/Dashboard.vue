<script setup>
import { onMounted, ref, computed } from "vue";
import { useDashboardStore } from "@/stores/dashboard";
import { useAuthStore } from "@/stores/auth";
import { useUserActivityStore } from "@/stores/userActivity";
import { storeToRefs } from "pinia";
import { can } from "@/helpers/permissionHelper";
import {
  Tag,
  TrendingUp,
  TrendingDown,
  Clock,
  CheckCircle,
  FileText,
  Users,
  BarChart3,
  Award,
  Calendar,
  Building,
  Activity,
  ArrowRight,
  Plus,
} from "lucide-vue-next";
import Chart from 'chart.js/auto';
import JobCalendar from "@/components/common/JobCalendar.vue";

const dashboardStore = useDashboardStore();
const authStore = useAuthStore();
const {
  metrics,
  statusDistribution,
  ticketsPerBranch,
  topStaffResolved,
  fastestStaff,
  ticketsTrend,
  staffReportsTrend,
  unconfirmedTickets,
  unconfirmedWorkOrders,
  userRecentTickets,
  loading,
} = storeToRefs(dashboardStore);

// Computed properties for user role
const isStaff = computed(() => authStore.user?.roles?.includes("staff"));
const isAdmin = computed(() => authStore.user?.roles?.includes("admin") || authStore.user?.roles?.includes("superadmin"));
const isRegularUser = computed(() => !isStaff.value && !isAdmin.value);

const selectedPeriod = ref("day");
const statusChart = ref(null);
const branchChart = ref(null);
const ticketsTrendChart = ref(null);
const reportsTrendChart = ref(null);

// Inactive Users Logic
const userActivityStore = useUserActivityStore();
const inactiveUsers = ref([]);
const loadingInactive = ref(false);

const loadInactiveUsers = async () => {
  if (can('user-activity-list') && !isStaff.value) {
    loadingInactive.value = true;
    try {
      // Fetch inactive users (30+ days or never logged in)
      const users = await userActivityStore.fetchUsers({ activity_status: 'inactive' });
      inactiveUsers.value = users.slice(0, 5); // Show top 5
    } catch (e) {
      console.error("Failed to load inactive users", e);
    } finally {
      loadingInactive.value = false;
    }
  }
};


const periodOptions = [
  { value: "day", label: "Harian" },
  { value: "week", label: "Mingguan" },
];

const loadDashboardData = async () => {
  await Promise.all([
    dashboardStore.fetchAllData(selectedPeriod.value),
    loadInactiveUsers()
  ]);
};

const initializeCharts = () => {
  initializeStatusChart();
  initializeBranchChart();
  initializeTrendCharts();
};

const initializeStatusChart = () => {
  const ctx = document.getElementById("statusChart")?.getContext("2d");
  if (!ctx || !statusDistribution.value) return;

  if (statusChart.value) {
    statusChart.value.destroy();
  }

  statusChart.value = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: ["Open", "In Progress", "Resolved", "Closed"],
      datasets: [
        {
          data: [
            statusDistribution.value.open || 0,
            statusDistribution.value.in_progress || 0,
            statusDistribution.value.resolved || 0,
            statusDistribution.value.closed || 0,
          ],
          backgroundColor: ["#3B82F6", "#F59E0B", "#10B981", "#EF4444"],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            padding: 20,
            usePointStyle: true,
          },
        },
      },
      cutout: "70%",
    },
  });
};

const initializeBranchChart = () => {
  const ctx = document.getElementById("branchChart")?.getContext("2d");
  if (!ctx || !ticketsPerBranch.value.length) return;

  if (branchChart.value) {
    branchChart.value.destroy();
  }

  branchChart.value = new Chart(ctx, {
    type: "bar",
    data: {
      labels: ticketsPerBranch.value.map((item) => item.branch),
      datasets: [
        {
          label: "Jumlah Tiket",
          data: ticketsPerBranch.value.map((item) => item.count),
          backgroundColor: "#3B82F6",
          borderRadius: 8,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: "#F3F4F6",
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
    },
  });
};

const initializeTrendCharts = () => {
  initializeTicketsTrendChart();
  initializeReportsTrendChart();
};

const initializeTicketsTrendChart = () => {
  const ctx = document.getElementById("ticketsTrendChart")?.getContext("2d");
  if (!ctx || !ticketsTrend.value.length) return;

  if (ticketsTrendChart.value) {
    ticketsTrendChart.value.destroy();
  }

  ticketsTrendChart.value = new Chart(ctx, {
    type: "line",
    data: {
      labels: ticketsTrend.value.map((item) => item.period),
      datasets: [
        {
          label: "Tiket",
          data: ticketsTrend.value.map((item) => item.count),
          borderColor: "#3B82F6",
          backgroundColor: "rgba(59, 130, 246, 0.1)",
          tension: 0.4,
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: "#F3F4F6",
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
    },
  });
};

const initializeReportsTrendChart = () => {
  const ctx = document.getElementById("reportsTrendChart")?.getContext("2d");
  if (!ctx || !staffReportsTrend.value.length) return;

  if (reportsTrendChart.value) {
    reportsTrendChart.value.destroy();
  }

  reportsTrendChart.value = new Chart(ctx, {
    type: "line",
    data: {
      labels: staffReportsTrend.value.map((item) => item.period),
      datasets: [
        {
          label: "Laporan Staff",
          data: staffReportsTrend.value.map((item) => item.count),
          borderColor: "#10B981",
          backgroundColor: "rgba(16, 185, 129, 0.1)",
          tension: 0.4,
          fill: true,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: "#F3F4F6",
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
    },
  });
};

const handlePeriodChange = async () => {
  await loadDashboardData();
  setTimeout(() => {
    initializeCharts();
  }, 100);
};

onMounted(async () => {
  await loadDashboardData();
  setTimeout(() => {
    initializeCharts();
  }, 100);
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">
          {{
            isStaff
              ? "Dashboard pribadi - Tiket dan laporan kerja Anda"
              : isRegularUser
              ? "Selamat datang, pantau status tiket Anda di sini"
              : "Overview sistem GA Maintenance dan laporan kerja"
          }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        <RouterLink 
            v-if="isRegularUser" 
            :to="{ name: 'admin.ticket.create' }" 
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
            <Plus :size="18" />
            <span>Buat Tiket</span>
        </RouterLink>

        <select
          v-model="selectedPeriod"
          @change="handlePeriodChange"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option
            v-for="option in periodOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
      ></div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="space-y-6">
      <!-- Metrics Cards -->
      <div
        v-if="can('dashboard-view-metrics')"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
      >
        <!-- Total Tickets Today -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">
                {{ isStaff ? "Tiket Saya Hari Ini" : "Tiket Hari Ini" }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ metrics?.total_tickets_today || 0 }}
              </h3>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
              <Tag :size="24" class="text-blue-600" />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500"
              >{{ isStaff ? "Total bulan ini:" : "Total bulan ini:" }}
              {{ metrics?.total_tickets_this_month || 0 }}</span
            >
          </div>
        </div>

        <!-- Open Tickets -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">
                {{ isStaff ? "Tiket Saya yang Open" : "Tiket Open" }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ metrics?.open_tickets || 0 }}
              </h3>
            </div>
            <div class="p-3 bg-yellow-50 rounded-lg">
              <Clock :size="24" class="text-yellow-600" />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500">
              {{ isStaff ? "Perlu saya tangani" : "Perlu ditangani" }}
            </span>
          </div>
        </div>

        <!-- Average Resolution Time -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">
                {{
                  isStaff
                    ? "Rata-rata Penyelesaian Saya"
                    : "Rata-rata Penyelesaian"
                }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ metrics?.avg_resolution_time || 0 }}h
              </h3>
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
              <CheckCircle :size="24" class="text-green-600" />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500">
              {{ isStaff ? "Waktu rata-rata saya" : "Waktu rata-rata" }}
            </span>
          </div>
        </div>

        <!-- Active Work Orders (Hide for Regular User) -->
        <div v-if="!isRegularUser" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">
                {{ isStaff ? "SPK Saya yang Aktif" : "SPK Aktif" }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ metrics?.active_work_orders || 0 }}
              </h3>
            </div>
            <div class="p-3 bg-purple-50 rounded-lg">
              <FileText :size="24" class="text-purple-600" />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-500">
              {{ isStaff ? "Work order saya aktif" : "Work order aktif" }}
            </span>
          </div>
        </div>
      </div>

      <!-- Job Calendar Section -->
      <div v-if="!isAdmin" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <Calendar :size="20" class="text-blue-600" />
                <h3 class="text-lg font-semibold text-gray-800">Kalender Pekerjaan Rutin</h3>
            </div>
            <p class="text-gray-500 text-sm mt-1">Jadwal maintenance dan pekerjaan rutin bulanan</p>
        </div>
        <div class="p-6">
            <JobCalendar />
        </div>
      </div>

      <!-- Action Items for Staff -->
      <div v-if="isStaff" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Unconfirmed Tickets -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <Tag :size="20" class="text-blue-600" />
                    <h3 class="text-lg font-semibold text-gray-800">Tiket Perlu Konfirmasi</h3>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ unconfirmedTickets.length }}</span>
            </div>
            
            <div v-if="unconfirmedTickets.length > 0" class="space-y-3">
                <div v-for="ticket in unconfirmedTickets" :key="ticket.id" class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ ticket.code }}</span>
                        <span class="text-xs text-gray-500">{{ new Date(ticket.created_at).toLocaleDateString('id-ID') }}</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">{{ ticket.title }}</h4>
                    <div class="flex justify-end mt-2">
                        <RouterLink :to="{ name: 'admin.ticket.detail', params: { id: ticket.id } }" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Lihat Detail <ArrowRight :size="12" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
                <CheckCircle :size="32" class="mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Tidak ada tiket perlu konfirmasi</p>
            </div>
        </div>

        <!-- Unconfirmed Work Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <FileText :size="20" class="text-yellow-600" />
                    <h3 class="text-lg font-semibold text-gray-800">SPK Perlu Konfirmasi</h3>
                </div>
                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ unconfirmedWorkOrders.length }}</span>
            </div>
            
            <div v-if="unconfirmedWorkOrders.length > 0" class="space-y-3">
                <div v-for="spk in unconfirmedWorkOrders" :key="spk.id" class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded">{{ spk.number }}</span>
                        <span class="text-xs text-gray-500">{{ new Date(spk.created_at).toLocaleDateString('id-ID') }}</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">{{ spk.title }}</h4>
                    <div class="flex justify-end mt-2">
                        <RouterLink :to="{ name: 'admin.workorder.detail', params: { id: spk.id } }" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Lihat Detail <ArrowRight :size="12" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
                <CheckCircle :size="32" class="mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Tidak ada SPK perlu konfirmasi</p>
            </div>
        </div>
      </div>

      <!-- Charts Row - Different layout for staff vs admin -->
      <div
        v-if="can('dashboard-view-charts')"
        :class="
          isStaff || isRegularUser
            ? 'grid grid-cols-1 lg:grid-cols-2 gap-6'
            : 'grid grid-cols-1 lg:grid-cols-3 gap-6'
        "
      >
        <!-- Status Distribution Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <BarChart3 :size="20" class="text-blue-600" />
            <h3 class="text-lg font-semibold text-gray-800">
              {{
                isStaff
                  ? "Distribusi Status Tiket Saya"
                  : "Distribusi Status Tiket"
              }}
            </h3>
          </div>
          <div class="h-64">
            <canvas id="statusChart"></canvas>
          </div>
        </div>



        <!-- Tickets Per Branch Chart (Hide for Regular User) -->
        <div v-if="!isRegularUser" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <Building :size="20" class="text-green-600" />
            <h3 class="text-lg font-semibold text-gray-800">
              {{ isStaff ? "Tiket Saya per Cabang" : "Tiket per Cabang" }}
            </h3>
          </div>
          <div class="h-64">
            <canvas id="branchChart"></canvas>
          </div>
        </div>

        <!-- Recent Tickets for Regular User -->
        <div v-if="isRegularUser" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <Clock :size="20" class="text-blue-600" />
                    <h3 class="text-lg font-semibold text-gray-800">Tiket Tercepat Saya</h3>
                </div>
                <RouterLink :to="{ name: 'admin.tickets' }" class="text-sm text-blue-600 hover:text-blue-700">Lihat Semua</RouterLink>
            </div>
            
            <div v-if="userRecentTickets.length > 0" class="space-y-3">
                <div v-for="ticket in userRecentTickets" :key="ticket.id" class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span 
                            class="text-xs font-medium px-2 py-1 rounded"
                            :class="{
                                'bg-blue-100 text-blue-800': ticket.status === 'open',
                                'bg-yellow-100 text-yellow-800': ticket.status === 'in_progress',
                                'bg-green-100 text-green-800': ticket.status === 'resolved',
                                'bg-gray-100 text-gray-800': ticket.status === 'closed'
                            }"
                        >
                            {{ ticket.status.replace('_', ' ').toUpperCase() }}
                        </span>
                        <span class="text-xs text-gray-500">{{ new Date(ticket.created_at).toLocaleDateString('id-ID') }}</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">{{ ticket.title }}</h4>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-xs text-gray-500">{{ ticket.ticket_number }}</span>
                        <RouterLink :to="{ name: 'admin.ticket.detail', params: { id: ticket.id } }" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Detail <ArrowRight :size="12" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
                <CheckCircle :size="32" class="mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Anda belum membuat tiket</p>
            </div>
        </div>

        <!-- Top Staff Resolved - Only for Admin -->
        <div
          v-if="can('dashboard-view-staff-rankings') && !isStaff"
          class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
        >
          <div class="flex items-center gap-2 mb-4">
            <Award :size="20" class="text-yellow-600" />
            <h3 class="text-lg font-semibold text-gray-800">
              Top 5 Staff Resolved
            </h3>
          </div>
          <div class="space-y-3">
            <div
              v-for="(staff, index) in topStaffResolved"
              :key="staff.staff_name"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-bold text-blue-600"
                >
                  {{ index + 1 }}
                </div>
                <span class="font-medium text-gray-800">{{
                  staff.staff_name
                }}</span>
              </div>
              <span class="text-sm font-semibold text-blue-600">{{
                staff.resolved_count
              }}</span>
            </div>
            <div
              v-if="topStaffResolved.length === 0"
              class="text-center py-4 text-gray-500"
            >
              <Users :size="32" class="mx-auto mb-2 text-gray-300" />
              <p class="text-sm">Belum ada data</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Trends Row -->
      <div
        v-if="can('dashboard-view-trends')"
        :class="
          isStaff || isRegularUser
            ? 'grid grid-cols-1 lg:grid-cols-2 gap-6'
            : 'grid grid-cols-1 lg:grid-cols-2 gap-6'
        "
      >
        <!-- Tickets Trend -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <TrendingUp :size="20" class="text-blue-600" />
            <h3 class="text-lg font-semibold text-gray-800">

              {{ isStaff || isRegularUser ? "Trend Tiket Saya" : "Trend Tiket" }}
              {{ selectedPeriod === "day" ? "Harian" : "Mingguan" }}
            </h3>
          </div>
          <div class="h-64">
            <canvas v-if="ticketsTrend.length > 0" id="ticketsTrendChart"></canvas>
            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                <TrendingUp :size="48" class="mb-2 opacity-20" />
                <span class="text-sm">Belum ada data trend tiket</span>
            </div>
          </div>
        </div>

        <!-- Staff Reports Trend (Hide for Regular User) -->
        <div v-if="!isRegularUser" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <Calendar :size="20" class="text-purple-600" />
            <h3 class="text-lg font-semibold text-gray-800">
              {{ isStaff ? "Trend Laporan Saya" : "Trend Laporan Staff" }}
              {{ selectedPeriod === "day" ? "Harian" : "Mingguan" }}
            </h3>
          </div>
          <div class="h-64">
            <canvas v-if="staffReportsTrend.length > 0" id="reportsTrendChart"></canvas>
            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                <Calendar :size="48" class="mb-2 opacity-20" />
                <span class="text-sm">Belum ada data laporan</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Staff Performance - Only for Admin -->
      <div
        v-if="can('dashboard-view-staff-rankings') && !isStaff"
        class="grid grid-cols-1 lg:grid-cols-2 gap-6"
      >
        <!-- Fastest Staff -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <Activity :size="20" class="text-green-600" />
            <h3 class="text-lg font-semibold text-gray-800">Staff Tercepat</h3>
          </div>
          <div class="space-y-3">
            <div
              v-for="(staff, index) in fastestStaff"
              :key="staff.staff_name"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-sm font-bold text-green-600"
                >
                  {{ index + 1 }}
                </div>
                <div>
                  <span class="font-medium text-gray-800">{{
                    staff.staff_name
                  }}</span>
                  <p class="text-xs text-gray-500">
                    {{ staff.total_resolved }} tiket resolved
                  </p>
                </div>
              </div>
              <span class="text-sm font-semibold text-green-600"
                >{{ staff.avg_resolution_hours }}h</span
              >
            </div>
            <div
              v-if="fastestStaff.length === 0"
              class="text-center py-4 text-gray-500"
            >
              <Clock :size="32" class="mx-auto mb-2 text-gray-300" />
              <p class="text-sm">Belum ada data</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Inactive Users Widget -->
      <div
        v-if="can('user-activity-list') && !isStaff && inactiveUsers.length > 0"
        class="grid grid-cols-1 lg:grid-cols-2 gap-6"
      >
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <Users :size="20" class="text-red-500" />
              <h3 class="text-lg font-semibold text-gray-800">User Tidak Aktif (30 Hari+)</h3>
            </div>
            <RouterLink :to="{ name: 'admin.user-activity' }" class="text-sm text-blue-600 hover:text-blue-700">
              Lihat Semua
            </RouterLink>
          </div>
          
          <div class="space-y-3">
             <div 
               v-for="user in inactiveUsers" 
               :key="user.id"
               class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-100"
             >
                <div class="flex items-center gap-3">
                   <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-red-600 font-bold text-xs border border-red-200">
                      {{ user.name.charAt(0).toUpperCase() }}
                   </div>
                   <div>
                      <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                      <p class="text-xs text-gray-500 capitalize">{{ user.roles[0] || 'User' }}</p>
                   </div>
                </div>
                <div class="text-right">
                   <span class="text-xs font-medium text-red-600">
                      {{ user.days_since_login ? user.days_since_login + ' hari' : 'Belum pernah' }}
                   </span>
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
