<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm"
        @click.self="$emit('cancel')"
      >
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="translate-y-2 scale-95 opacity-0"
            enter-to-class="translate-y-0 scale-100 opacity-100"
            leave-active-class="transition-all duration-150"
            leave-from-class="translate-y-0 scale-100 opacity-100"
            leave-to-class="translate-y-2 scale-95 opacity-0"
          >
            <div
              v-if="show"
              class="w-full max-w-md overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl"
              @click.stop
            >
              <!-- Header -->
              <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-950">
                  Konfirmasi Penonaktifan Permission
                </h3>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  class="size-8 text-slate-500 hover:text-slate-900"
                  aria-label="Tutup dialog"
                  @click="$emit('cancel')"
                >
                  <X :size="18" />
                </Button>
              </div>

              <!-- Body -->
              <div class="px-5 py-5">
                <div class="flex gap-4">
                  <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-50">
                    <AlertTriangle :size="20" class="text-amber-600" />
                  </div>

                  <div class="min-w-0 flex-1">
                    <p class="text-sm leading-6 text-slate-700">
                      Menonaktifkan permission
                      <span class="font-medium text-slate-900">{{ displayPermissionName }}</span>
                      akan turut menonaktifkan permission berikut yang bergantung padanya:
                    </p>

                    <!-- Daftar permission yang terpengaruh -->
                    <ul class="mt-3 space-y-1.5">
                      <li
                        v-for="dep in dependents"
                        :key="dep"
                        class="flex items-center gap-2 text-sm text-slate-600"
                      >
                        <span class="size-1.5 shrink-0 rounded-full bg-amber-400"></span>
                        <span>{{ getPermissionLabel(dep) }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                <Button
                  type="button"
                  variant="outline"
                  @click="$emit('cancel')"
                >
                  Batal
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  @click="$emit('confirm')"
                >
                  Ya, Lanjutkan
                </Button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from "vue";
import { AlertTriangle, X } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  permissionName: {
    type: String,
    default: "",
  },
  dependents: {
    type: Array,
    default: () => [],
  },
  permissionLabels: {
    type: Object,
    default: () => ({}),
  },
});

defineEmits(["confirm", "cancel"]);

const displayPermissionName = computed(() => {
  return props.permissionLabels[props.permissionName] || props.permissionName;
});

function getPermissionLabel(permName) {
  return props.permissionLabels[permName] || permName;
}
</script>
