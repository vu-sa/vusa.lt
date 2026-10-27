<template>
  <div class="agenda-notes-editor text-sm">
    <!-- Selection bubble: inline marks. Only while the editor is focused. -->
    <BubbleMenu
      v-if="editor"
      :editor
      plugin-key="notesBubble"
      :should-show="bubbleShouldShow"
      :options="menuOptions"
    >
      <div data-agenda-notes-menu class="notes-menu">
        <TiptapFormattingButtons :editor :marks="['bold', 'italic', 'strike']" />
        <TiptapLinkPopover v-model:open="linkOpen" :editor />
      </div>
    </BubbleMenu>

    <EditorContent :editor />
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { Extension } from '@tiptap/core';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import Collaboration from '@tiptap/extension-collaboration';
import CollaborationCaret from '@tiptap/extension-collaboration-caret';
import { TaskItem, TaskList } from '@tiptap/extension-list';
import Mention from '@tiptap/extension-mention';
import { Placeholder } from '@tiptap/extensions';
import { StarterKit } from '@tiptap/starter-kit';
import { Suggestion } from '@tiptap/suggestion';
import { trans as $t } from 'laravel-vue-i18n';
import { PluginKey } from '@tiptap/pm/state';
import type { Editor } from '@tiptap/core';
import type { EditorState } from '@tiptap/pm/state';
import type { Awareness } from 'y-protocols/awareness';
import type * as Y from 'yjs';

import TiptapFormattingButtons from '@/Components/TipTap/TiptapFormattingButtons.vue';
import TiptapLinkPopover from '@/Components/TipTap/TiptapLinkPopover.vue';
import { createSuggestionRenderer } from '@/Components/TipTap/composables/createSuggestionRenderer';
import AgendaItemNotesMentionList from '@/Components/AgendaItems/AgendaItemNotesMentionList.vue';
import AgendaItemNotesSlashMenu from '@/Components/AgendaItems/AgendaItemNotesSlashMenu.vue';
import { buildSlashCommandItems, type SlashCommandItem } from '@/Components/AgendaItems/notesSlashCommands';
import type { NotesMentionUser } from '@/Composables/useAgendaItemNotes';

const props = withDefaults(defineProps<{
  doc: Y.Doc;
  awareness: Awareness;
  userName: string;
  userColor: string;
  representatives?: NotesMentionUser[];
  editable?: boolean;
  placeholder?: string;
}>(), {
  representatives: () => [],
  editable: true,
  placeholder: '',
});

const emit = defineEmits<{ htmlChange: [html: string] }>();

const menuOptions = { strategy: 'fixed' as const };

const linkOpen = ref(false);
const mentionPluginKey = new PluginKey('agendaMention');
const slashPluginKey = new PluginKey('agendaSlash');

/** "@" → mention the meeting's student representatives. */
const mentionSuggestion = {
  char: '@',
  pluginKey: mentionPluginKey,
  items: ({ query }: { query: string }) => {
    const q = query.toLowerCase();
    return props.representatives
      .filter(user => user.name.toLowerCase().includes(q))
      .slice(0, 8);
  },
  render: createSuggestionRenderer<NotesMentionUser, { id: string; label: string }>(AgendaItemNotesMentionList, mentionPluginKey),
};

/** "/" → insert a block (heading / lists / checklist). */
const SlashCommands = Extension.create({
  name: 'notesSlashCommands',
  addProseMirrorPlugins() {
    return [
      Suggestion<SlashCommandItem, SlashCommandItem>({
        pluginKey: slashPluginKey,
        editor: this.editor,
        char: '/',
        startOfLine: false,
        items: ({ query }: { query: string }) => {
          const q = query.toLowerCase();
          return buildSlashCommandItems().filter(item => item.title.toLowerCase().includes(q));
        },
        command: ({ editor: instance, range, props: item }) => {
          item.command({ editor: instance, range });
        },
        render: createSuggestionRenderer<SlashCommandItem, SlashCommandItem>(AgendaItemNotesSlashMenu, slashPluginKey),
      }),
    ];
  },
});

