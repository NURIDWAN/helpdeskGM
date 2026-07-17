<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const props = defineProps({
  title: {
    type: String,
    default: "",
  },
});

const route = useRoute();
const isLoginPage = computed(() => route.name === "login");
const subtitle = computed(() => props.title || "Buat akun baru");
</script>
<template>
  <div
    class="flex min-h-svh items-center justify-center bg-muted p-6 md:p-10"
  >
    <div class="flex w-full max-w-sm flex-col gap-6">
      <div class="flex flex-col items-center gap-2 text-center">
        <RouterLink
          :to="{ name: 'login' }"
          class="flex items-center gap-2 font-medium text-slate-950"
        >
          <img src="/logo.png" alt="GA Maintenance" class="h-10 w-10 rounded" />
          GA Maintenance
        </RouterLink>
        <p v-if="!isLoginPage" class="text-sm text-muted-foreground">
          {{ subtitle }}
        </p>
      </div>

      <router-view v-if="isLoginPage"></router-view>

      <div v-else class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <router-view></router-view>
      </div>
    </div>
  </div>
</template>
