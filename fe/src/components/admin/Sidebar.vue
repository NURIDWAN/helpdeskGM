<script setup>
import { computed, ref } from "vue";
import { useRoute } from "vue-router";
import { storeToRefs } from "pinia";
import {
  Activity,
  BarChart3,
  Building,
  Calendar,
  ChevronDown,
  ClipboardList,
  FileSpreadsheet,
  FileText,
  LogOut,
  MessageSquare,
  ScrollText,
  Settings,
  Shield,
  Tag,
  Users,
  X,
} from "lucide-vue-next";
import { can } from "@/helpers/permissionHelper";
import { useAuthStore } from "@/stores/auth";
import { Avatar, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
  Sidebar as SidebarRoot,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar";

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  isCollapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close"]);
const route = useRoute();
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);
const { logout } = authStore;

const manuallyOpenGroups = ref({
  masterData: false,
  operations: false,
  management: false,
});

const dashboardItem = {
  label: "Dashboard",
  icon: BarChart3,
  to: { name: "admin.dashboard" },
  isActive: () => route.name === "admin.dashboard",
};

const menuGroups = computed(() => [
  {
    key: "masterData",
    label: "Data Master",
    triggerLabel: "Konfigurasi",
    icon: Settings,
    canShow: () =>
      can("branch-menu") ||
      can("job-template-menu") ||
      can("whatsapp-setting-menu") ||
      can("ticket-category-menu"),
    isActive: () =>
      route.name?.startsWith("admin.branch") ||
      route.name?.startsWith("admin.job-template") ||
      route.name?.startsWith("admin.ticket-categor") ||
      route.name?.startsWith("admin.whatsapp"),
    items: [
      {
        label: "Data Cabang",
        icon: Building,
        to: { name: "admin.branches" },
        canShow: () => can("branch-menu"),
        isActive: () => route.name?.startsWith("admin.branch"),
      },
      {
        label: "Template Job",
        icon: Tag,
        to: { name: "admin.job-templates" },
        canShow: () => can("job-template-menu"),
        isActive: () => route.name?.startsWith("admin.job-template"),
      },
      {
        label: "Kategori Tiket",
        icon: Tag,
        to: { name: "admin.ticket-categories" },
        canShow: () => can("ticket-category-menu"),
        isActive: () => route.name?.startsWith("admin.ticket-categor"),
      },
      {
        label: "Pengaturan WhatsApp",
        icon: MessageSquare,
        to: { name: "admin.whatsapp-settings" },
        canShow: () => can("whatsapp-setting-menu"),
        isActive: () => route.name?.startsWith("admin.whatsapp"),
      },
    ],
  },
  {
    key: "operations",
    label: "Operasional",
    triggerLabel: "Manajemen Kerja",
    icon: Activity,
    canShow: () =>
      can("ticket-menu") ||
      can("form-permintaan-view-all") ||
      can("work-order-menu") ||
      can("work-report-menu") ||
      can("daily-record-menu"),
    isActive: () =>
      route.name?.startsWith("admin.ticket") ||
      route.name?.startsWith("admin.form-permintaan") ||
      route.name?.startsWith("admin.workorder") ||
      route.name?.startsWith("admin.workreport") ||
      route.name?.startsWith("admin.daily-record") ||
      route.name === "admin.daily-usage-report",
    items: [
      {
        label: "Data Tiket",
        icon: Tag,
        to: { name: "admin.tickets" },
        canShow: () => can("ticket-menu"),
        isActive: () => route.name?.startsWith("admin.ticket"),
      },
      {
        label: "Form Permintaan",
        icon: ScrollText,
        to: { name: "admin.form-permintaan" },
        canShow: () => can("form-permintaan-view-all"),
        isActive: () => route.name?.startsWith("admin.form-permintaan"),
      },
      {
        label: "Surat Perintah Kerja",
        icon: ClipboardList,
        to: { name: "admin.workorders" },
        canShow: () => can("work-order-menu"),
        isActive: () => route.name?.startsWith("admin.workorder"),
      },
      {
        label: "Laporan Pekerjaan",
        icon: FileText,
        to: { name: "admin.workreports" },
        canShow: () => can("work-report-menu"),
        isActive: () => route.name?.startsWith("admin.workreport"),
      },
      {
        label: "Laporan Harian Cabang",
        icon: Calendar,
        to: { name: "admin.daily-records" },
        canShow: () => can("daily-record-menu"),
        isActive: () =>
          route.name?.startsWith("admin.daily-record") &&
          route.name !== "admin.daily-usage-report",
      },
      {
        label: "Laporan Daily Usage",
        icon: FileSpreadsheet,
        to: { name: "admin.daily-usage-report" },
        canShow: () => can("daily-record-menu"),
        isActive: () => route.name === "admin.daily-usage-report",
      },
    ],
  },
  {
    key: "management",
    label: "Manajemen",
    triggerLabel: "Pengguna",
    icon: Users,
    canShow: () =>
      can("user-menu") ||
      can("role-menu") ||
      can("user-activity-menu") ||
      can("activity-log-menu"),
    isActive: () =>
      route.name?.startsWith("admin.user") ||
      route.name?.startsWith("admin.role") ||
      route.name?.startsWith("admin.activity-log"),
    items: [
      {
        label: "Data User",
        icon: Users,
        to: { name: "admin.users" },
        canShow: () => can("user-menu"),
        isActive: () =>
          route.name === "admin.users" || route.name?.startsWith("admin.user."),
      },
      {
        label: "Data Role",
        icon: Shield,
        to: { name: "admin.roles" },
        canShow: () => can("role-menu"),
        isActive: () => route.name?.startsWith("admin.role"),
      },
      {
        label: "Monitoring Aktivitas",
        icon: Activity,
        to: { name: "admin.user-activity" },
        canShow: () => can("user-activity-menu"),
        isActive: () => route.name === "admin.user-activity",
      },
      {
        label: "Activity Log",
        icon: ScrollText,
        to: { name: "admin.activity-logs" },
        canShow: () => can("activity-log-menu"),
        isActive: () => route.name === "admin.activity-logs",
      },
    ],
  },
]);

