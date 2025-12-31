<script setup>
import { useToastStore } from "@/stores/toast";
import { storeToRefs } from "pinia";
import { CheckCircle, XCircle, AlertTriangle, Info, X } from "lucide-vue-next";

const toastStore = useToastStore();
const { toasts } = storeToRefs(toastStore);
const { remove } = toastStore;

const getIcon = (type) => {
    const icons = {
        success: CheckCircle,
        error: XCircle,
        warning: AlertTriangle,
        info: Info,
    };
    return icons[type] || Info;
};

const getClasses = (type) => {
    const classes = {
        success: "bg-white border-green-500 text-green-700 shadow-lg shadow-green-100",
        error: "bg-white border-red-500 text-red-700 shadow-lg shadow-red-100",
        warning: "bg-white border-yellow-500 text-yellow-700 shadow-lg shadow-yellow-100",
        info: "bg-white border-blue-500 text-blue-700 shadow-lg shadow-blue-100",
    };
    return classes[type] || classes.info;
};

const getIconClasses = (type) => {
    const classes = {
        success: "text-green-500",
        error: "text-red-500",
        warning: "text-yellow-500",
        info: "text-blue-500",
    };
    return classes[type] || classes.info;
};
</script>

<template>
    <div
        class="fixed top-4 right-4 z-50 flex flex-col gap-3 pointer-events-none w-full max-w-sm"
    >
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="['pointer-events-auto flex items-start gap-3 p-4 rounded-lg border-l-4 shadow-md bg-white min-w-[300px]', getClasses(toast.type)]"
            >
                <component
                    :is="getIcon(toast.type)"
                    :size="20"
                    :class="['flex-shrink-0 mt-0.5', getIconClasses(toast.type)]"
                />
                
                <div class="flex-1 text-sm font-medium text-gray-800">
                    {{ toast.message }}
                </div>

                <button
                    @click="remove(toast.id)"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <X :size="16" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
