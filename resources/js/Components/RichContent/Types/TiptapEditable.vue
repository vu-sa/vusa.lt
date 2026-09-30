<template>
  <div ref="containerRef" class="relative">
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
import { createFullExtensions } from '@/Components/TipTap/extensions/presets';
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

function initEditor(): void {
  if (editor.value || !props.editable) return;

  editor.value = new Editor({
    content: props.element.json_content ?? {},
    editorProps: {
      attributes: {
        class: 'rc-prose-editing tracking-normal focus:outline-none min-h-[80px]',
      },
    },
    extensions: createFullExtensions({
      placeholder: $t('rich-content.enter_text'),
    }),
    editable: true,
    onUpdate: ({ editor: currentEditor }) => {
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
  const isSame = JSON.stringify(editor.value.getJSON()) === JSON.stringify(newContent);
  if (!isSame && !editor.value.isFocused) {
    editor.value.commands.setContent(newContent ?? {}, false);
  }
});

onBeforeUnmount(() => {
  editor.value?.destroy();
  editor.value = null;
});
</script>
