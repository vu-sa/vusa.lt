<template>
  <div
    class="tiptap-editor"
    :class="[
      `tiptap-editor--${preset}`,
      isFramed && [
        'tiptap-editor--framed border border-border bg-background transition-colors',
        'focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20',
      ],
    ]"
  >
    <template v-if="editor && preset !== 'minimal'">
      <TiptapContextMenus
        ref="contextMenus"
        :editor
        :show-bold
        :disable-links
        :text-bubble="!showToolbar || tools.headingLevels.length > 0"
        :image-menu="preset === 'full'"
      />
    </template>

    <div
      v-if="editor && showToolbar"
      :class="[
        'tiptap-toolbar flex flex-nowrap items-center gap-0.5 overflow-x-auto p-1',
        isFramed ? 'border-b border-border bg-secondary/50' : 'border border-border bg-secondary/50',
      ]"
      role="toolbar"
      :aria-label="$t('rich-content.toolbar')"
    >
      <Select
        v-if="tools.headingLevels.length"
        :model-value="commands.currentHeadingLevel.value"
        @update:model-value="commands.setHeadingLevel($event as string)"
      >
        <SelectTrigger size="sm" class="h-8 w-28 shrink-0 border-transparent bg-transparent text-xs shadow-none hover:bg-accent" data-testid="tiptap-heading-select" :aria-label="$t('rich-content.text_style')">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="paragraph">
            {{ headingLevelLabel('paragraph') }}
          </SelectItem>
          <SelectItem v-for="level in tools.headingLevels" :key="level" :value="String(level)">
            {{ headingLevelLabel(level) }}
          </SelectItem>
        </SelectContent>
      </Select>
      <Separator v-if="tools.headingLevels.length" orientation="vertical" class="mx-0.5 h-5" />

      <TiptapFormattingButtons :editor :show-bold />

      <TiptapToolButton
        v-if="linksEnabled"
        toggle
        data-testid="tiptap-link"
        :active="editor.isActive('link')"
        :label="$t('rich-content.link')"
        @click="contextMenus?.openLinkDialog()"
      >
        <IFluentLink24Regular />
      </TiptapToolButton>

      <template v-if="tools.lists">
        <Separator orientation="vertical" class="mx-0.5 h-5" />
        <TiptapToolButton
          toggle
          :active="editor.isActive('bulletList')"
          :label="$t('rich-content.bullet_list')"
          @click="editor.chain().focus().toggleBulletList().run()"
        >
          <IFluentTextBulletListLtr20Regular />
        </TiptapToolButton>
        <TiptapToolButton
          toggle
          :active="editor.isActive('orderedList')"
          :label="$t('rich-content.ordered_list')"
          @click="editor.chain().focus().toggleOrderedList().run()"
        >
          <IFluentTextNumberListLtr20Regular />
        </TiptapToolButton>
      </template>

      <template v-if="hasInsertTools(tools) || hasMoreMenu">
        <Separator orientation="vertical" class="mx-0.5 h-5" />
        <TiptapInsertMenu v-if="hasInsertTools(tools)" :editor :tools />
        <TiptapMoreMenu v-if="hasMoreMenu" :editor :tools show-history />
      </template>

      <div v-if="tools.lists" class="ml-auto hidden items-center gap-0.5 pl-1 sm:flex">
        <TiptapToolButton
          :label="$t('rich-content.undo')"
          :disabled="!editor.can().undo()"
          @click="editor.chain().focus().undo().run()"
        >
          <IFluentArrowUndo20Regular />
        </TiptapToolButton>
        <TiptapToolButton
          :label="$t('rich-content.redo')"
          :disabled="!editor.can().redo()"
          @click="editor.chain().focus().redo().run()"
        >
          <IFluentArrowRedo20Regular />
        </TiptapToolButton>
      </div>
    </div>

    <div
      :class="[
        'tiptap-content overflow-hidden',
        isFramed ? '' : 'border border-border bg-background',
        { 'tiptap-content--prose': proseStyle },
      ]"
    >
      <EditorContent :editor />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, useTemplateRef } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Extension } from '@tiptap/core';
import { trans as $t } from 'laravel-vue-i18n';

import './accessible-image-commands.d.ts';

import { type EditorPreset, getExtensionsForPreset } from './extensions/presets';
import { useTiptapFileUpload } from './composables/useTiptapFileUpload';
import { useTiptapCommands } from './composables/useTiptapCommands';
import { normalizeContent } from './normalizeContent';
import { headingLevelLabel } from './toolbarOptions';
import { hasInsertTools, profileForPreset, toolsFor, type ToolbarProfile } from './toolbarProfiles';
import TiptapContextMenus from './TiptapContextMenus.vue';
import TiptapFormattingButtons from './TiptapFormattingButtons.vue';
import TiptapInsertMenu from './TiptapInsertMenu.vue';
import TiptapMoreMenu from './TiptapMoreMenu.vue';
import TiptapToolButton from './TiptapToolButton.vue';

import { Separator } from '@/Components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { latinizeId } from '@/Utils/String';
import IFluentArrowRedo20Regular from '~icons/fluent/arrow-redo20-regular';
import IFluentArrowUndo20Regular from '~icons/fluent/arrow-undo20-regular';
import IFluentLink24Regular from '~icons/fluent/link24-regular';
import IFluentTextBulletListLtr20Regular from '~icons/fluent/text-bullet-list-ltr20-regular';
import IFluentTextNumberListLtr20Regular from '~icons/fluent/text-number-list-ltr20-regular';

