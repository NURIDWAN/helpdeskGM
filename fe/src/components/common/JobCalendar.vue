<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useJobScheduleStore } from "@/stores/jobSchedule";
import { storeToRefs } from "pinia";
import { DateTime } from "luxon";
import { ChevronLeft, ChevronRight, CheckCircle2, Clock, AlertTriangle } from "lucide-vue-next";

const props = defineProps({
  branchId: {
    type: [Number, String],
    default: null,
  },
});

const jobScheduleStore = useJobScheduleStore();
const { schedules, loading } = storeToRefs(jobScheduleStore);
const { fetchSchedules } = jobScheduleStore;

const currentDate = ref(DateTime.now());
const selectedDate = ref(DateTime.now());

// Calendar Grid Logic
const calendarDays = computed(() => {
  const startOfMonth = currentDate.value.startOf("month");
  const endOfMonth = currentDate.value.endOf("month");
  
  // Start from the beginning of the week (Monday)
  let date = startOfMonth.startOf("week");
  const endDate = endOfMonth.endOf("week");
  
  const days = [];
  while (date <= endDate) {
    days.push(date);
    date = date.plus({ days: 1 });
  }
  return days;
});

const weekDays = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Ming"];

const monthName = computed(() => currentDate.value.setLocale('id').toFormat("MMMM yyyy"));

const changeMonth = (delta) => {
  currentDate.value = currentDate.value.plus({ months: delta });
  loadSchedules();
};

const goToToday = () => {
    currentDate.value = DateTime.now();
    selectedDate.value = DateTime.now();
    loadSchedules();
}

const loadSchedules = async () => {
  await fetchSchedules({
    month: currentDate.value.month,
    year: currentDate.value.year,
    branch_id: props.branchId,
  });
};

const getSchedulesForDate = (date) => {
  const dateStr = date.toFormat("yyyy-MM-dd");
  return schedules.value.filter((s) => s.date === dateStr);
};

const isSameDay = (d1, d2) => d1.hasSame(d2, "day");
const isToday = (date) => date.hasSame(DateTime.now(), "day");
const isCurrentMonth = (date) => date.hasSame(currentDate.value, "month");

const getDayClass = (date) => {
  return {
    "bg-white": isCurrentMonth(date) && !isSameDay(date, selectedDate.value),
    "bg-gray-50 text-gray-400": !isCurrentMonth(date),
    "ring-2 ring-blue-600 z-10": isToday(date),
    "bg-blue-50": isSameDay(date, selectedDate.value),
  };
};

const selectDate = (date) => {
    selectedDate.value = date;
};

// Watch for branch changes
watch(() => props.branchId, () => {
  loadSchedules();
});

onMounted(() => {
  loadSchedules();
});

// Helper for schedule indicators
const getStatusColor = (schedule) => {
    if (schedule.is_completed) return "bg-green-500";
    if (schedule.is_overdue) return "bg-red-500";
    return "bg-amber-500";
};

const selectedDateSchedules = computed(() => getSchedulesForDate(selectedDate.value));

</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-4 flex items-center justify-between border-b border-gray-100">
      <h2 class="text-lg font-semibold text-gray-900">{{ monthName }}</h2>
      <div class="flex items-center gap-2">
        <button 
            @click="goToToday"
            class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition-colors mr-2"
        >
            Hari Ini
        </button>
        <button
          @click="changeMonth(-1)"
          class="p-1 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ChevronLeft :size="20" />
        </button>
        <button
          @click="changeMonth(1)"
          class="p-1 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ChevronRight :size="20" />
        </button>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
        <!-- Calendar Grid -->
        <div class="flex-1 p-4">
            <!-- Weekday Headers -->
            <div class="grid grid-cols-7 mb-2">
                <div 
                    v-for="day in weekDays" 
                    :key="day"
                    class="text-center text-xs font-medium text-gray-500 py-2"
                >
                    {{ day }}
                </div>
            </div>

            <!-- Days Grid -->
            <div class="grid grid-cols-7 gap-1 lg:gap-2">
                <div
                    v-for="date in calendarDays"
                    :key="date.ts"
                    @click="selectDate(date)"
                    :class="getDayClass(date)"
                    class="aspect-square relative rounded-lg p-1 cursor-pointer hover:bg-gray-50 transition-colors flex flex-col items-center justify-start border border-transparent hover:border-gray-200"
                >
                    <!-- Date Number -->
                    <span 
                        class="text-sm font-medium w-7 h-7 flex items-center justify-center rounded-full mb-1"
                        :class="{ 'bg-blue-600 text-white': isSameDay(date, selectedDate), 'text-gray-700': !isSameDay(date, selectedDate) && isCurrentMonth(date) }"
                    >
                        {{ date.day }}
                    </span>

                    <!-- Indicators -->
                    <div class="flex gap-0.5 flex-wrap justify-center w-full px-1">
                         <div 
                            v-for="schedule in getSchedulesForDate(date).slice(0, 4)"
                            :key="schedule.id"
                            class="w-1.5 h-1.5 rounded-full"
                            :class="getStatusColor(schedule)"
                         ></div>
                         <div v-if="getSchedulesForDate(date).length > 4" class="text-[8px] text-gray-400 font-bold">+</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Sidebar -->
        <div class="w-full lg:w-80 bg-gray-50 border-l border-gray-100 p-4 overflow-y-auto max-h-[500px]">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                Jadwal: {{ selectedDate.setLocale('id').toFormat("cccc, d MMMM yyyy") }}
            </h3>
            
            <div v-if="loading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="selectedDateSchedules.length === 0" class="text-center py-8 text-gray-500">
                <p class="text-sm">Tidak ada jadwal pekerjaan hari ini.</p>
            </div>

            <div v-else class="space-y-3">
                <div 
                    v-for="schedule in selectedDateSchedules" 
                    :key="schedule.id"
                    class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm hover:shadow-md transition-shadow"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span 
                            class="px-2 py-0.5 rounded-full text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-700': schedule.is_completed,
                                'bg-amber-100 text-amber-700': !schedule.is_completed && !schedule.is_overdue,
                                'bg-red-100 text-red-700': schedule.is_overdue
                            }"
                        >
                            {{ schedule.is_completed ? 'Selesai' : (schedule.is_overdue ? 'Terlewat' : 'Belum Dikerjakan') }}
                        </span>
                        <span class="text-xs text-gray-400 capitalize">{{ schedule.frequency }}</span>
                    </div>
                    
                    <h4 class="font-medium text-gray-900 text-sm mb-1">{{ schedule.job_template_name }}</h4>
                    <p class="text-xs text-gray-500 mb-2 truncate">{{ schedule.branch_name }}</p>
                    
                    <div v-if="!schedule.is_completed" class="mt-2 pt-2 border-t border-gray-50 flex justify-end">
                       <RouterLink 
                            :to="{ 
                                name: 'admin.workreport.create', 
                                query: { 
                                    job_template_id: schedule.job_template_id,
                                    branch_id: schedule.branch_id
                                } 
                            }"
                            class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                        >
                            Buat Laporan &rarr;
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>
