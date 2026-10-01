<template>
  <div ref="containerRef" class="relative">
    <p v-if="contentError" class="text-sm text-destructive" role="alert">{{ $t('editor.unsupported_content') }}</p>
    <template v-if="editable">
      <RCSmartTiptapToolbar
        v-if="editor"
        :editor
        :container-ref
      />

      <TiptapContextMenus v-if="editor" :editor image-menu />

      <EditorContent :editor />
    </template>

    <TiptapDisplay v-else :element />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapDisplay from './TiptapDisplay.vue';

import RCSmartTiptapToolbar from '@/Components/TipTap/RCSmartTiptapToolbar.vue';
import TiptapContextMenus from '@/Components/TipTap/TiptapContextMenus.vue';
import { useTiptapFileUpload } from '@/Components/TipTap/composables/useTiptapFileUpload';
import { createFullExtensions } from '@/Components/TipTap/extensions/presets';
import { normalizeContent } from '@/Components/TipTap/normalizeContent';
import '@/Components/TipTap/tiptap-base.css';

const props = withDefaults(defineProps<{
  element: {
    id?: number;
    type?: string;
    html?: string | null;
    json_content?: Record<string, unknown> | null;
    options?: Record<string, unknown> | null;
  };
  editable?: boolean;
  blockKey?: string;
}>(), { editable: true });

const emit = defineEmits<(e: 'update:element', value: typeof props.element) => void>();

const containerRef = ref<HTMLElement | null>(null);
const editor = ref<Editor | null>(null);
const contentError = ref(false);

const { handleFileDrop, handleFilePaste, clearPendingUploads } = useTiptapFileUpload();

function initEditor(): void {
  if (editor.value || !props.editable) return;

  editor.value = new Editor({
    enableContentCheck: true,
    onContentError: ({ editor: currentEditor }) => { contentError.value = true; currentEditor.setEditable(false); },
    content: normalizeContent(props.element.json_content ?? null),
    editorProps: {
      attributes: {
        class: 'rc-prose-editing tracking-normal focus:outline-none min-h-[80px]',
      },
    },
    extensions: createFullExtensions({
      placeholder: $t('rich-content.enter_text'),
      onFileDrop: handleFileDrop,
      onFilePaste: handleFilePaste,
    }),
    editable: true,
    onUpdate: ({ editor: currentEditor }) => {
      if (contentError.value) return;
      emit('update:element', {
        ...props.element,
        json_content: currentEditor.getJSON(),
      });
    },
  });
}

onMounted(() => {
  if (props.editable) {
    initEditor();
  }
});

watch(() => props.editable, (val) => {
  if (val) {
    initEditor();
  }
  else if (editor.value) {
    editor.value.destroy();
    editor.value = null;
  }
});

watch(() => props.element.json_content, (newContent) => {
  if (!editor.value) return;
  // Heading ids are assigned document-wide by the parent; re-setting content for them alone moves the caret.
  const withoutIds = (value: unknown) => JSON.stringify(value, (key, child) => (key === 'id' ? undefined : child));
  const isSame = withoutIds(editor.value.getJSON()) === withoutIds(newContent);
  if (!isSame) {
    editor.value.commands.setContent(normalizeContent(newContent ?? null), { emitUpdate: false });
  }
});

onBeforeUnmount(() => {
  clearPendingUploads();
  editor.value?.destroy();
  editor.value = null;
});
</script>
