<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { axiosInstance } from "@/plugins/axios";
import { can } from "@/helpers/permissionHelper";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";
import {
  enableBrowserNotifications,
  fetchBrowserNotifications,
  markBrowserNotificationsSeen,
  startBrowserNotificationPolling,
  stopBrowserNotificationPolling,
} from "@/composables/useBrowserNotifications";
import { Avatar, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Sidebar as SidebarRoot,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarInset,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar";
import { usePwaInstall } from "@/composables/usePwaInstall";
import {
  Activity,
  BarChart3,
  Bell,
  Building,
  Calendar,
  ChevronDown,
  ClipboardList,
  Command,
  Download,
  FileSpreadsheet,
  FileText,
  Home,
  LogOut,
  MessageSquare,
  PanelLeftClose,
  PanelLeftOpen,
  ScrollText,
  Search,
  Settings,
  Shield,
  Tag,
  Users,
  X,
} from "lucide-vue-next";

const props = defineProps({
  mode: {
    type: String,
    default: "app",
    validator: (value) => ["app", "admin"].includes(value),
  },
});

document.body.classList.remove("bg-white");
document.body.classList.add("bg-slate-50");

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToastStore();
const { user } = storeToRefs(authStore);
const { logout } = authStore;

const sidebarOpen = ref(false);
const isSidebarCollapsed = ref(false);

const { isInstallable, isStandalone, promptInstall } = usePwaInstall();

const openGroups = ref({
  appReports: false,
  masterData: false,
  operations: false,
  management: false,
});

const showNotifications = ref(false);
const showSearchModal = ref(false);
const modalSearchInput = ref(null);
const searchQuery = ref("");
const searchResults = ref([]);
const searchLoading = ref(false);
const notificationItems = ref([]);
const notificationLoading = ref(false);
const browserNotificationEnabled = ref(false);
let searchTimer = null;

const isAdminMode = computed(() => props.mode === "admin");
const portalLabel = computed(() => (isAdminMode.value ? "Admin panel" : "User portal"));
const pageTitle = computed(() => route.meta?.title || "Dashboard");
const homeRoute = computed(() => ({
  name: isAdminMode.value ? "admin.dashboard" : "app.dashboard",
}));
const profileRoute = computed(() => ({
  name: isAdminMode.value ? "admin.profile" : "app.profile",
}));
const notificationCount = computed(() => notificationItems.value.length);
const sidebarIconSize = computed(() => (isSidebarCollapsed.value ? 22 : 18));
const sidebarSubIconSize = computed(() => (isSidebarCollapsed.value ? 20 : 17));
const sidebarIconClass = computed(() =>
  isSidebarCollapsed.value ? "lg:size-5.5 shrink-0" : "shrink-0"
);

const userRoleLabel = computed(() => {
  if (Array.isArray(user.value?.roles) && user.value.roles.length > 0) {
    return user.value.roles[0];
  }

  if (typeof user.value?.role === "string") {
    return user.value.role;
  }

  return user.value?.role?.name || user.value?.type || "User";
});

const avatarUrl = computed(
  () =>
    `https://ui-avatars.com/api/?name=${user.value?.name || "User"}&background=0D8ABC&color=fff`
);

const ticketTitle = (ticket) =>
  ticket.category?.name || ticket.title || ticket.subject || "Tiket";

const formPermintaanTitle = (form) => form.request_number || "Form Permintaan";

const statusLabel = (status) => {
  const labels = {
    open: "Menunggu",
    in_progress: "Diproses",
    resolved: "Selesai",
    closed: "Ditutup",
    pending: "Pending",
    approved: "Approved",
  };

  return labels[status] || status || "-";
};

const priorityLabel = (priority) => {
  const labels = {
    low: "Rendah",
    medium: "Sedang",
    high: "Tinggi",
    urgent: "Urgent",
  };

  return labels[priority] || priority || "-";
};

const statusBadgeVariant = (status) => {
  if (status === "open" || status === "pending") return "warning";
  if (status === "in_progress") return "info";
  if (status === "resolved" || status === "approved") return "success";
  return "muted";
};

const priorityBadgeVariant = (priority) => {
  if (priority === "urgent" || priority === "high") return "destructive";
  if (priority === "medium") return "warning";
  return "success";
};

const stripHtml = (html) => {
  const element = document.createElement("div");
  element.innerHTML = html || "";
  return element.textContent?.trim() || "";
};

const formatDate = (date) => {
  if (!date) return "-";

  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(new Date(date));
};

const visibleItems = (items) =>
  items.filter((item) => !item.canShow || item.canShow());

const appGroups = computed(() => {
  const mainItems = visibleItems([
    {
      label: "Dashboard",
      icon: Home,
      to: { name: "app.dashboard" },
      isActive: () => route.name === "app.dashboard",
    },
    {
      label: "Tiket",
      icon: Tag,
      to: { name: "app.tickets" },
      isActive: () => route.name?.startsWith("app.ticket"),
    },
    {
      label: "Form Permintaan",
      icon: ClipboardList,
      to: { name: "app.form-permintaan" },
      canShow: () => can("form-permintaan-list"),
      isActive: () => route.name?.startsWith("app.form-permintaan"),
    },
  ]);

  const reportItems = visibleItems([
    {
      label: "Laporan Harian Cabang",
      icon: FileText,
      to: { name: "app.daily-records" },
      isActive: () => route.name?.startsWith("app.daily-record"),
    },
    {
      label: "Laporan Daily Usage",
      icon: FileSpreadsheet,
      to: { name: "app.daily-usage-report" },
      isActive: () => route.name === "app.daily-usage-report",
    },
  ]);

  return [
    {
      key: "appMain",
      type: "items",
      items: mainItems,
    },
    {
      key: "appReports",
      type: "group",
      label: "Laporan",
      triggerLabel: "Laporan",
      icon: BarChart3,
      items: reportItems,
      isActive: () => reportItems.some((item) => item.isActive()),
    },
  ].filter((group) => group.items.length > 0);
});

const adminGroups = computed(() => {
  const groups = [
    {
      key: "adminDashboard",
      type: "items",
      items: [
        {
          label: "Dashboard",
          icon: BarChart3,
          to: { name: "admin.dashboard" },
          isActive: () => route.name === "admin.dashboard",
        },
      ],
    },
    {
      key: "masterData",
      type: "group",
      label: "Data Master",
      triggerLabel: "Konfigurasi",
      icon: Settings,
      isActive: () =>
        route.name?.startsWith("admin.branch") ||
        route.name?.startsWith("admin.job-template") ||
        route.name?.startsWith("admin.ticket-categor") ||
        route.name?.startsWith("admin.whatsapp"),
      items: visibleItems([
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
      ]),
    },
    {
      key: "operations",
      type: "group",
      label: "Operasional",
      triggerLabel: "Manajemen Kerja",
      icon: Activity,
      isActive: () =>
        route.name?.startsWith("admin.ticket") ||
        route.name?.startsWith("admin.form-permintaan") ||
        route.name?.startsWith("admin.workorder") ||
        route.name?.startsWith("admin.workreport") ||
        route.name?.startsWith("admin.daily-record") ||
        route.name === "admin.daily-usage-report",
      items: visibleItems([
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
      ]),
    },
    {
      key: "management",
      type: "group",
      label: "Manajemen",
      triggerLabel: "Pengguna",
      icon: Users,
      isActive: () =>
        route.name?.startsWith("admin.user") ||
        route.name?.startsWith("admin.role") ||
        route.name?.startsWith("admin.activity-log"),
      items: visibleItems([
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
      ]),
    },
  ];

  return groups.filter((group) => group.items.length > 0);
});

const menuGroups = computed(() =>
  isAdminMode.value ? adminGroups.value : appGroups.value
);

const bottomNavItems = computed(() => {
  if (isAdminMode.value) {
    return visibleItems([
      {
        label: "Home",
        icon: Home,
        to: { name: "admin.dashboard" },
        isActive: () => route.name === "admin.dashboard",
      },
      {
        label: "Tiket",
        icon: Tag,
        to: { name: "admin.tickets" },
        canShow: () => can("ticket-menu"),
        isActive: () => route.name?.startsWith("admin.ticket"),
      },
      {
        label: "Request",
        icon: ClipboardList,
        to: { name: "admin.form-permintaan" },
        canShow: () => can("form-permintaan-list"),
        isActive: () => route.name?.startsWith("admin.form-permintaan"),
      },
      {
        label: "Laporan",
        icon: Calendar,
        to: { name: "admin.daily-records" },
        canShow: () => can("daily-record-list"),
        isActive: () => route.name?.startsWith("admin.daily-record"),
      },
    ]).slice(0, 4);
  }

  return visibleItems([
    {
      label: "Home",
      icon: Home,
      to: { name: "app.dashboard" },
      isActive: () => route.name === "app.dashboard",
    },
    {
      label: "Tiket",
      icon: Tag,
      to: { name: "app.tickets" },
      isActive: () => route.name?.startsWith("app.ticket"),
    },
    {
      label: "Request",
      icon: ClipboardList,
      to: { name: "app.form-permintaan" },
      canShow: () => can("form-permintaan-list"),
      isActive: () => route.name?.startsWith("app.form-permintaan"),
    },
    {
      label: "Laporan",
      icon: Calendar,
      to: { name: "app.daily-records" },
      isActive: () => route.name?.startsWith("app.daily-record"),
    },
  ]).slice(0, 4);
});

const isGroupOpen = (group) =>
  isSidebarCollapsed.value || openGroups.value[group.key] || group.isActive();

const toggleGroup = (key) => {
  openGroups.value[key] = !openGroups.value[key];
};

const closeSidebar = () => {
  sidebarOpen.value = false;
};

const toggleSidebarCollapsed = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const handleLogout = async () => {
  await logout();
};

const routeForTicket = (ticket) => {
  if (isAdminMode.value) {
    return { name: "admin.ticket.detail", params: { id: ticket.id } };
  }

  return { name: "app.ticket.detail", params: { code: ticket.code } };
};

const routeForFormPermintaan = (form) => {
  if (isAdminMode.value) {
    return { name: "admin.form-permintaan.detail", params: { id: form.id } };
  }

  return { name: "app.form-permintaan.detail", params: { id: form.id } };
};

const notificationRoute = (item) => {
  if (item.url) {
    return item.url;
  }

  if (item.type === "form_permintaan") {
    return routeForFormPermintaan(item.data);
  }

  return routeForTicket(item.data);
};

const openSearchResult = (item) => {
  showSearchModal.value = false;
  router.push(
    item.type === "form_permintaan"
      ? routeForFormPermintaan(item.data)
      : routeForTicket(item.data)
  );
};

const fetchNotifications = async () => {
  notificationLoading.value = true;

  try {
    const notifications = await fetchBrowserNotifications({ limit: 10 });
    notificationItems.value = [...notifications].reverse();
    markBrowserNotificationsSeen(user.value, notifications);
  } catch (error) {
    console.error("Gagal memuat notifikasi", error);
    notificationItems.value = [];
  } finally {
    notificationLoading.value = false;
  }
};

const browserNotificationMessage = (reason) => {
  const messages = {
    denied: "Izin notifikasi browser ditolak. Aktifkan dari pengaturan site browser.",
    default: "Izin notifikasi browser belum diberikan.",
    unsupported: "Browser ini tidak mendukung push notification.",
    missing_vapid_key: "VAPID public key belum terbaca oleh backend.",
    service_worker_unavailable: "Service worker notifikasi belum tersedia. Restart frontend lalu refresh halaman.",
  };

  return messages[reason] || "Gagal mengaktifkan browser notification.";
};

const toggleNotifications = async () => {
  showNotifications.value = !showNotifications.value;

  if (showNotifications.value) {
    if (!browserNotificationEnabled.value) {
      try {
        const result = await enableBrowserNotifications(user.value);
        browserNotificationEnabled.value = Boolean(result.enabled);

        if (result.enabled) {
          toast.success("Browser notification aktif.");
        } else {
          toast.warning(browserNotificationMessage(result.reason));
        }
      } catch (error) {
        console.error("Gagal mengaktifkan browser notification", error);
        toast.error("Gagal subscribe browser notification. Cek koneksi API atau login ulang.");
      }
    }

    await fetchNotifications();
  }
};

const fetchSearchResults = async () => {
  const query = searchQuery.value.trim();

  if (!query) {
    searchResults.value = [];
    return;
  }

  searchLoading.value = true;

  try {
    const [ticketsResponse, formsResponse] = await Promise.allSettled([
      axiosInstance.get("/tickets", {
        params: {
          search: query,
          limit: 6,
        },
      }),
      axiosInstance.get("/form-permintaan", {
        params: {
          search: query,
          row_per_page: 6,
        },
      }),
    ]);

    const ticketsData = ticketsResponse.status === "fulfilled" ? ticketsResponse.value.data.data || [] : [];
    const formsData = formsResponse.status === "fulfilled" ? formsResponse.value.data.data?.data || [] : [];

    const tickets = ticketsData.map((ticket) => ({
      type: "ticket",
      data: ticket,
    }));
    const forms = formsData.map((form) => ({
      type: "form_permintaan",
      data: form,
    }));

    searchResults.value = [...tickets, ...forms].slice(0, 10);
  } catch (error) {
    console.error("Gagal mencari data", error);
    searchResults.value = [];
  } finally {
    searchLoading.value = false;
  }
};

const scheduleSearch = () => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(fetchSearchResults, 250);
};

