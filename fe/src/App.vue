<script setup>
import { RouterLink, RouterView } from 'vue-router'
import ToastContainer from "@/components/common/ToastContainer.vue";
import PwaInstallPrompt from "@/components/app/PwaInstallPrompt.vue";
import PwaUpdatePrompt from "@/components/app/PwaUpdatePrompt.vue";
</script>

<template>
  <RouterView />
  <ToastContainer />
  <PwaInstallPrompt />
  <PwaUpdatePrompt />
</template>
