<template>
  <ErrorPage
    code="403"
    title="Akses Ditolak"
    message="Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator jika Anda merasa ini adalah kesalahan."
  >
    <template #illustration>
      <div class="illustration-403">
        <div class="lock-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            <circle cx="12" cy="16" r="1"></circle>
          </svg>
        </div>
        <div class="shield">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <path d="M9 12l2 2 4-4"></path>
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
.illustration-403 {
  position: relative;
  padding: 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.lock-icon {
  color: #ef4444;
  animation: shake 2s ease-in-out infinite;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.shield {
  position: absolute;
  top: 0;
  right: 25%;
  color: #667eea;
  opacity: 0.6;
  animation: pulse-shield 2s ease-in-out infinite;
}

@keyframes pulse-shield {
  0%, 100% { 
    transform: scale(1);
    opacity: 0.6;
  }
  50% { 
    transform: scale(1.1);
    opacity: 1;
  }
}
</style>
