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
import TopOutletUsage from "@/components/dashboard/TopOutletUsage.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Select } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";

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
  topOutletUsage,
  topOutletUsageLoading,
  topOutletUsageError,
  loading,
} = storeToRefs(dashboardStore);

// Computed properties for user role
const isStaff = computed(() => authStore.user?.roles?.includes("staff"));
const isSuperAdmin = computed(() => authStore.user?.roles?.includes("superadmin"));
const isAdmin = computed(() => authStore.user?.roles?.includes("admin"));
const isManagement = computed(() => isAdmin.value || isSuperAdmin.value);
const isRegularUser = computed(() => !isStaff.value && !isManagement.value);
const canViewDashboard = computed(() => can("dashboard-view"));
const canViewMetrics = computed(() => can("dashboard-view-metrics") || canViewDashboard.value);
const canViewCharts = computed(() => can("dashboard-view-charts") || canViewDashboard.value);
const canViewTrends = computed(() => can("dashboard-view-trends") || canViewDashboard.value);
const canViewStaffRankings = computed(() => can("dashboard-view-staff-rankings") && !isStaff.value);
const hasStatusData = computed(() => {
  const data = statusDistribution.value || {};
  return ["open", "in_progress", "resolved", "closed"].some((key) => Number(data[key] || 0) > 0);
});
const hasBranchData = computed(() => ticketsPerBranch.value.some((item) => Number(item.count || 0) > 0));

const selectedPeriod = ref("day");
const statusChart = ref(null);
const branchChart = ref(null);
const ticketsTrendChart = ref(null);
const reportsTrendChart = ref(null);
const cardPaddingClass = "p-3 sm:p-4 lg:p-5";
const chartHeaderClass = "mb-4 flex items-center gap-2";
const chartBodyClass = "h-56 rounded-lg bg-slate-50/50 px-2 py-3";

// Inactive Users Logic
const userActivityStore = useUserActivityStore();
const inactiveUsers = ref([]);
const loadingInactive = ref(false);

