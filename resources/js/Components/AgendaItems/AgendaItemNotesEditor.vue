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
        <template v-if="!linkOpen">
          <button type="button" :class="btnClass(editor.isActive('bold'))" :title="$t('Paryškinti')" @mousedown.prevent="toggle('toggleBold')">
            <Bold class="h-4 w-4" />
          </button>
          <button type="button" :class="btnClass(editor.isActive('italic'))" :title="$t('Kursyvas')" @mousedown.prevent="toggle('toggleItalic')">
            <Italic class="h-4 w-4" />
          </button>
          <button type="button" :class="btnClass(editor.isActive('strike'))" :title="$t('Perbraukti')" @mousedown.prevent="toggle('toggleStrike')">
            <Strikethrough class="h-4 w-4" />
          </button>
          <button type="button" :class="btnClass(editor.isActive('link'))" :title="$t('Nuoroda')" @mousedown.prevent="openLink">
            <LinkIcon class="h-4 w-4" />
          </button>
        </template>
        <template v-else>
          <input
            ref="linkInput"
            v-model="linkUrl"
            type="url"
            class="h-7 w-44 rounded-md border border-zinc-200 bg-transparent px-2 text-xs focus:outline-none dark:border-zinc-700"
            :placeholder="$t('Įklijuokite nuorodą…')"
            @keydown.enter.prevent="applyLink"
            @keydown.esc.prevent="closeLink"
          >
          <button type="button" :class="btnClass(false)" :title="$t('Nuoroda')" @mousedown.prevent="applyLink">
            <Check class="h-4 w-4" />
          </button>
        </template>
      </div>
    </BubbleMenu>

    <EditorContent :editor />
  </div>
</template>

<script setup lang="ts">
import { type Component, nextTick, onBeforeUnmount, ref } from 'vue';
import { Extension } from '@tiptap/core';
import { EditorContent, useEditor, VueRenderer } from '@tiptap/vue-3';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import Collaboration from '@tiptap/extension-collaboration';
import CollaborationCaret from '@tiptap/extension-collaboration-caret';
import { TaskItem, TaskList } from '@tiptap/extension-list';
import Mention from '@tiptap/extension-mention';
import { Placeholder } from '@tiptap/extensions';
import { StarterKit } from '@tiptap/starter-kit';
import { Suggestion } from '@tiptap/suggestion';
import { computePosition, flip, offset, shift } from '@floating-ui/dom';
import { trans as $t } from 'laravel-vue-i18n';
import { Bold, Check, Italic, Link as LinkIcon, Strikethrough } from 'lucide-vue-next';
import type { Awareness } from 'y-protocols/awareness';
import type * as Y from 'yjs';

import AgendaItemNotesMentionList from '@/Components/AgendaItems/AgendaItemNotesMentionList.vue';
import AgendaItemNotesSlashMenu from '@/Components/AgendaItems/AgendaItemNotesSlashMenu.vue';
import { buildSlashCommandItems } from '@/Components/AgendaItems/notesSlashCommands';
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
const linkUrl = ref('');
const linkInput = ref<HTMLInputElement | null>(null);

/**
 * Shared suggestion popup renderer (used by both the "@" mention and "/" slash
 * menus): mounts a Vue list component with VueRenderer and positions it with
 * floating-ui (no tippy dependency). The list component must expose `onKeyDown`.
 */
function createSuggestionRenderer(listComponent: Component) {
  return () => {
    let component: VueRenderer | null = null;
    let dropdown: HTMLElement | null = null;

    const reposition = (clientRect?: (() => DOMRect | null) | null) => {
      if (!dropdown || !clientRect) {
        return;
      }
      const virtual = { getBoundingClientRect: () => clientRect() as DOMRect };
      void computePosition(virtual, dropdown, {
        placement: 'bottom-start',
        strategy: 'fixed',
        middleware: [offset(6), flip(), shift({ padding: 8 })],
      }).then(({ x, y }) => {
        if (dropdown) {
          Object.assign(dropdown.style, { left: `${x}px`, top: `${y}px` });
        }
      });
    };

    return {
      onStart: (sprops: any) => {
        component = new VueRenderer(listComponent, { props: sprops, editor: sprops.editor });
        dropdown = component.element as HTMLElement;
        dropdown.style.position = 'fixed';
        dropdown.style.zIndex = '9999';
        document.body.appendChild(dropdown);
        reposition(sprops.clientRect);
      },
      onUpdate: (sprops: any) => {
        component?.updateProps(sprops);
        reposition(sprops.clientRect);
      },
      onKeyDown: (sprops: any) => {
        if (sprops.event.key === 'Escape') {
          return true;
        }
        return (component?.ref as any)?.onKeyDown(sprops) ?? false;
      },
      onExit: () => {
        dropdown?.remove();
        component?.destroy();
        component = null;
        dropdown = null;
      },
    };
  };
}

