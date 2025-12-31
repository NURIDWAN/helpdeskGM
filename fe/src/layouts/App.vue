<script setup>
import Navbar from "@/components/app/Navbar.vue";

// inject bg-gray-50 to body
document.body.classList.add("bg-gray-50");
</script>

<template>
  <Navbar />

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-white mt-5">
    <router-view></router-view>
  </main>
</template>
