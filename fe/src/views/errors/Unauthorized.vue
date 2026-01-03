<template>
  <ErrorPage
    code="401"
    title="Tidak Terotentikasi"
    message="Anda perlu login untuk mengakses halaman ini. Silakan masuk dengan akun Anda."
  >
    <template #illustration>
      <div class="illustration-401">
        <div class="user-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
            <line x1="4" y1="4" x2="20" y2="20" stroke="#ef4444" stroke-width="3"></line>
          </svg>
        </div>
      </div>
    </template>
  </ErrorPage>
</template>

<script setup>
import ErrorPage from './ErrorPage.vue'
</script>

<style scoped>
.illustration-401 {
  padding: 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.user-icon {
  color: #6b7280;
  animation: fade-in-out 2s ease-in-out infinite;
}

@keyframes fade-in-out {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
</style>
