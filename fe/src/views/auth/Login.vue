<script setup>
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";
import { storeToRefs } from "pinia";
import { ref } from "vue";
import { Eye, EyeOff, Loader2, Mail } from "lucide-vue-next";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

const authStore = useAuthStore();
const toast = useToastStore();
const { loading, error } = storeToRefs(authStore);
const { login } = authStore;

const form = ref({
  email: null,
  password: null,
});

const showPassword = ref(false);

const handleSubmit = async () => {
  await login(form.value);

  if (error.value) {
    form.value.password = null;
    toast.error("Email atau password salah");
  }
};

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};
</script>

<template>
  <div class="flex flex-col gap-6">
    <Card>
      <CardHeader class="text-center">
        <CardTitle class="text-xl">Masuk ke akun Anda</CardTitle>
        <CardDescription>
          Gunakan email perusahaan untuk mengakses Helpdesk.
        </CardDescription>
      </CardHeader>

      <CardContent>
        <form class="grid gap-6" @submit.prevent="handleSubmit">
          <div class="grid gap-2">
            <label for="email" class="text-sm font-medium leading-none text-slate-950">
              Email
            </label>
            <div class="relative">
              <Input
                id="email"
                v-model="form.email"
                type="email"
                name="email"
                required
                autocomplete="email"
                placeholder="nama@perusahaan.com"
                class="pr-10"
              />
              <Mail
                :size="16"
                class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
              />
            </div>
          </div>

          <div class="grid gap-2">
            <div class="flex items-center">
              <label
                for="password"
                class="text-sm font-medium leading-none text-slate-950"
              >
                Password
              </label>
              <a
                href="#"
                class="ml-auto text-sm text-primary underline-offset-4 hover:underline"
              >
                Lupa password?
              </a>
            </div>
            <div class="relative">
              <Input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Masukkan password"
                class="pr-10"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                :aria-label="
                  showPassword ? 'Sembunyikan password' : 'Tampilkan password'
                "
                @click="togglePassword"
              >
                <Eye v-if="!showPassword" :size="16" />
                <EyeOff v-else :size="16" />
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between gap-3">
            <label for="remember" class="flex items-center gap-2 text-sm text-slate-700">
              <input
                id="remember"
                type="checkbox"
                name="remember"
                class="size-4 rounded border-slate-300 text-primary focus:ring-primary"
              />
              Ingat saya
            </label>
          </div>

          <Button type="submit" class="w-full" :disabled="loading">
            <Loader2 v-if="loading" :size="16" class="animate-spin" />
            <span>{{ loading ? "Memproses..." : "Masuk" }}</span>
          </Button>
        </form>
      </CardContent>
    </Card>

    <p class="px-6 text-center text-xs text-muted-foreground">
      Dengan masuk, Anda menyetujui penggunaan sistem Helpdesk GA Maintenance
      sesuai kebijakan perusahaan.
    </p>
  </div>
</template>