const editor = useEditor({
  editable: props.editable,
  extensions: [
    // Yjs owns history; StarterKit history would conflict with collaborative undo.
    StarterKit.configure({
      undoRedo: false,
      codeBlock: false,
      code: false,
      horizontalRule: false,
      blockquote: false,
      underline: false,
      heading: { levels: [2] },
      link: {
        openOnClick: false,
        HTMLAttributes: { class: 'text-brand underline font-medium' },
      },
    }),
    TaskList,
    TaskItem.configure({ nested: true }),
    SlashCommands,
    Mention.configure({
      HTMLAttributes: { class: 'notes-mention' },
      suggestion: mentionSuggestion,
    }),
    Placeholder.configure({
      placeholder: props.placeholder || $t('Rašyk bendras pastabas…'),
    }),
    Collaboration.configure({ document: props.doc }),
    CollaborationCaret.configure({
      provider: { awareness: props.awareness },
      user: { name: props.userName, color: props.userColor },
    }),
  ],
  editorProps: {
    attributes: {
      class: 'focus:outline-none px-3 py-2 min-h-24 leading-relaxed',
    },
  },
  onUpdate: ({ editor: instance }) => {
    emit('htmlChange', instance.getHTML());
  },
});

// --- Menu visibility (focus-gated so menus never linger when the editor is not active) ---

function bubbleShouldShow({ editor: instance, state }: { editor: Editor; state: EditorState }): boolean {
  return linkOpen.value || (instance.isFocused && !state.selection.empty);
}

onBeforeUnmount(() => {
  // The parent owns the shared Y.Doc and awareness across remounts.
  editor.value?.destroy();
});
</script>

<style scoped>
.notes-menu {
  display: flex;
  align-items: center;
  gap: 0.125rem;
  border: 1px solid var(--border);
  background: var(--popover);
  padding: 0.2rem;
}

/* Tailwind's reset strips list markers and there is no typography plugin, so the
 * editor styles its own content. */
.agenda-notes-editor :deep(.ProseMirror) > * + * {
  margin-top: 0.4rem;
}

.agenda-notes-editor :deep(.ProseMirror h2) {
  font-size: 1rem;
  font-weight: 600;
  margin-top: 0.75rem;
  margin-bottom: 0.25rem;
}

.agenda-notes-editor :deep(.ProseMirror ul),
.agenda-notes-editor :deep(.ProseMirror ol) {
  padding-left: 1.25rem;
  margin: 0.25rem 0;
}

.agenda-notes-editor :deep(.ProseMirror ul) {
  list-style: disc;
}

.agenda-notes-editor :deep(.ProseMirror ol) {
  list-style: decimal;
}

.agenda-notes-editor :deep(.ProseMirror li) {
  margin: 0.1rem 0;
}

.agenda-notes-editor :deep(.ProseMirror li > p) {
  margin: 0;
}

/* Task list (checkboxes) */
.agenda-notes-editor :deep(.ProseMirror ul[data-type='taskList']) {
  list-style: none;
  padding-left: 0.25rem;
}

.agenda-notes-editor :deep(.ProseMirror ul[data-type='taskList'] li) {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.agenda-notes-editor :deep(.ProseMirror ul[data-type='taskList'] li > label) {
  margin-top: 0.2rem;
  flex: 0 0 auto;
  user-select: none;
}

.agenda-notes-editor :deep(.ProseMirror ul[data-type='taskList'] li > div) {
  flex: 1 1 auto;
}

.agenda-notes-editor :deep(.ProseMirror ul[data-type='taskList'] input[type='checkbox']) {
  cursor: pointer;
}

/* @mention chip */
.agenda-notes-editor :deep(.notes-mention) {
  padding: 0.05rem 0.3rem;
  font-weight: 600;
  color: var(--brand);
  background: color-mix(in oklab, var(--brand) 10%, transparent);
}

/* Remote collaborators' carets + labels (CollaborationCaret renders these;
 * border/background colours are applied inline from the user colour). */
.agenda-notes-editor :deep(.collaboration-carets__caret) {
  position: relative;
  margin-left: -1px;
  margin-right: -1px;
  border-left: 1px solid;
  border-right: 1px solid;
  word-break: normal;
  pointer-events: none;
}

.agenda-notes-editor :deep(.collaboration-carets__label) {
  position: absolute;
  top: -1.4em;
  left: -1px;
  font-size: 11px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
  white-space: nowrap;
  user-select: none;
  pointer-events: none;
  color: #fff;
  padding: 0.05rem 0.3rem;
  border-radius: 0.25rem 0.25rem 0.25rem 0;
}

.agenda-notes-editor :deep(.collaboration-carets__selection) {
  border-radius: 0.15rem;
  mix-blend-mode: multiply;
}

.agenda-notes-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
  color: var(--muted-foreground);
}
</style>
