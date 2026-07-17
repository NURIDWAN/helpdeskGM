<script setup>
import { nextTick, onMounted, ref, watch } from "vue";
import {
  Bold,
  Italic,
  Link,
  Image as ImageIcon,
  List,
  ListOrdered,
  Quote,
  Underline,
} from "lucide-vue-next";

const props = defineProps({
  modelValue: {
    type: String,
    default: "",
  },
  id: {
    type: String,
    default: undefined,
  },
  placeholder: {
    type: String,
    default: "",
  },
  minHeight: {
    type: String,
    default: "160px",
  },
  error: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "image-selected"]);

const editor = ref(null);
const imageInput = ref(null);
const isFocused = ref(false);
const isEditorEmpty = ref(true);
const savedSelection = ref(null);

const isEmptyHtml = (html) => {
  const node = document.createElement("div");
  node.innerHTML = html || "";
  return !node.textContent?.replace(/\u00a0/g, " ").trim() && !node.querySelector("img");
};

const getSerializableHtml = () => {
  const node = editor.value?.cloneNode(true);
  if (!node) return "";

  node.querySelectorAll("img[data-local-preview='true']").forEach((image) => {
    image.remove();
  });

  return node.innerHTML || "";
};

const emitContent = () => {
  isEditorEmpty.value = isEmptyHtml(editor.value?.innerHTML || "");
  const html = getSerializableHtml();
  emit("update:modelValue", isEmptyHtml(html) ? "" : html);
};

const saveSelection = () => {
  const selection = window.getSelection();
  if (!selection || selection.rangeCount === 0 || !editor.value) return;

  const range = selection.getRangeAt(0);
  if (editor.value.contains(range.commonAncestorContainer)) {
    savedSelection.value = range.cloneRange();
  }
};

const restoreSelection = () => {
  if (!savedSelection.value) return;

  const selection = window.getSelection();
  selection?.removeAllRanges();
  selection?.addRange(savedSelection.value);
};

const runCommand = (command, value = null) => {
  editor.value?.focus();
  restoreSelection();
  document.execCommand(command, false, value);
  emitContent();
};

const addLink = () => {
  const url = window.prompt("Masukkan URL link");
  if (!url) return;

  const normalizedUrl = /^https?:\/\//i.test(url) ? url : `https://${url}`;
  runCommand("createLink", normalizedUrl);
};

const selectImage = () => {
  saveSelection();
  imageInput.value?.click();
};

const addImage = (event) => {
  const file = event.target.files?.[0];
  event.target.value = "";

  if (!file || !file.type.startsWith("image/")) return;
  if (file.size > 2 * 1024 * 1024) {
    window.alert("Ukuran gambar maksimal 2MB");
    return;
  }

  emit("image-selected", file);

  const reader = new FileReader();
  reader.onload = () => {
    if (!reader.result) return;

    editor.value?.focus();
    restoreSelection();
    document.execCommand("insertImage", false, reader.result);

    const images = Array.from(editor.value?.querySelectorAll("img") || []);
    const image = [...images].reverse().find((item) => item.getAttribute("src") === reader.result);
    image?.setAttribute("data-local-preview", "true");
    image?.setAttribute("alt", file.name);

    emitContent();
  };
  reader.readAsDataURL(file);
};

watch(
  () => props.modelValue,
  async (value) => {
    if (isFocused.value || !editor.value || editor.value.innerHTML === value) {
      return;
    }

    await nextTick();
    editor.value.innerHTML = value || "";
    isEditorEmpty.value = isEmptyHtml(editor.value.innerHTML);
  },
  { immediate: true }
);

onMounted(() => {
  if (editor.value) {
    editor.value.innerHTML = props.modelValue || "";
    isEditorEmpty.value = isEmptyHtml(editor.value.innerHTML);
  }
});
</script>

<template>
  <div
    class="overflow-hidden rounded-lg border bg-white"
    :class="error ? 'border-red-300' : 'border-gray-200 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500'"
  >
    <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-2">
      <button type="button" class="editor-button" title="Bold" @click="runCommand('bold')">
        <Bold :size="16" />
      </button>
      <button type="button" class="editor-button" title="Italic" @click="runCommand('italic')">
        <Italic :size="16" />
      </button>
      <button type="button" class="editor-button" title="Underline" @click="runCommand('underline')">
        <Underline :size="16" />
      </button>
      <span class="mx-1 h-5 w-px bg-gray-200"></span>
      <button type="button" class="editor-button" title="Bullet list" @click="runCommand('insertUnorderedList')">
        <List :size="16" />
      </button>
      <button type="button" class="editor-button" title="Numbered list" @click="runCommand('insertOrderedList')">
        <ListOrdered :size="16" />
      </button>
      <button type="button" class="editor-button" title="Quote" @click="runCommand('formatBlock', 'blockquote')">
        <Quote :size="16" />
      </button>
      <span class="mx-1 h-5 w-px bg-gray-200"></span>
      <button type="button" class="editor-button" title="Link" @click="addLink">
        <Link :size="16" />
      </button>
      <button type="button" class="editor-button" title="Tambah gambar sebagai lampiran" @click="selectImage">
        <ImageIcon :size="16" />
      </button>
      <input
        ref="imageInput"
        type="file"
        accept="image/*"
        class="hidden"
        @change="addImage"
      />
    </div>

    <div
      :id="id"
      ref="editor"
      contenteditable="true"
      class="rich-editor-content w-full overflow-y-auto px-4 py-3 text-sm text-gray-800 outline-none"
      :class="{ 'is-empty': isEditorEmpty }"
      :data-placeholder="placeholder"
      :style="{ minHeight }"
      @focus="isFocused = true"
      @blur="saveSelection(); isFocused = false; emitContent()"
      @input="emitContent"
      @keyup="saveSelection"
      @mouseup="saveSelection"
    ></div>
  </div>
</template>

<style scoped>
.editor-button {
  align-items: center;
  border-radius: 6px;
  color: rgb(55 65 81);
  display: inline-flex;
  height: 32px;
  justify-content: center;
  width: 32px;
}

.editor-button:hover {
  background: rgb(229 231 235);
}

.rich-editor-content.is-empty::before {
  color: rgb(148 163 184);
  content: attr(data-placeholder);
  pointer-events: none;
}

.rich-editor-content :deep(p) {
  margin: 0 0 0.65rem;
}

.rich-editor-content :deep(ul),
.rich-editor-content :deep(ol) {
  margin: 0.5rem 0;
  padding-left: 1.25rem;
}

.rich-editor-content :deep(ul) {
  list-style: disc;
}

.rich-editor-content :deep(ol) {
  list-style: decimal;
}

.rich-editor-content :deep(blockquote) {
  border-left: 3px solid rgb(147 197 253);
  color: rgb(71 85 105);
  margin: 0.75rem 0;
  padding-left: 0.75rem;
}

.rich-editor-content :deep(a) {
  color: rgb(37 99 235);
  text-decoration: underline;
}

.rich-editor-content :deep(img) {
  border-radius: 8px;
  display: block;
  height: auto;
  margin: 0.75rem 0;
  max-width: 100%;
}
</style>