/** "@" → mention the meeting's student representatives. */
const mentionSuggestion = {
  char: '@',
  items: ({ query }: { query: string }) => {
    const q = query.toLowerCase();
    return props.representatives
      .filter(user => user.name.toLowerCase().includes(q))
      .slice(0, 8);
  },
  render: createSuggestionRenderer(AgendaItemNotesMentionList),
};

/** "/" → insert a block (heading / lists / checklist). */
const SlashCommands = Extension.create({
  name: 'notesSlashCommands',
  addProseMirrorPlugins() {
    return [
      Suggestion({
        editor: this.editor,
        char: '/',
        startOfLine: false,
        items: ({ query }: { query: string }) => {
          const q = query.toLowerCase();
          return buildSlashCommandItems().filter(item => item.title.toLowerCase().includes(q));
        },
        command: ({ editor: instance, range, props: item }: any) => {
          item.command({ editor: instance, range });
        },
        render: createSuggestionRenderer(AgendaItemNotesSlashMenu),
      }),
    ];
  },
});

const editor = useEditor({
  editable: props.editable,
  extensions: [
    // Collaboration provides its own undo/redo history, so the StarterKit
    // history MUST be disabled or the two fight over the document. The feature
    // set is intentionally minimal (a single H2 heading level, no quote/code/HR).
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
        HTMLAttributes: { class: 'text-vusa-red underline font-medium' },
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
      placeholder: props.placeholder || $t('Rašykite bendras pastabas…'),
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

function bubbleShouldShow({ editor: instance, state }: any): boolean {
  return (instance.isFocused || linkOpen.value) && !state.selection.empty;
}

// --- Button helpers ---

function btnClass(active: boolean): string[] {
  return [
    'flex h-7 w-7 items-center justify-center rounded-md transition-colors',
    active
      ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
      : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
  ];
}

type ChainCommand = 'toggleBold' | 'toggleItalic' | 'toggleStrike';

function toggle(command: ChainCommand): void {
  const chain = editor.value?.chain().focus();
  (chain as any)?.[command]().run();
}

// --- Link flow (inline input inside the bubble) ---

function openLink(): void {
  linkUrl.value = editor.value?.getAttributes('link').href ?? '';
  linkOpen.value = true;
  nextTick(() => linkInput.value?.focus());
}

function applyLink(): void {
  const url = linkUrl.value.trim();
  const chain = editor.value?.chain().focus().extendMarkRange('link');
  if (url) {
    chain?.setLink({ href: url }).run();
  }
  else {
    chain?.unsetLink().run();
  }
  closeLink();
}

function closeLink(): void {
  linkOpen.value = false;
  linkUrl.value = '';
  editor.value?.chain().focus().run();
}

onBeforeUnmount(() => {
  // Destroy only the editor view; the shared Y.Doc + awareness live in the
  // composable so re-mounting (sidebar ↔ dialog) preserves all content.
  editor.value?.destroy();
});
</script>

<style scoped>
.notes-menu {
  display: flex;
  align-items: center;
  gap: 0.125rem;
  border-radius: 0.5rem;
  border: 1px solid rgb(228 228 231);
  background: var(--popover, #fff);
  padding: 0.2rem;
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
}

:global(.dark) .notes-menu {
  border-color: rgb(63 63 70);
  background: rgb(24 24 27);
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
  border-radius: 0.3rem;
  padding: 0.05rem 0.3rem;
  font-weight: 600;
  color: var(--vusa-red, #bd2835);
  background: rgb(189 40 53 / 0.1);
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
  color: rgb(161 161 170);
}
</style>
