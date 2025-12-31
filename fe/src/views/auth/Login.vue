<script setup>
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";
import { ref } from "vue";
import { Eye, EyeOff, Mail } from "lucide-vue-next";

const authStore = useAuthStore();
const { loading, error } = storeToRefs(authStore);
const { login } = authStore;

const form = ref({
  email: null,
  password: null,
});

const showPassword = ref(false);

const handleSubmit = async () => {
  await login(form.value);

  if (error.value === "Unauthorized") {
    form.value.password = null;
    alert("Email atau password salah");
  }
};

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <!-- Email -->
    <div>
      <label for="email" class="block text-sm font-medium text-gray-700"
        >Email</label
      >
      <div class="mt-1 relative">
        <!-- TODO: Add v-model binding for email -->
        <input
          v-model="form.email"
          type="email"
          id="email"
          name="email"
          required
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          placeholder="nama@perusahaan.com"
        />
        <div
          class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
        >
          <Mail :size="16" class="w-4 h-4 text-gray-400" />
        </div>
      </div>
    </div>

    <!-- Password -->
    <div>
      <label for="password" class="block text-sm font-medium text-gray-700"
        >Password</label
      >
      <div class="mt-1 relative">
        <!-- TODO: Add v-model binding for password -->
        <input
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          id="password"
          name="password"
          required
          class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
          placeholder="••••••••"
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
          <!-- TODO: Add click handler for password toggle -->
          <button
            type="button"
            @click="togglePassword"
            class="text-gray-400 hover:text-gray-600 focus:outline-none"
          >
            <Eye v-if="!showPassword" :size="16" class="w-4 h-4" id="password-toggle" />
            <EyeOff v-else :size="16" class="w-4 h-4" id="password-toggle" />
          </button>
        </div>
      </div>
    </div>

    <!-- Remember Me & Forgot Password -->
    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <input
          type="checkbox"
          id="remember"
          name="remember"
          class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
        />
        <label for="remember" class="ml-2 block text-sm text-gray-700"
          >Ingat saya</label
        >
      </div>
      <a href="#" class="text-sm text-blue-600 hover:text-blue-800"
        >Lupa password?</a
      >
    </div>

    <!-- Submit Button -->
    <div>
      <!-- TODO: Add loading state to button -->
      <button
        type="submit"
        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <span v-if="!loading"> Masuk </span>
        <span v-else> Loading... </span>
      </button>
    </div>
  </form>
</template>
