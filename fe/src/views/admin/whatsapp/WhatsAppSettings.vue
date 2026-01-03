<script setup>
import { ref, onMounted, computed } from "vue";
import { useWhatsAppSettingStore } from "@/stores/whatsappSetting";
import { storeToRefs } from "pinia";
import Alert from "@/components/common/Alert.vue";
import { MessageSquare, Settings, Save, Send, Edit, Check, X, Info } from "lucide-vue-next";
import { can } from "@/helpers/permissionHelper";

const store = useWhatsAppSettingStore();
const { settings, templates, placeholders, loading, success, error } = storeToRefs(store);
const { fetchSettings, updateSettings, fetchTemplates, updateTemplate, fetchPlaceholders, testSend, testSendGroup, clearMessages } = store;

// Tab state
const activeTab = ref("settings");

// Settings form
const settingsForm = ref({
  enabled: "true",
  token: "",
  group_id: "",
  delay: "2",
});

// Template edit state
const editingTemplate = ref(null);
const editForm = ref({
  content: "",
  is_active: true,
  send_to_group: false,
});

// Test send state
const testPhone = ref("");
const testMessage = ref("Test pesan dari GA Maintenance");
const showTestModal = ref(false);
const showTestGroupModal = ref(false);
const testGroupMessage = ref("");

// Template type labels
const templateLabels = {
  new_ticket: "🚨 Tiket Baru",
  status_update: "📢 Update Status",
  reply: "💬 Balasan Tiket",
  assignment: "👋 Penugasan Staff",
};

// Methods
const loadData = async () => {
  try {
    await Promise.all([fetchSettings(), fetchTemplates()]);
    settingsForm.value = { ...settings.value };
  } catch (e) {
    console.error("Error loading data:", e);
  }
};

const handleSaveSettings = async () => {
  try {
    await updateSettings(settingsForm.value);
  } catch (e) {
    console.error("Error saving settings:", e);
  }
};

const startEditTemplate = async (template) => {
  editingTemplate.value = template;
  editForm.value = {
    content: template.content,
    is_active: template.is_active,
    send_to_group: template.send_to_group,
  };
  await fetchPlaceholders(template.type);
};

const cancelEditTemplate = () => {
  editingTemplate.value = null;
  editForm.value = { content: "", is_active: true, send_to_group: false };
};

const handleSaveTemplate = async () => {
  if (!editingTemplate.value) return;

  try {
    await updateTemplate(editingTemplate.value.id, editForm.value);
    editingTemplate.value = null;
  } catch (e) {
    console.error("Error saving template:", e);
  }
};

const handleToggleActive = async (template) => {
  try {
    await updateTemplate(template.id, { is_active: !template.is_active });
  } catch (e) {
    console.error("Error toggling template:", e);
  }
};

const handleTestSend = async () => {
  if (!testPhone.value || !testMessage.value) return;

  try {
    await testSend(testPhone.value, testMessage.value);
    showTestModal.value = false;
    testPhone.value = "";
    testMessage.value = "Test pesan dari GA Maintenance";
  } catch (e) {
    console.error("Error sending test:", e);
  }
};

const handleTestSendGroup = async () => {
  try {
    await testSendGroup(testGroupMessage.value || null);
    showTestGroupModal.value = false;
    testGroupMessage.value = "";
  } catch (e) {
    console.error("Error sending test to group:", e);
  }
};

