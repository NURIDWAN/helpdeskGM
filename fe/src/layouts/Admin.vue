<script setup>
import Sidebar from "@/components/admin/Sidebar.vue";
import { Bell, ChevronDown, Menu, X } from "lucide-vue-next";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";
import { ref, onMounted, onUnmounted } from "vue";

const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const { logout } = authStore;

const showUserMenu = ref(false);
const sidebarOpen = ref(false);

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value;
};

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
};

const closeSidebar = () => {
  sidebarOpen.value = false;
};

const handleLogout = async () => {
  await logout();
};

// Close sidebar when clicking outside on mobile
const handleClickOutside = (event) => {
  // Don't close if clicking on the menu button or sidebar
  if (
    sidebarOpen.value &&
    !event.target.closest(".sidebar-container") &&
    !event.target.closest(".mobile-menu-button")
  ) {
    sidebarOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
  <div class="flex h-screen">
    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
      @click="closeSidebar"
    ></div>

    <!-- Sidebar -->
    <div class="sidebar-container">
      <Sidebar :is-open="sidebarOpen" @close="closeSidebar" />
    </div>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto">
      <!-- Topbar -->
      <div class="bg-white shadow-sm sticky top-0 z-30">
        <div class="flex items-center justify-between px-4 lg:px-6 py-4">
          <!-- Mobile Menu Button -->
          <div class="flex items-center space-x-4">
            <button
              @click="toggleSidebar"
              class="mobile-menu-button lg:hidden p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg"
            >
              <Menu v-if="!sidebarOpen" :size="24" class="pointer-events-none" />
              <X v-else :size="24" class="pointer-events-none" />
            </button>
            <h2 class="text-lg lg:text-xl font-semibold text-gray-800">
              {{ $route.meta.title }}
            </h2>
          </div>

          <div class="flex items-center space-x-2 lg:space-x-4">
            <!-- Notifications -->
            <button
              class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full"
            >
              <Bell :size="20" class="lg:w-6 lg:h-6" />
              <span
                class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"
              ></span>
            </button>

            <!-- User Menu -->
            <div class="relative">
              <button
                @click="toggleUserMenu()"
                class="flex items-center bg-gray-50 px-3 lg:px-4 py-2 rounded-full hover:bg-gray-100"
              >
                <img
                  :src="`https://ui-avatars.com/api/?name=${user?.name}&background=0D8ABC&color=fff`"
                  alt="Profile"
                  class="w-6 h-6 lg:w-8 lg:h-8 rounded-full"
                />
                <span
                  class="ml-2 text-sm font-medium text-gray-700 hidden sm:block"
                  >{{ user?.name }}</span
                >
                <ChevronDown :size="16" class="ml-1 lg:ml-2 text-gray-500" />
              </button>
              <div
                v-if="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
              >
                <RouterLink
                  :to="{ name: 'admin.profile' }"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  @click="showUserMenu = false"
                >
                  Pengaturan
                </RouterLink>
                <div class="border-t border-gray-100 my-1"></div>
                <a
                  href="#"
                  @click="handleLogout"
                  class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                >
                  Keluar
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="p-4 lg:p-6 space-y-6">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>