const visibleGroups = computed(() =>
  menuGroups.value
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => item.canShow()),
    }))
    .filter((group) => group.canShow() && group.items.length > 0)
);

const userRoleLabel = computed(() => {
  if (Array.isArray(user.value?.roles) && user.value.roles.length > 0) {
    return user.value.roles[0];
  }

  if (typeof user.value?.role === "string") {
    return user.value.role;
  }

  return user.value?.role?.name || user.value?.type || "Admin";
});

const avatarUrl = computed(
  () =>
    `https://ui-avatars.com/api/?name=${user.value?.name || "Admin"}&background=0D8ABC&color=fff`
);

const closeSidebar = () => {
  emit("close");
};

const toggleGroup = (key) => {
  manuallyOpenGroups.value[key] = !manuallyOpenGroups.value[key];
};

const isGroupOpen = (group) => {
  return props.isCollapsed || manuallyOpenGroups.value[group.key] || group.isActive();
};

const handleLogout = async () => {
  await logout();
};
</script>

<template>
  <SidebarRoot class="sidebar-container">
    <div class="flex justify-end border-b border-sidebar-border p-4 lg:hidden">
      <Button
        type="button"
        variant="ghost"
        size="icon"
        class="text-sidebar-foreground/70"
        aria-label="Tutup sidebar"
        @click="closeSidebar"
      >
        <X :size="24" />
      </Button>
    </div>

    <SidebarHeader :class="isCollapsed ? 'lg:px-2' : ''">
      <div
        class="flex h-10 items-center gap-2"
        :class="isCollapsed ? 'lg:justify-center' : 'lg:justify-start'"
      >
        <RouterLink
          :to="{ name: 'admin.dashboard' }"
          class="flex min-w-0 items-center gap-2.5 rounded-md"
          :class="{ 'lg:justify-center': isCollapsed }"
          title="GA Maintenance"
          @click="closeSidebar"
        >
          <img src="/logo.png" alt="GA Maintenance" class="h-8 w-8 shrink-0 rounded" />
          <span
            class="truncate text-base font-bold text-sidebar-primary"
            :class="{ 'lg:hidden': isCollapsed }"
          >
            GA Maintenance
          </span>
        </RouterLink>

      </div>
    </SidebarHeader>

    <SidebarContent>
      <SidebarMenu>
        <SidebarMenuItem>
          <RouterLink
            :to="dashboardItem.to"
            custom
            v-slot="{ href, navigate }"
          >
            <SidebarMenuButton
              as="a"
              :href="href"
              :is-active="dashboardItem.isActive()"
              :title="dashboardItem.label"
              @click="
                navigate($event);
                closeSidebar();
              "
            >
              <component :is="dashboardItem.icon" :size="18" />
              <span :class="{ 'lg:hidden': isCollapsed }">
                {{ dashboardItem.label }}
              </span>
            </SidebarMenuButton>
          </RouterLink>
        </SidebarMenuItem>
      </SidebarMenu>

      <SidebarGroup v-for="group in visibleGroups" :key="group.key">
        <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton
                :is-active="group.isActive()"
                :title="group.triggerLabel"
                @click="toggleGroup(group.key)"
              >
                <component :is="group.icon" :size="18" />
                <span :class="{ 'lg:hidden': isCollapsed }">
                  {{ group.triggerLabel }}
                </span>
                <ChevronDown
                  :size="16"
                  class="ml-auto transition-transform duration-200"
                  :class="[
                    { 'rotate-180': isGroupOpen(group) },
                    { 'lg:hidden': isCollapsed },
                  ]"
                />
              </SidebarMenuButton>

              <SidebarMenuSub v-show="isGroupOpen(group)">
                <SidebarMenuSubItem v-for="item in group.items" :key="item.label">
                  <RouterLink
                    :to="item.to"
                    custom
                    v-slot="{ href, navigate }"
                  >
                    <SidebarMenuSubButton
                      as="a"
                      :href="href"
                      :is-active="item.isActive()"
                      :title="item.label"
                      @click="
                        navigate($event);
                        closeSidebar();
                      "
                    >
                      <component :is="item.icon" :size="17" />
                      <span :class="{ 'lg:hidden': isCollapsed }">
                        {{ item.label }}
                      </span>
                    </SidebarMenuSubButton>
                  </RouterLink>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter :class="isCollapsed ? 'lg:px-2' : ''">
      <div v-if="isCollapsed" class="hidden justify-center lg:flex">
        <RouterLink
          :to="{ name: 'admin.profile' }"
          title="Profil"
          class="rounded-full ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:ring-offset-2"
          @click="closeSidebar"
        >
          <Avatar class="size-9">
            <AvatarImage :src="avatarUrl" :alt="user?.name || 'Admin'" />
          </Avatar>
        </RouterLink>
      </div>

      <div
        class="flex items-center gap-3 rounded-lg bg-sidebar-accent/70 p-2"
        :class="{ 'lg:hidden': isCollapsed }"
      >
        <RouterLink
          :to="{ name: 'admin.profile' }"
          class="shrink-0 rounded-full"
          title="Profil"
          @click="closeSidebar"
        >
          <Avatar class="size-9">
            <AvatarImage :src="avatarUrl" :alt="user?.name || 'Admin'" />
          </Avatar>
        </RouterLink>

        <RouterLink
          :to="{ name: 'admin.profile' }"
          class="min-w-0 flex-1"
          @click="closeSidebar"
        >
          <span class="block truncate text-sm font-semibold leading-5 text-sidebar-foreground">
            {{ user?.name || "Admin" }}
          </span>
          <span class="block truncate text-xs leading-5 text-sidebar-foreground/60">
            {{ userRoleLabel }}
          </span>
        </RouterLink>

        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="size-8 shrink-0 text-sidebar-foreground/70 hover:bg-sidebar hover:text-red-600"
          title="Keluar"
          aria-label="Keluar"
          @click.prevent="handleLogout"
        >
          <LogOut :size="16" />
        </Button>
      </div>
    </SidebarFooter>
  </SidebarRoot>
</template>