const loadInactiveUsers = async () => {
  if (can('user-activity-list') && isSuperAdmin.value) {
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

const dashboardMetricCards = computed(() => [
  {
    label: isStaff.value ? "Tiket Saya Hari Ini" : "Tiket Hari Ini",
    value: metrics.value?.total_tickets_today || 0,
    helper: `Total bulan ini: ${metrics.value?.total_tickets_this_month || 0}`,
    icon: Tag,
    iconClass: "bg-blue-50 text-blue-600",
    show: true,
  },
  {
    label: isStaff.value ? "Tiket Saya yang Open" : "Tiket Open",
    value: metrics.value?.open_tickets || 0,
    helper: isStaff.value ? "Perlu saya tangani" : "Perlu ditangani",
    icon: Clock,
    iconClass: "bg-amber-50 text-amber-600",
    show: true,
  },
  {
    label: isStaff.value
      ? "Rata-rata Penyelesaian Saya"
      : "Rata-rata Penyelesaian",
    value: `${metrics.value?.avg_resolution_time || 0}h`,
    helper: isStaff.value ? "Waktu rata-rata saya" : "Waktu rata-rata",
    icon: CheckCircle,
    iconClass: "bg-green-50 text-green-600",
    show: true,
  },
  {
    label: isStaff.value ? "SPK Saya yang Aktif" : "SPK Aktif",
    value: metrics.value?.active_work_orders || 0,
    helper: isStaff.value ? "Work order saya aktif" : "Work order aktif",
    icon: FileText,
    iconClass: "bg-violet-50 text-violet-600",
    show: !isRegularUser.value,
  },
]);

const ticketStatusVariant = (status) => {
  if (status === "open") return "info";
  if (status === "in_progress") return "warning";
  if (status === "resolved") return "success";
  return "muted";
};

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
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 text-sm mt-0.5">
          {{
            isStaff
              ? "Dashboard pribadi - Tiket dan laporan kerja Anda"
              : isSuperAdmin
              ? "Overview penuh sistem, operasional, dan aktivitas pengguna"
              : isAdmin
              ? "Overview operasional GA Maintenance dan laporan kerja"
              : "Overview sistem GA Maintenance dan laporan kerja"
          }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        <RouterLink 
            v-if="isRegularUser" 
            :to="{ name: 'admin.ticket.create' }" 
            custom
            v-slot="{ navigate }"
        >
          <Button type="button" @click="navigate">
            <Plus :size="18" />
            <span>Buat Tiket</span>
          </Button>
        </RouterLink>

        <Select
          v-model="selectedPeriod"
          @change="handlePeriodChange"
          class="w-36"
        >
          <option
            v-for="option in periodOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </Select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Skeleton v-for="item in 4" :key="item" class="h-28" />
    </div>

    <!-- Dashboard Content -->
    <div v-else class="space-y-3 sm:space-y-4">
      <!-- Metrics Cards -->
      <div
        v-if="canViewMetrics"
        class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4"
      >
        <Card
          v-for="card in dashboardMetricCards.filter((item) => item.show)"
          :key="card.label"
        >
          <CardContent :class="cardPaddingClass">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
              <h3 class="text-xl font-bold text-gray-800 mt-0.5">
                {{ card.value }}
              </h3>
            </div>
            <div class="rounded-lg p-2" :class="card.iconClass">
              <component :is="card.icon" :size="20" />
            </div>
          </div>
          <div class="mt-2 flex items-center text-xs">
            <span class="text-slate-500">{{ card.helper }}</span>
          </div>
          </CardContent>
        </Card>
      </div>

      <!-- Job Calendar Section -->
      <Card v-if="!isManagement" class="overflow-hidden">
        <CardHeader class="border-b border-slate-100">
            <div class="flex items-center gap-2">
                <Calendar :size="20" class="text-blue-600" />
                <CardTitle>Kalender Pekerjaan Rutin</CardTitle>
            </div>
            <CardDescription>Jadwal maintenance dan pekerjaan rutin bulanan</CardDescription>
        </CardHeader>
          <CardContent :class="cardPaddingClass">
            <JobCalendar />
        </CardContent>
      </Card>

      <!-- Action Items for Staff -->
      <div v-if="isStaff" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Unconfirmed Tickets -->
        <Card>
          <CardContent :class="cardPaddingClass">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <Tag :size="20" class="text-blue-600" />
                    <CardTitle>Tiket Perlu Konfirmasi</CardTitle>
                </div>
                <Badge variant="info">{{ unconfirmedTickets.length }}</Badge>
            </div>
            
            <div v-if="unconfirmedTickets.length > 0" class="space-y-3">
                <div v-for="ticket in unconfirmedTickets" :key="ticket.id" class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ ticket.code }}</span>
                        <span class="text-xs text-gray-500">{{ new Date(ticket.created_at).toLocaleDateString('id-ID') }}</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">{{ ticket.category?.name || 'Tiket' }}</h4>
                    <div class="flex justify-end mt-2">
                        <RouterLink :to="{ name: 'admin.ticket.detail', params: { id: ticket.id } }" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Lihat Detail <ArrowRight :size="12" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-6 text-gray-500">
                <CheckCircle :size="32" class="mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Tidak ada tiket perlu konfirmasi</p>
            </div>
          </CardContent>
        </Card>

        <!-- Unconfirmed Work Orders -->
        <Card>
          <CardContent :class="cardPaddingClass">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <FileText :size="20" class="text-yellow-600" />
                    <CardTitle>SPK Perlu Konfirmasi</CardTitle>
                </div>
                <Badge variant="warning">{{ unconfirmedWorkOrders.length }}</Badge>
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
            <div v-else class="text-center py-6 text-gray-500">
                <CheckCircle :size="32" class="mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Tidak ada SPK perlu konfirmasi</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Charts Row - Different layout for staff vs admin -->
      <div
        v-if="canViewCharts"
        :class="
          isStaff || isRegularUser
            ? 'grid grid-cols-1 lg:grid-cols-2 gap-4'
            : 'grid grid-cols-1 lg:grid-cols-3 gap-4'
        "
      >
        <!-- Status Distribution Chart -->
        <Card>
          <CardContent :class="cardPaddingClass">
          <div :class="chartHeaderClass">
            <BarChart3 :size="20" class="text-blue-600" />
            <CardTitle>
              {{
                isStaff
                  ? "Distribusi Status Tiket Saya"
                  : "Distribusi Status Tiket"
              }}
            </CardTitle>
          </div>
          <div :class="chartBodyClass">
            <canvas v-if="hasStatusData" id="statusChart"></canvas>
            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
              <BarChart3 :size="48" class="mb-2 opacity-20" />
              <span class="text-sm">Belum ada data status tiket</span>
            </div>
          </div>
          </CardContent>
        </Card>



        <!-- Tickets Per Branch Chart (Hide for Regular User) -->
        <Card v-if="!isRegularUser">
          <CardContent :class="cardPaddingClass">
          <div :class="chartHeaderClass">
            <Building :size="20" class="text-green-600" />
            <CardTitle>
              {{ isStaff ? "Tiket Saya per Cabang" : "Tiket per Cabang" }}
            </CardTitle>
          </div>
          <div :class="chartBodyClass">
            <canvas v-if="hasBranchData" id="branchChart"></canvas>
            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
              <Building :size="48" class="mb-2 opacity-20" />
              <span class="text-sm">Belum ada data cabang</span>
            </div>
          </div>
          </CardContent>
        </Card>

        <!-- Recent Tickets for Regular User -->
        <Card v-if="isRegularUser">
          <CardContent :class="cardPaddingClass">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <Clock :size="20" class="text-blue-600" />
                    <CardTitle>Tiket Tercepat Saya</CardTitle>
                </div>
                <RouterLink :to="{ name: 'admin.tickets' }" class="text-sm text-blue-600 hover:text-blue-700">Lihat Semua</RouterLink>
            </div>
            
            <div v-if="userRecentTickets.length > 0" class="space-y-3">
                <div v-for="ticket in userRecentTickets" :key="ticket.id" class="p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <Badge :variant="ticketStatusVariant(ticket.status)">
                            {{ ticket.status.replace('_', ' ').toUpperCase() }}
                        </Badge>
                        <span class="text-xs text-gray-500">{{ new Date(ticket.created_at).toLocaleDateString('id-ID') }}</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900 mb-1 truncate">{{ ticket.category?.name || 'Tiket' }}</h4>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-xs text-gray-500">{{ ticket.ticket_number }}</span>
                        <RouterLink :to="{ name: 'admin.ticket.detail', params: { id: ticket.id } }" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            Detail <ArrowRight :size="12" />
                        </RouterLink>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-6 text-gray-500">
                <CheckCircle :size="32" class="mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Anda belum membuat tiket</p>
            </div>
          </CardContent>
        </Card>

        <!-- Top Staff Resolved + Staff Tercepat - Only for Admin -->
        <div
          v-if="canViewStaffRankings"
        >
          <Card>
            <CardContent :class="cardPaddingClass">
          <div :class="chartHeaderClass">
            <Award :size="20" class="text-yellow-600" />
            <CardTitle>
              Top 5 Staff Resolved
            </CardTitle>
          </div>
          <div class="space-y-2">
            <div
              v-for="(staff, index) in topStaffResolved"
              :key="staff.staff_name"
              class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-7 h-7 bg-blue-100 rounded-full flex items-center justify-center text-xs font-bold text-blue-600"
                >
                  {{ index + 1 }}
                </div>
                <span class="text-sm font-medium text-gray-800">{{
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

          <!-- Staff Tercepat (inline) -->
          <div class="mt-5 pt-4 border-t border-gray-100">
            <div :class="chartHeaderClass">
              <Activity :size="20" class="text-green-600" />
              <CardTitle>Staff Tercepat</CardTitle>
            </div>
            <div class="space-y-2">
              <div
                v-for="(staff, index) in fastestStaff"
                :key="staff.staff_name"
                class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-7 h-7 bg-green-100 rounded-full flex items-center justify-center text-xs font-bold text-green-600"
                  >
                    {{ index + 1 }}
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-800">{{
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
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Trends Row -->
      <div
        v-if="canViewTrends"
        :class="
          isStaff || isRegularUser
            ? 'grid grid-cols-1 lg:grid-cols-2 gap-4'
            : 'grid grid-cols-1 lg:grid-cols-2 gap-4'
        "
      >
        <!-- Tickets Trend -->
        <Card>
          <CardContent :class="cardPaddingClass">
          <div :class="chartHeaderClass">
            <TrendingUp :size="20" class="text-blue-600" />
            <CardTitle>

              {{ isStaff || isRegularUser ? "Trend Tiket Saya" : "Trend Tiket" }}
              {{ selectedPeriod === "day" ? "Harian" : "Mingguan" }}
            </CardTitle>
          </div>
          <div :class="chartBodyClass">
            <canvas v-if="ticketsTrend.length > 0" id="ticketsTrendChart"></canvas>
            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                <TrendingUp :size="48" class="mb-2 opacity-20" />
                <span class="text-sm">Belum ada data trend tiket</span>
            </div>
          </div>
          </CardContent>
        </Card>

        <!-- Staff Reports Trend (Hide for Regular User) -->
        <Card v-if="!isRegularUser">
          <CardContent :class="cardPaddingClass">
          <div :class="chartHeaderClass">
            <Calendar :size="20" class="text-purple-600" />
            <CardTitle>
              {{ isStaff ? "Trend Laporan Saya" : "Trend Laporan Staff" }}
              {{ selectedPeriod === "day" ? "Harian" : "Mingguan" }}
            </CardTitle>
          </div>
          <div :class="chartBodyClass">
            <canvas v-if="staffReportsTrend.length > 0" id="reportsTrendChart"></canvas>
            <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                <Calendar :size="48" class="mb-2 opacity-20" />
                <span class="text-sm">Belum ada data laporan</span>
            </div>
          </div>
          </CardContent>
        </Card>
      </div>

      <!-- Inactive Users Widget -->
      <div
        v-if="can('user-activity-list') && isSuperAdmin && inactiveUsers.length > 0"
        class="grid grid-cols-1 lg:grid-cols-2 gap-4"
      >
        <Card>
          <CardContent :class="cardPaddingClass">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <Users :size="20" class="text-red-500" />
              <CardTitle>User Tidak Aktif (30 Hari+)</CardTitle>
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
          </CardContent>
        </Card>
      </div>

      <!-- Top Outlet Usage Section - Only for Admin/SuperAdmin -->
      <TopOutletUsage
        v-if="isManagement"
        :data="topOutletUsage"
        :loading="topOutletUsageLoading"
        :error="topOutletUsageError"
      />
    </div>
  </div>
</template>
