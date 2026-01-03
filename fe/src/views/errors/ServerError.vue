<template>
  <ErrorPage
    code="500"
    title="Kesalahan Server"
    message="Maaf, terjadi kesalahan pada server. Tim kami sedang bekerja untuk memperbaikinya. Silakan coba lagi nanti."
  >
    <template #illustration>
      <div class="illustration-500">
        <div class="server-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
            <line x1="6" y1="6" x2="6.01" y2="6"></line>
            <line x1="6" y1="18" x2="6.01" y2="18"></line>
          </svg>
        </div>
        <div class="error-sparks">
          <span class="spark"></span>
          <span class="spark"></span>
          <span class="spark"></span>
        </div>
      </div>
    </template>
  </ErrorPage>
</template>

<script setup>
import ErrorPage from './ErrorPage.vue'
</script>

<style scoped>
.illustration-500 {
  position: relative;
  padding: 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.server-icon {
  color: #f59e0b;
  animation: glitch 1s ease-in-out infinite;
}

@keyframes glitch {
  0%, 100% { 
    transform: translate(0);
    filter: none;
  }
  20% { 
    transform: translate(-2px, 2px);
    filter: hue-rotate(90deg);
  }
  40% { 
    transform: translate(-2px, -2px);
  }
  60% { 
    transform: translate(2px, 2px);
    filter: hue-rotate(-90deg);
  }
  80% { 
    transform: translate(2px, -2px);
  }
}

.error-sparks {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100px;
  height: 100px;
  pointer-events: none;
}

.spark {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #f59e0b;
  border-radius: 50%;
  animation: spark 1.5s ease-in-out infinite;
}

.spark:nth-child(1) {
  top: 20%;
  left: 10%;
  animation-delay: 0s;
}

.spark:nth-child(2) {
  top: 50%;
  right: 10%;
  animation-delay: 0.5s;
}

.spark:nth-child(3) {
  bottom: 20%;
  left: 50%;
  animation-delay: 1s;
}

@keyframes spark {
  0%, 100% {
    opacity: 0;
    transform: scale(0);
  }
  50% {
    opacity: 1;
    transform: scale(2);
    box-shadow: 0 0 10px #f59e0b, 0 0 20px #f59e0b;
  }
}
</style>
