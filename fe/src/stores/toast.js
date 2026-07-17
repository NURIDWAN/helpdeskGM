import { defineStore } from "pinia";
import { toast as sonnerToast } from "vue-sonner";

const normalizeOptions = (durationOrOptions) => {
  if (typeof durationOrOptions === "number") {
    return { duration: durationOrOptions };
  }

  if (durationOrOptions && typeof durationOrOptions === "object") {
    return durationOrOptions;
  }

  return {};
};

export const useToastStore = defineStore("toast", () => {
  const add = (message, type = "success", durationOrOptions = 3000) => {
    const options = normalizeOptions(durationOrOptions);

    const toastByType = {
      success: sonnerToast.success,
      error: sonnerToast.error,
      warning: sonnerToast.warning,
      info: sonnerToast.info,
    };

    return (toastByType[type] || sonnerToast)(message, options);
  };

  const remove = (id) => sonnerToast.dismiss(id);
  const success = (message, durationOrOptions = 3000) =>
    add(message, "success", durationOrOptions);
  const error = (message, durationOrOptions = 5000) =>
    add(message, "error", durationOrOptions);
  const warning = (message, durationOrOptions = 4000) =>
    add(message, "warning", durationOrOptions);
  const info = (message, durationOrOptions = 3000) =>
    add(message, "info", durationOrOptions);

  return {
    add,
    remove,
    success,
    error,
    warning,
    info,
  };
});
