import { defineStore } from "pinia";
import { ref } from "vue";

export const useToastStore = defineStore("toast", () => {
    const toasts = ref([]);
    const counter = ref(0);

    const add = (message, type = "success", duration = 3000) => {
        const id = counter.value++;
        toasts.value.push({
            id,
            message,
            type,
            duration,
        });

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }
    };

    const remove = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (message, duration = 3000) => add(message, "success", duration);
    const error = (message, duration = 5000) => add(message, "error", duration); // Default longer for errors
    const warning = (message, duration = 4000) => add(message, "warning", duration);
    const info = (message, duration = 3000) => add(message, "info", duration);

    return {
        toasts,
        add,
        remove,
        success,
        error,
        warning,
        info,
    };
});