const insertPlaceholder = (placeholder) => {
  editForm.value.content += placeholder;
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan WhatsApp</h1>
        <p class="text-gray-600">Kelola pengaturan dan template pesan WhatsApp</p>
      </div>
      <div class="flex gap-2">
        <button
          v-if="can('whatsapp-setting-edit')"
          @click="showTestGroupModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          <Send :size="16" class="mr-2" />
          Test Grup
        </button>
        <button
          v-if="can('whatsapp-setting-edit')"
          @click="showTestModal = true"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
        >
          <Send :size="16" class="mr-2" />
          Test Kirim
        </button>
      </div>
    </div>

    <!-- Alert -->
    <Alert
      v-if="success"
      type="success"
      :message="success"
      :auto-close="true"
      :duration="3000"
      @close="clearMessages"
    />
    <Alert
      v-if="error"
      type="error"
      :message="error"
      :auto-close="true"
      :duration="5000"
      @close="clearMessages"
    />

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'settings'"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm',
            activeTab === 'settings'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
          ]"
        >
          <Settings :size="16" class="inline mr-2" />
          Pengaturan
        </button>
        <button
          @click="activeTab = 'templates'"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm',
            activeTab === 'templates'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
          ]"
        >
          <MessageSquare :size="16" class="inline mr-2" />
          Template Pesan
        </button>
      </nav>
    </div>

    <!-- Settings Tab -->
    <div v-if="activeTab === 'settings'" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-6">
        <!-- Enable Toggle -->
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-900">Aktifkan Notifikasi WhatsApp</label>
            <p class="text-sm text-gray-500">Kirim notifikasi ke WhatsApp saat ada aktivitas tiket</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              :checked="settingsForm.enabled === 'true'"
              @change="settingsForm.enabled = $event.target.checked ? 'true' : 'false'"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
        </div>

        <!-- Token -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fonnte Token</label>
          <input
            v-model="settingsForm.token"
            type="password"
            placeholder="Masukkan token Fonnte"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p class="mt-1 text-xs text-gray-500">Token API dari dashboard Fonnte</p>
        </div>

        <!-- Group ID -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Group ID</label>
          <input
            v-model="settingsForm.group_id"
            type="text"
            placeholder="Contoh: 120363322658703628@g.us"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <p class="mt-1 text-xs text-gray-500">ID grup WhatsApp untuk notifikasi tiket baru</p>
        </div>

        <!-- Delay -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Delay Pengiriman (detik)</label>
          <input
            v-model="settingsForm.delay"
            type="number"
            min="1"
            max="60"
            class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Save Button -->
        <div class="pt-4 border-t">
          <button
            v-if="can('whatsapp-setting-edit')"
            @click="handleSaveSettings"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <Save :size="16" class="mr-2" />
            {{ loading ? "Menyimpan..." : "Simpan Pengaturan" }}
          </button>
        </div>
      </div>
    </div>

    <!-- Templates Tab -->
    <div v-if="activeTab === 'templates'" class="space-y-4">
      <div
        v-for="template in templates"
        :key="template.id"
        class="bg-white rounded-lg shadow overflow-hidden"
      >
        <!-- Template Header -->
        <div class="px-6 py-4 bg-gray-50 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-lg">{{ templateLabels[template.type] || template.name }}</span>
            <span
              :class="[
                'px-2 py-0.5 text-xs rounded-full',
                template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600',
              ]"
            >
              {{ template.is_active ? "Aktif" : "Nonaktif" }}
            </span>
            <span
              v-if="template.send_to_group"
              class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800"
            >
              Kirim ke Grup
            </span>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="can('whatsapp-setting-edit')"
              @click="handleToggleActive(template)"
              :class="[
                'px-3 py-1 text-xs rounded',
                template.is_active
                  ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  : 'bg-green-100 text-green-700 hover:bg-green-200',
              ]"
            >
              {{ template.is_active ? "Nonaktifkan" : "Aktifkan" }}
            </button>
            <button
              v-if="can('whatsapp-setting-edit')"
              @click="startEditTemplate(template)"
              class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
            >
              <Edit :size="14" class="inline mr-1" />
              Edit
            </button>
          </div>
        </div>

        <!-- Template Content (View Mode) -->
        <div
          v-if="editingTemplate?.id !== template.id"
          class="px-6 py-4"
        >
          <pre class="whitespace-pre-wrap text-sm text-gray-700 font-sans bg-gray-50 p-4 rounded-lg">{{ template.content }}</pre>
        </div>

        <!-- Template Content (Edit Mode) -->
        <div
          v-if="editingTemplate?.id === template.id"
          class="px-6 py-4 space-y-4"
        >
          <!-- Placeholders -->
          <div class="bg-blue-50 p-4 rounded-lg">
            <div class="flex items-center gap-2 mb-2">
              <Info :size="16" class="text-blue-600" />
              <span class="text-sm font-medium text-blue-800">Placeholder yang tersedia:</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="(desc, placeholder) in placeholders[template.type]"
                :key="placeholder"
                @click="insertPlaceholder(placeholder)"
                class="px-2 py-1 text-xs bg-white border border-blue-200 rounded hover:bg-blue-100"
                :title="desc"
              >
                {{ placeholder }}
              </button>
            </div>
          </div>

          <!-- Content Editor -->
          <textarea
            v-model="editForm.content"
            rows="12"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
          ></textarea>

          <!-- Options -->
          <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="editForm.is_active"
                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">Aktif</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="editForm.send_to_group"
                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">Kirim ke Grup</span>
            </label>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end gap-2">
            <button
              @click="cancelEditTemplate"
              class="px-4 py-2 text-gray-600 hover:text-gray-800"
            >
              <X :size="16" class="inline mr-1" />
              Batal
            </button>
            <button
              @click="handleSaveTemplate"
              :disabled="loading"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <Check :size="16" class="mr-2" />
              {{ loading ? "Menyimpan..." : "Simpan Template" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Send Modal -->
    <div
      v-if="showTestModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold mb-4">Test Kirim WhatsApp</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Tujuan</label>
            <input
              v-model="testPhone"
              type="text"
              placeholder="08xxxxxxxxxx"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pesan</label>
            <textarea
              v-model="testMessage"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
          <button
            @click="showTestModal = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-800"
          >
            Batal
          </button>
          <button
            @click="handleTestSend"
            :disabled="loading || !testPhone || !testMessage"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            <Send :size="16" class="mr-2" />
            {{ loading ? "Mengirim..." : "Kirim" }}
          </button>
        </div>
      </div>
    </div>

    <!-- Test Send Group Modal -->
    <div
      v-if="showTestGroupModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold mb-4">Test Kirim ke Grup WhatsApp</h3>

        <div class="space-y-4">
          <div class="bg-blue-50 p-3 rounded-lg">
            <p class="text-sm text-blue-800">
              <strong>Group ID:</strong> {{ settingsForm.group_id || 'Belum dikonfigurasi' }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pesan (opsional)</label>
            <textarea
              v-model="testGroupMessage"
              rows="4"
              placeholder="Kosongkan untuk menggunakan pesan default"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
          <button
            @click="showTestGroupModal = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-800"
          >
            Batal
          </button>
          <button
            @click="handleTestSendGroup"
            :disabled="loading || !settingsForm.group_id"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <Send :size="16" class="mr-2" />
            {{ loading ? "Mengirim..." : "Kirim ke Grup" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
