<template>
  <div ref="containerRef" class="relative">
    <template v-if="editable">
      <!-- Smart Floating & Sticky Toolbar above the active paragraph -->
      <RCSmartTiptapToolbar
        v-if="editor"
        :editor
        :container-ref
      />

      <!-- Selection, link, table and image menus — the same ones a form field gets. -->
      <TiptapContextMenus v-if="editor" :editor image-menu />

      <!-- Live prose editing canvas. `rc-prose-editing` goes on the ProseMirror root
           itself (via editorProps below), not on a wrapper: the shared prose block's
           flow rules are direct-child selectors (`> * + *`, `> h2`), and EditorContent
           mounts the root two levels below any wrapper — the same pattern
           TiptapEditor.vue uses for its `prose-style` prop. -->
      <EditorContent :editor />
    </template>

    <template v-else>
      <!-- Server-rendered HTML or unsaved live json_content for clean preview -->
      <div v-if="element.html" class="rc-prose" v-html="element.html" />
      <RichContentTiptapHTML v-else-if="element.json_content" :json_content="element.json_content" />
      <div v-else-if="element.html === null" class="text-sm italic text-muted-foreground">
        {{ $t('Turinio nepavyko atvaizduoti') }}
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import { trans as $t } from 'laravel-vue-i18n';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';

import RCSmartTiptapToolbar from '@/Components/TipTap/RCSmartTiptapToolbar.vue';
import TiptapContextMenus from '@/Components/TipTap/TiptapContextMenus.vue';
import { createFullExtensions } from '@/Components/TipTap/extensions/presets';
import '@/Components/TipTap/tiptap-base.css';

const props = defineProps<{
  element: {
    id?: number;
    type?: string;
    html?: string | null;
    json_content?: Record<string, unknown> | null;
    options?: Record<string, unknown> | null;
  };
  editable?: boolean;
  blockKey?: string;
}>();

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
