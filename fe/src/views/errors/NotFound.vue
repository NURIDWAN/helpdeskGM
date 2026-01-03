<template>
  <ErrorPage
    code="404"
    title="Halaman Tidak Ditemukan"
    message="Maaf, halaman yang Anda cari tidak dapat ditemukan atau telah dipindahkan."
  >
    <template #illustration>
      <div class="illustration-404">
        <div class="search-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="M21 21l-4.35-4.35"></path>
            <path d="M11 8v6M8 11h6" stroke-dasharray="2 2"></path>
          </svg>
        </div>
        <div class="floating-elements">
          <span class="float-item">?</span>
          <span class="float-item">!</span>
          <span class="float-item">×</span>
        </div>
      </div>
    </template>
  </ErrorPage>
</template>

<script setup>
import ErrorPage from './ErrorPage.vue'
</script>

<style scoped>
.illustration-404 {
  position: relative;
  padding: 2rem;
}

.search-icon {
  color: #9ca3af;
  animation: wobble 3s ease-in-out infinite;
}

@keyframes wobble {
  0%, 100% { transform: rotate(-5deg); }
  50% { transform: rotate(5deg); }
}

.floating-elements {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.float-item {
  position: absolute;
  font-size: 1.5rem;
  font-weight: bold;
  color: #667eea;
  opacity: 0.5;
  animation: float 4s ease-in-out infinite;
}

.float-item:nth-child(1) {
  top: 10%;
  left: 20%;
  animation-delay: 0s;
}

.float-item:nth-child(2) {
  top: 20%;
  right: 20%;
  animation-delay: 1s;
}

.float-item:nth-child(3) {
  bottom: 20%;
  left: 30%;
  animation-delay: 2s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
    opacity: 0.5;
  }
  50% {
    transform: translateY(-10px);
    opacity: 0.8;
  }
}
</style>
