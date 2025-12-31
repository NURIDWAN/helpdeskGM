<script setup>
import { onMounted, ref, computed } from "vue";
import { Chart } from "chart.js/auto";
import { useDashboardStore } from "@/stores/dashboard";
import { useAuthStore } from "@/stores/auth";
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
} from "lucide-vue-next";

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
  loading,
} = storeToRefs(dashboardStore);

// Computed properties for user role
const isStaff = computed(() => authStore.user?.roles?.includes("staff"));
const isAdmin = computed(() => authStore.user?.roles?.includes("admin"));

const selectedPeriod = ref("day");
const statusChart = ref(null);
const branchChart = ref(null);
const ticketsTrendChart = ref(null);
const reportsTrendChart = ref(null);

const periodOptions = [
  { value: "day", label: "Harian" },
  { value: "week", label: "Mingguan" },
];

const loadDashboardData = async () => {
  await dashboardStore.fetchAllData(selectedPeriod.value);
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
              : "Overview sistem GA Maintenance dan laporan kerja"
          }}
        </p>
      </div>
      <div class="flex items-center gap-3">
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

        <!-- Active Work Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
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

      <!-- Charts Row - Different layout for staff vs admin -->
      <div
        v-if="can('dashboard-view-charts')"
        :class="
          isStaff
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

        <!-- Tickets Per Branch Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
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

      <!-- Trends Row - Compact layout for staff -->
      <div
        v-if="can('dashboard-view-trends')"
        :class="
          isStaff
            ? 'grid grid-cols-1 lg:grid-cols-2 gap-6'
            : 'grid grid-cols-1 lg:grid-cols-2 gap-6'
        "
      >
        <!-- Tickets Trend -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <TrendingUp :size="20" class="text-blue-600" />
            <h3 class="text-lg font-semibold text-gray-800">
              {{ isStaff ? "Trend Tiket Saya" : "Trend Tiket" }}
              {{ selectedPeriod === "day" ? "Harian" : "Mingguan" }}
            </h3>
          </div>
          <div class="h-64">
            <canvas id="ticketsTrendChart"></canvas>
          </div>
        </div>

        <!-- Staff Reports Trend -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <div class="flex items-center gap-2 mb-4">
            <Calendar :size="20" class="text-purple-600" />
            <h3 class="text-lg font-semibold text-gray-800">
              {{ isStaff ? "Trend Laporan Saya" : "Trend Laporan Staff" }}
              {{ selectedPeriod === "day" ? "Harian" : "Mingguan" }}
            </h3>
          </div>
          <div class="h-64">
            <canvas id="reportsTrendChart"></canvas>
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
    </div>
  </div>
</template>
