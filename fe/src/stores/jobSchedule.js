import { defineStore } from "pinia";
import { ref } from "vue";
import { axiosInstance as axios } from "@/plugins/axios"; // Adjust path if necessary
import { DateTime } from "luxon";

export const useJobScheduleStore = defineStore("jobSchedule", () => {
    const schedules = ref([]);
    const todaysSummary = ref({
        total: 0,
        completed: 0,
        pending: 0,
        schedules: []
    });
    const loading = ref(false);
    const error = ref(null);

    const fetchSchedules = async (params) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get("/job-schedules", { params });
            schedules.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || "Failed to fetch schedules";
            console.error(err);
        } finally {
            loading.value = false;
        }
    };

    const fetchTodaysSummary = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get("/job-schedules/today", { params });
            todaysSummary.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || "Failed to fetch summary";
            console.error(err);
        } finally {
            loading.value = false;
        }
    };

    return {
        schedules,
        todaysSummary,
        loading,
        error,
        fetchSchedules,
        fetchTodaysSummary,
    };
});
