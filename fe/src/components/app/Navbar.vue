<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { axiosInstance } from "@/plugins/axios";
import {
  Bell,
  Command,
  FileText,
  Search,
} from "lucide-vue-next";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { SidebarRail, SidebarTrigger } from "@/components/ui/sidebar";
import { Skeleton } from "@/components/ui/skeleton";

const router = useRouter();
const route = useRoute();

const showNotifications = ref(false);
const showSearchModal = ref(false);
const searchInput = ref(null);
const modalSearchInput = ref(null);
const searchQuery = ref("");
const searchResults = ref([]);
const searchLoading = ref(false);
const notificationItems = ref([]);
const notificationLoading = ref(false);
let searchTimer = null;

const notificationCount = computed(() => notificationItems.value.length);
const pageTitle = computed(() => route.meta?.title || "Dashboard");

const ticketTitle = (ticket) =>
  ticket.category?.name || ticket.title || ticket.subject || "Tiket";

const formPermintaanTitle = (form) => form.request_number || "Form Permintaan";

const statusLabel = (status) => {
  const labels = {
    open: "Menunggu",
    in_progress: "Diproses",
    resolved: "Selesai",
    closed: "Ditutup",
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
  if (status === "open") return "warning";
  if (status === "in_progress") return "info";
  if (status === "resolved") return "success";
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

const fetchNotifications = async () => {
  notificationLoading.value = true;

  try {
    const [openResponse, progressResponse, formPermintaanResponse] = await Promise.all([
      axiosInstance.get("/tickets", { params: { status: "open", limit: 5 } }),
      axiosInstance.get("/tickets", { params: { status: "in_progress", limit: 5 } }),
      axiosInstance.get("/form-permintaan", { params: { status: "pending", row_per_page: 5 } }),
    ]);

    notificationItems.value = [
      ...(openResponse.data.data || []).map((ticket) => ({ type: "ticket", data: ticket })),
      ...(progressResponse.data.data || []).map((ticket) => ({ type: "ticket", data: ticket })),
      ...((formPermintaanResponse.data.data?.data || []).map((form) => ({ type: "form_permintaan", data: form }))),
    ]
      .sort((a, b) => new Date(b.data.updated_at || b.data.created_at) - new Date(a.data.updated_at || a.data.created_at))
      .slice(0, 6);
  } catch (error) {
    console.error("Gagal memuat notifikasi", error);
    notificationItems.value = [];
  } finally {
    notificationLoading.value = false;
  }
};

const notificationRoute = (item) => {
  if (item.type === "form_permintaan") {
    return { name: "app.form-permintaan.detail", params: { id: item.data.id } };
  }

  return { name: "app.ticket.detail", params: { code: item.data.code } };
};

const toggleNotifications = async () => {
  showNotifications.value = !showNotifications.value;

  if (showNotifications.value) {
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
    const response = await axiosInstance.get("/tickets", {
      params: {
        search: query,
        limit: 8,
      },
    });

    searchResults.value = response.data.data || [];
  } catch (error) {
    console.error("Gagal mencari tiket", error);
    searchResults.value = [];
  } finally {
    searchLoading.value = false;
  }
};

const scheduleSearch = () => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(fetchSearchResults, 250);
};

const openTicket = (ticket) => {
  showSearchModal.value = false;
  router.push({ name: "app.ticket.detail", params: { code: ticket.code } });
};

const submitSearch = async () => {
  if (!searchResults.value.length) {
    await fetchSearchResults();
  }

  if (searchResults.value.length === 1) {
    openTicket(searchResults.value[0]);
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
});

onBeforeUnmount(() => {
  window.removeEventListener("keydown", focusSearch);
  window.clearTimeout(searchTimer);
});
</script>

<template>
  <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="relative flex h-16 items-center justify-between gap-4 px-4 pr-16 sm:px-5 sm:pr-16 lg:px-6">
      <SidebarRail class="hidden lg:inline-flex" />

      <RouterLink
        :to="{ name: 'app.dashboard' }"
        class="flex min-w-0 shrink items-center gap-2 text-blue-600 lg:hidden"
      >
        <img src="/logo.png" alt="GA Maintenance" class="h-7 w-7 shrink-0 rounded" />
        <span class="truncate text-base font-bold">GA Maintenance</span>
      </RouterLink>

      <div class="hidden min-w-0 lg:block">
        <h1 class="truncate text-sm font-semibold text-slate-950">
          {{ pageTitle }}
        </h1>
        <p class="text-xs text-slate-500">User portal</p>
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
            ref="searchInput"
            v-model="searchQuery"
            type="search"
            class="pl-10 pr-16"
            placeholder="Cari tiket, ID, atau keyword..."
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
                :key="`${item.type}-${item.data.id}`"
                :to="notificationRoute(item)"
                class="flex gap-3 px-4 py-3 transition hover:bg-slate-50"
                @click="showNotifications = false"
              >
                <span
                  class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                  :class="item.type === 'form_permintaan' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'"
                >
                  <FileText :size="16" />
                </span>
                <span class="min-w-0">
                  <span class="block truncate text-sm font-semibold text-slate-900">
                    {{ item.type === 'form_permintaan' ? formPermintaanTitle(item.data) : ticketTitle(item.data) }}
                  </span>
                  <span class="mt-0.5 block truncate text-xs text-slate-500">
                    <template v-if="item.type === 'form_permintaan'">
                      Form Permintaan · Pending
                    </template>
                    <template v-else>
                      {{ item.data.code }} · {{ statusLabel(item.data.status) }}
                    </template>
                  </span>
                </span>
              </RouterLink>
            </div>

            <div v-else class="px-4 py-6 text-center text-sm text-slate-500">
              Tidak ada notifikasi aktif.
            </div>
          </DropdownMenuContent>
        </DropdownMenu>

        <SidebarTrigger
          class="absolute right-3 top-1/2 h-10 w-10 -translate-y-1/2 text-slate-600 lg:hidden"
        />
      </div>
    </div>
  </header>

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
            placeholder="Cari kode tiket, kategori, cabang, atau deskripsi..."
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
          <p class="mt-3 text-sm font-semibold text-slate-800">Mulai cari tiket</p>
          <p class="mt-1 text-sm text-slate-500">
            Gunakan kode tiket, kata kunci deskripsi, kategori, atau cabang.
          </p>
        </div>

        <div v-else-if="searchLoading" class="space-y-3 px-5 py-5">
          <Skeleton class="h-20" />
          <Skeleton class="h-20" />
          <Skeleton class="h-20" />
        </div>

        <div v-else-if="searchResults.length" class="divide-y divide-slate-100">
          <button
            v-for="ticket in searchResults"
            :key="ticket.id"
            type="button"
            class="grid w-full gap-3 px-5 py-4 text-left transition hover:bg-slate-50 sm:grid-cols-[1fr_auto]"
            @click="openTicket(ticket)"
          >
            <span class="min-w-0">
              <span class="flex flex-wrap items-center gap-2">
                <Badge variant="info">
                  {{ ticket.code }}
                </Badge>
                <Badge :variant="statusBadgeVariant(ticket.status)">
                  {{ statusLabel(ticket.status) }}
                </Badge>
                <Badge :variant="priorityBadgeVariant(ticket.priority)">
                  {{ priorityLabel(ticket.priority) }}
                </Badge>
              </span>

              <span class="mt-2 block truncate text-sm font-semibold text-slate-950">
                {{ ticketTitle(ticket) }}
              </span>
              <span class="mt-1 line-clamp-2 block text-sm leading-5 text-slate-600">
                {{ stripHtml(ticket.description) || "Tidak ada deskripsi." }}
              </span>
            </span>

            <span class="flex flex-col gap-1 text-xs text-slate-500 sm:items-end">
              <span>{{ ticket.branch?.name || "Cabang tidak tersedia" }}</span>
              <span>{{ formatDate(ticket.created_at) }}</span>
              <span>{{ ticket.replies_count || 0 }} balasan</span>
            </span>
          </button>
        </div>

        <div v-else class="px-6 py-10 text-center">
          <FileText :size="34" class="mx-auto text-slate-300" />
          <p class="mt-3 text-sm font-semibold text-slate-800">Tidak ada hasil</p>
          <p class="mt-1 text-sm text-slate-500">
            Coba gunakan kode tiket atau kata kunci lain.
          </p>
        </div>
      </div>

      <div class="flex items-center justify-between border-t border-slate-100 px-4 py-2 text-xs text-slate-500">
        <span>Tekan Enter untuk membuka jika hanya ada satu hasil</span>
        <span>Ctrl K</span>
      </div>
    </Card>
  </div>
</template>