const submitSearch = async () => {
  if (!searchResults.value.length) {
    await fetchSearchResults();
  }

  if (searchResults.value.length === 1) {
    openSearchResult(searchResults.value[0]);
  }
};

const openSearchModal = async () => {
  showSearchModal.value = true;
  showNotifications.value = false;

  await nextTick();
  modalSearchInput.value?.focus();

  if (searchQuery.value.trim()) {
    await fetchSearchResults();
  }
};

const closeSearchModal = () => {
  showSearchModal.value = false;
};

const focusSearch = (event) => {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
    event.preventDefault();
    openSearchModal();
  }

  if (event.key === "Escape" && showSearchModal.value) {
    closeSearchModal();
  }
};

onMounted(() => {
  window.addEventListener("keydown", focusSearch);
  fetchNotifications();
  startBrowserNotificationPolling(user.value).catch((error) => {
    console.error("Gagal memulai browser notification polling", error);
  });
});

onBeforeUnmount(() => {
  window.removeEventListener("keydown", focusSearch);
  window.clearTimeout(searchTimer);
  stopBrowserNotificationPolling();
});
</script>

<template>
  <SidebarProvider
    v-model:open="sidebarOpen"
    v-model:collapsed="isSidebarCollapsed"
    class="bg-slate-50 text-slate-900"
  >
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm lg:hidden"
      @click="closeSidebar"
    ></div>

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

      <SidebarHeader :class="isSidebarCollapsed ? 'lg:px-2' : ''">
        <div
          class="flex h-10 items-center gap-2"
          :class="isSidebarCollapsed ? 'lg:justify-center' : 'lg:justify-start'"
        >
          <RouterLink
            :to="homeRoute"
            class="flex min-w-0 items-center gap-2.5 rounded-md"
            :class="{ 'lg:justify-center': isSidebarCollapsed }"
            title="GA Maintenance"
            @click="closeSidebar"
          >
            <img src="/logo.png" alt="GA Maintenance" class="h-8 w-8 shrink-0 rounded" />
            <span
              class="truncate text-base font-bold text-sidebar-primary"
              :class="{ 'lg:hidden': isSidebarCollapsed }"
            >
              GA Maintenance
            </span>
          </RouterLink>
        </div>
      </SidebarHeader>

      <SidebarContent>
        <template v-for="group in menuGroups" :key="group.key">
          <SidebarMenu v-if="group.type === 'items'">
            <SidebarMenuItem v-for="item in group.items" :key="item.label">
              <RouterLink :to="item.to" custom v-slot="{ href, navigate }">
                <SidebarMenuButton
                  as="a"
                  :href="href"
                  :is-active="item.isActive()"
                  :title="item.label"
                  @click="
                    navigate($event);
                    closeSidebar();
                  "
                >
                  <component :is="item.icon" :size="sidebarIconSize" :class="sidebarIconClass" />
                  <span :class="{ 'lg:hidden': isSidebarCollapsed }">
                    {{ item.label }}
                  </span>
                </SidebarMenuButton>
              </RouterLink>
            </SidebarMenuItem>
          </SidebarMenu>

          <SidebarGroup v-else>
            <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
            <SidebarGroupContent>
              <SidebarMenu>
                <SidebarMenuItem>
                  <SidebarMenuButton
                    :is-active="group.isActive()"
                    :title="group.triggerLabel"
                    @click="toggleGroup(group.key)"
                  >
                    <component :is="group.icon" :size="sidebarIconSize" :class="sidebarIconClass" />
                    <span :class="{ 'lg:hidden': isSidebarCollapsed }">
                      {{ group.triggerLabel }}
                    </span>
                    <ChevronDown
                      :size="16"
                      class="ml-auto transition-transform duration-200"
                      :class="[
                        { 'rotate-180': isGroupOpen(group) },
                        { 'lg:hidden': isSidebarCollapsed },
                      ]"
                    />
                  </SidebarMenuButton>

                  <SidebarMenuSub v-show="isGroupOpen(group)">
                    <SidebarMenuSubItem v-for="item in group.items" :key="item.label">
                      <RouterLink :to="item.to" custom v-slot="{ href, navigate }">
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
                          <component :is="item.icon" :size="sidebarSubIconSize" :class="sidebarIconClass" />
                          <span :class="{ 'lg:hidden': isSidebarCollapsed }">
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
        </template>

        <SidebarGroup>
          <SidebarMenu>
            <SidebarMenuItem>
              <RouterLink :to="profileRoute" custom v-slot="{ href, navigate }">
                <SidebarMenuButton
                  as="a"
                  :href="href"
                  :is-active="route.name === profileRoute.name"
                  title="Pengaturan"
                  @click="
                    navigate($event);
                    closeSidebar();
                  "
                >
                  <Settings :size="sidebarIconSize" :class="sidebarIconClass" />
                  <span :class="{ 'lg:hidden': isSidebarCollapsed }">
                    Pengaturan
                  </span>
                </SidebarMenuButton>
              </RouterLink>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroup>
      </SidebarContent>

      <SidebarFooter :class="isSidebarCollapsed ? 'lg:px-2' : ''">
        <!-- PWA Install Button - hidden only when already running as standalone PWA -->
        <button
          v-if="!isStandalone"
          type="button"
          class="flex w-full items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 text-sm font-medium text-blue-700 transition hover:bg-blue-100"
          :class="isSidebarCollapsed ? 'lg:justify-center lg:border-0 lg:bg-transparent lg:px-2' : ''"
          aria-label="Install aplikasi"
          title="Install aplikasi"
          @click="promptInstall"
        >
          <Download :size="18" class="shrink-0" />
          <span :class="{ 'lg:hidden': isSidebarCollapsed }">Install Aplikasi</span>
        </button>

        <div v-if="isSidebarCollapsed" class="hidden justify-center lg:flex">
          <RouterLink
            :to="profileRoute"
            title="Profil"
            class="rounded-full ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:ring-offset-2"
            @click="closeSidebar"
          >
            <Avatar class="size-9">
              <AvatarImage :src="avatarUrl" :alt="user?.name || 'User'" />
            </Avatar>
          </RouterLink>
        </div>

        <div
          class="flex items-center gap-3 rounded-lg bg-sidebar-accent/70 p-2"
          :class="{ 'lg:hidden': isSidebarCollapsed }"
        >
          <RouterLink
            :to="profileRoute"
            class="shrink-0 rounded-full"
            title="Profil"
            @click="closeSidebar"
          >
            <Avatar class="size-9">
              <AvatarImage :src="avatarUrl" :alt="user?.name || 'User'" />
            </Avatar>
          </RouterLink>

          <RouterLink
            :to="profileRoute"
            class="min-w-0 flex-1"
            @click="closeSidebar"
          >
            <span class="block truncate text-sm font-semibold leading-5 text-sidebar-foreground">
              {{ user?.name || "User" }}
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

    <SidebarInset>
      <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="relative flex h-16 items-center justify-between gap-4 px-4 sm:px-5 lg:px-6">
          <div class="hidden shrink-0 lg:block">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-8 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-950"
              :title="isSidebarCollapsed ? 'Perluas sidebar' : 'Minimize sidebar'"
              :aria-label="isSidebarCollapsed ? 'Perluas sidebar' : 'Minimize sidebar'"
              @click="toggleSidebarCollapsed"
            >
              <PanelLeftOpen v-if="isSidebarCollapsed" :size="18" />
              <PanelLeftClose v-else :size="18" />
            </Button>
          </div>

          <SidebarTrigger
            class="h-9 w-9 shrink-0 text-slate-600 lg:hidden"
          />

          <RouterLink
            :to="homeRoute"
            class="flex min-w-0 shrink items-center gap-2 text-blue-600 lg:hidden"
          >
            <img src="/logo.png" alt="GA Maintenance" class="h-7 w-7 shrink-0 rounded" />
            <span class="truncate text-base font-bold">GA Maintenance</span>
          </RouterLink>

          <div class="hidden min-w-0 lg:block">
            <h1 class="truncate text-sm font-semibold text-slate-950">
              {{ pageTitle }}
            </h1>
            <p class="text-xs text-slate-500">{{ portalLabel }}</p>
          </div>

          <form
            class="hidden w-full max-w-[360px] items-center lg:ml-auto lg:flex xl:max-w-[420px]"
            @submit.prevent="submitSearch"
          >
            <div class="relative w-full">
              <Search
                :size="18"
                class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <Input
                v-model="searchQuery"
                type="search"
                class="pl-10 pr-16"
                placeholder="Cari tiket, form, atau keyword..."
                readonly
                @focus="openSearchModal"
                @click="openSearchModal"
              />
              <div
                class="pointer-events-none absolute right-3 top-1/2 flex -translate-y-1/2 items-center gap-1 rounded border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500"
              >
                <Command :size="12" />
                K
              </div>
            </div>
          </form>

          <div class="flex items-center gap-3">
            <DropdownMenu>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="relative rounded-full text-slate-600 hover:text-slate-900"
                aria-label="Notifikasi"
                @click="toggleNotifications"
              >
                <Bell :size="22" />
                <span
                  v-if="notificationCount"
                  class="absolute right-1.5 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white"
                >
                  {{ notificationCount }}
                </span>
              </Button>

              <DropdownMenuContent
                v-if="showNotifications"
                class="w-80 p-0"
              >
                <div class="border-b border-slate-100 px-4 py-3">
                  <p class="text-sm font-semibold text-slate-900">Notifikasi</p>
                  <p class="mt-0.5 text-xs text-slate-500">Tiket aktif dan form permintaan pending</p>
                </div>

                <div v-if="notificationLoading" class="space-y-2 px-4 py-4">
                  <Skeleton class="h-12" />
                  <Skeleton class="h-12" />
                  <Skeleton class="h-12" />
                </div>

                <div v-else-if="notificationItems.length" class="max-h-80 overflow-y-auto py-1">
                  <RouterLink
                    v-for="item in notificationItems"
                    :key="item.id"
                    :to="notificationRoute(item)"
                    class="flex gap-3 px-4 py-3 transition hover:bg-slate-50"
                    @click="showNotifications = false"
                  >
                    <span
                      class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                      :class="item.module === 'form-permintaan' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'"
                    >
                      <FileText :size="16" />
                    </span>
                    <span class="min-w-0">
                      <span class="block truncate text-sm font-semibold text-slate-900">
                        {{ item.title }}
                      </span>
                      <span class="mt-0.5 block truncate text-xs text-slate-500">
                        {{ item.body }}
                      </span>
                    </span>
                  </RouterLink>
                </div>

                <div v-else class="px-4 py-6 text-center text-sm text-slate-500">
                  Tidak ada notifikasi aktif.
                </div>
              </DropdownMenuContent>
            </DropdownMenu>

          </div>
        </div>
      </header>

      <main class="px-4 pb-24 pt-4 sm:px-5 lg:px-6 lg:pb-4">
        <router-view></router-view>
      </main>
    </SidebarInset>

    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-2 py-2 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden">
      <div class="grid gap-1" :style="{ gridTemplateColumns: `repeat(${bottomNavItems.length}, minmax(0, 1fr))` }">
        <RouterLink
          v-for="item in bottomNavItems"
          :key="item.label"
          :to="item.to"
          class="flex flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 text-xs font-medium text-slate-500 transition hover:bg-slate-50 hover:text-blue-600"
          :class="{ 'bg-blue-50 text-blue-600': item.isActive() }"
        >
          <component :is="item.icon" :size="20" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </div>
    </nav>
  </SidebarProvider>

  <div
    v-if="showSearchModal"
    class="fixed inset-0 z-50 bg-slate-950/50 px-4 py-8 backdrop-blur-sm sm:py-12"
    @click.self="closeSearchModal"
  >
    <Card class="mx-auto max-w-3xl overflow-hidden shadow-2xl">
      <form class="border-b border-slate-200" @submit.prevent="submitSearch">
        <div class="flex items-center gap-3 px-4 py-3">
          <Search :size="20" class="shrink-0 text-slate-400" />
          <Input
            ref="modalSearchInput"
            v-model="searchQuery"
            type="search"
            class="h-10 min-w-0 flex-1 border-0 bg-transparent text-base text-slate-900 outline-none placeholder:text-slate-400"
            placeholder="Cari kode tiket, form permintaan, kategori, cabang, atau deskripsi..."
            @input="scheduleSearch"
          />
          <Button
            type="button"
            variant="outline"
            size="sm"
            class="h-8 text-xs text-slate-500"
            @click="closeSearchModal"
          >
            Esc
          </Button>
        </div>
      </form>

      <div class="max-h-[68vh] overflow-y-auto">
        <div v-if="!searchQuery.trim()" class="px-6 py-10 text-center">
          <Search :size="34" class="mx-auto text-slate-300" />
          <p class="mt-3 text-sm font-semibold text-slate-800">Mulai cari data</p>
          <p class="mt-1 text-sm text-slate-500">
            Gunakan kode tiket, nomor form permintaan, kategori, cabang, atau kata kunci deskripsi.
          </p>
        </div>

        <div v-else-if="searchLoading" class="space-y-3 px-5 py-5">
          <Skeleton class="h-20" />
          <Skeleton class="h-20" />
          <Skeleton class="h-20" />
        </div>

        <div v-else-if="searchResults.length" class="divide-y divide-slate-100">
          <button
            v-for="item in searchResults"
            :key="`${item.type}-${item.data.id}`"
            type="button"
            class="grid w-full gap-3 px-5 py-4 text-left transition hover:bg-slate-50 sm:grid-cols-[1fr_auto]"
            @click="openSearchResult(item)"
          >
            <span class="min-w-0">
              <span class="flex flex-wrap items-center gap-2">
                <Badge :variant="item.type === 'form_permintaan' ? 'warning' : 'info'">
                  {{ item.type === 'form_permintaan' ? 'Form Permintaan' : (item.data.code || 'Tiket') }}
                </Badge>
                <Badge :variant="statusBadgeVariant(item.data.status)">
                  {{ statusLabel(item.data.status) }}
                </Badge>
                <Badge v-if="item.data.priority" :variant="priorityBadgeVariant(item.data.priority)">
                  {{ priorityLabel(item.data.priority) }}
                </Badge>
              </span>

              <span class="mt-2 block truncate text-sm font-semibold text-slate-950">
                {{ item.type === 'form_permintaan' ? formPermintaanTitle(item.data) : ticketTitle(item.data) }}
              </span>
              <span class="mt-1 line-clamp-2 block text-sm leading-5 text-slate-600">
                <template v-if="item.type === 'form_permintaan'">
                  {{ item.data.branch?.name || item.data.user?.name || "Form permintaan" }}
                </template>
                <template v-else>
                  {{ stripHtml(item.data.description) || "Tidak ada deskripsi." }}
                </template>
              </span>
            </span>

            <span class="flex flex-col gap-1 text-xs text-slate-500 sm:items-end">
              <span>{{ item.data.branch?.name || "-" }}</span>
              <span>{{ formatDate(item.data.created_at || item.data.date) }}</span>
            </span>
          </button>
        </div>

        <div v-else class="px-6 py-10 text-center">
          <Search :size="34" class="mx-auto text-slate-300" />
          <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada hasil</p>
          <p class="mt-1 text-sm text-slate-500">
            Coba gunakan kata kunci lain.
          </p>
        </div>
      </div>
    </Card>
  </div>
</template>