import './tiptap-base.css';

const props = withDefaults(defineProps<{
  /** Extension schema: 'minimal' | 'marks' | 'compact' | 'full' */
  preset?: EditorPreset;
  /** Controls the toolbar offers; defaults to everything the preset supports. */
  tools?: ToolbarProfile;
  /** Content (JSON object or HTML string) */
  modelValue: string | Record<string, unknown> | null;
  /** Output HTML instead of JSON */
  html?: boolean;
  disableTables?: boolean;
  disableLinks?: boolean;
  maxCharacters?: number;
  placeholder?: string;
  /** Keep controls next to a selection instead of reserving space above the editor. */
  toolbar?: 'inline' | 'bubble';
  /** Hide bold where the surrounding component already enforces a bold display style. */
  showBold?: boolean;
  /**
   * Style the editing surface with `.rc-prose-editing` — the same flow/heading-scale
   * rules as the published rich-content output — so what you type looks like what renders.
   */
  proseStyle?: boolean;
  /**
   * One hairline field box with a tinted toolbar row. Defaults on for an inline toolbar;
   * canvas and comment editors (bubble toolbar, `minimal`) stay unframed.
   */
  framed?: boolean;
}>(), {
  preset: 'full',
  tools: undefined,
  html: false,
  disableTables: false,
  toolbar: 'inline',
  showBold: true,
  proseStyle: false,
  framed: undefined,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | Record<string, unknown> | null];
}>();

const contextMenus = useTemplateRef<InstanceType<typeof TiptapContextMenus>>('contextMenus');

const showToolbar = computed(() => props.toolbar !== 'bubble' && props.preset !== 'minimal');
const isFramed = computed(() => props.framed ?? showToolbar.value);
const tools = computed(() => toolsFor(props.tools ?? profileForPreset(props.preset), { disableTables: props.disableTables }));
const hasMoreMenu = computed(() => {
  const t = tools.value;
  return t.lists || t.blockquote || t.clearFormatting || t.alignment || t.tag || t.headingStyle;
});
const linksEnabled = computed(() => !props.disableLinks && Boolean(editor.value?.schema.marks.link));

const { handleFileDrop, handleFilePaste, clearPendingUploads } = useTiptapFileUpload();

/** ⌘K / Ctrl+K opens the link dialog, as in most editors. */
const LinkShortcut = Extension.create({
  name: 'linkDialogShortcut',
  addKeyboardShortcuts() {
    return {
      'Mod-k': () => {
        if (!linksEnabled.value) return false;
        contextMenus.value?.openLinkDialog();
        return true;
      },
    };
  },
});

const extensions = [
  ...getExtensionsForPreset(props.preset, {
    placeholder: props.placeholder ?? $t('rich-content.text_placeholder'),
    maxCharacters: props.maxCharacters ?? null,
    disableTables: props.disableTables,
    disableLinks: props.disableLinks,
    onFileDrop: props.preset === 'full' ? handleFileDrop : undefined,
    onFilePaste: props.preset === 'full' ? handleFilePaste : undefined,
  }),
  LinkShortcut,
];

const editor = useEditor({
  editorProps: {
    attributes: {
      class: ['focus:outline-none w-full min-h-[80px]', isFramed.value ? 'px-4 py-3 text-sm leading-relaxed' : 'px-3 py-2', props.proseStyle ? 'rc-prose-editing tracking-normal' : ''].filter(Boolean).join(' '),
    },
  },
  extensions,
  content: normalizeContent(props.modelValue),
  onUpdate: () => {
    if (props.preset === 'full') {
      updateHeadingIds();
    }

    nextTick(() => {
      if (props.html) {
        emit('update:modelValue', editor.value?.getHTML() ?? null);
      }
      else {
        emit('update:modelValue', editor.value?.getJSON() ?? null);
      }
    });
  },
});

const commands = useTiptapCommands(editor);

// Heading ID generation for TOC support
function updateHeadingIds() {
  if (!editor.value) return;

  const innerHeadings: { level: number; text: string; id: string }[] = [];
  const transaction = editor.value.state.tr;

  editor.value.state.doc.descendants((node, pos) => {
    if (node.type.name === 'heading') {
      let id = latinizeId(node.textContent);

      let counter = 1;
      while (innerHeadings.some(heading => heading.id === id)) {
        id = `${latinizeId(node.textContent)}-${counter}`;
        counter++;
      }

      if (node.attrs.id !== id) {
        transaction.setNodeAttribute(pos, 'id', id);
      }

      innerHeadings.push({
        level: node.attrs.level,
        text: node.textContent,
        id,
      });
    }
  });

  transaction.setMeta('addToHistory', false);
  transaction.setMeta('preventUpdate', true);

  editor.value.view.dispatch(transaction);
}

onBeforeUnmount(() => {
  clearPendingUploads();
  editor.value?.destroy();
});
</script>

<style scoped>
.tiptap-editor {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.tiptap-editor--framed {
  gap: 0;
}

.tiptap-content {
  min-height: 80px;
  max-height: 400px;
  overflow-y: auto;
}

/* Rich-content blocks get more room — a keyhole editing viewport for a full article
   discourages exactly the long-form writing this preset exists to support. */
.tiptap-content--prose {
  max-height: min(70vh, 40rem);
}

.tiptap-editor--full .tiptap-content {
  min-height: 120px;
}

.tiptap-editor--minimal .tiptap-content {
  min-height: 60px;
}
</style>
