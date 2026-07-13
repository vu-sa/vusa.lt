<template>
  <div class="comment-composer">
    <button
      v-if="collapsible && !isExpanded"
      type="button"
      class="w-full rounded-lg border border-transparent bg-transparent px-3.5 py-2.5 text-left transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900/50"
      @click="expand"
    >
      <span class="text-zinc-500 dark:text-zinc-400">{{ placeholder || $t('Parašykite komentarą…') }}</span>
    </button>
    <div
      v-else
      ref="composerRef"
      class="rounded-lg border border-zinc-200 bg-white focus-within:border-vusa-red/50 focus-within:ring-1 focus-within:ring-vusa-red/30 dark:border-zinc-700 dark:bg-zinc-900"
      @focusout="onFocusOut"
    >
      <EditorContent :editor="editor" />
      <div class="flex items-center justify-between gap-2 border-t border-zinc-100 px-2 py-1.5 dark:border-zinc-800">
        <div class="flex items-center gap-2">
          <span class="text-xs text-muted-foreground">{{ $t('Naudokite @ paminėti') }}</span>
          <slot name="leading" />
        </div>
        <div class="flex items-center gap-1.5">
          <Button v-if="showCancel" type="button" variant="ghost" size="sm" @click="$emit('cancel')">
            {{ $t('Atšaukti') }}
          </Button>
          <Button type="button" size="sm" :disabled="submitting || isEmpty" @click="submit">
            <Loader2 v-if="submitting" class="mr-1 h-3.5 w-3.5 animate-spin" />
            <Send v-else class="mr-1 h-3.5 w-3.5" />
            {{ submitLabel || $t('Komentuoti') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { type Component, computed, nextTick, onBeforeUnmount, ref } from 'vue';
import { EditorContent, useEditor, VueRenderer } from '@tiptap/vue-3';
import Mention from '@tiptap/extension-mention';
import { Placeholder } from '@tiptap/extensions';
import { StarterKit } from '@tiptap/starter-kit';
import { computePosition, flip, offset, shift } from '@floating-ui/dom';
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2, Send } from 'lucide-vue-next';

import CommentMentionList from '@/Components/Discussions/CommentMentionList.vue';
import { Button } from '@/Components/ui/button';
import type { MentionableUser } from '@/Types/discussions';

const props = withDefaults(defineProps<{
  mentionables?: MentionableUser[];
  placeholder?: string;
  submitLabel?: string;
  submitting?: boolean;
  showCancel?: boolean;
  autofocus?: boolean;
  content?: string;
  collapsible?: boolean;
}>(), {
  mentionables: () => [],
  submitting: false,
  showCancel: false,
  autofocus: false,
  content: '',
  collapsible: false,
});

const emit = defineEmits<{ submit: [html: string]; cancel: [] }>();

const isEmpty = ref(props.content.trim() === '');
const isExpanded = ref(!props.collapsible || props.autofocus || props.content.trim() !== '');
const composerRef = ref<HTMLDivElement | null>(null);

/**
 * Mount a Vue suggestion list with VueRenderer and position it with floating-ui
 * (mirrors the agenda notes editor pattern). The list must expose `onKeyDown`.
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

const editor = useEditor({
  autofocus: props.autofocus,
  content: props.content,
  extensions: [
    StarterKit.configure({
      codeBlock: false,
      code: false,
      horizontalRule: false,
      blockquote: false,
      heading: false,
      link: {
        openOnClick: false,
        HTMLAttributes: { class: 'text-vusa-red underline font-medium' },
      },
    }),
    Mention.configure({
      HTMLAttributes: { class: 'comment-mention' },
      suggestion: {
        char: '@',
        items: ({ query }: { query: string }) => {
          const q = query.toLowerCase();
          return props.mentionables
            .filter(user => user.name.toLowerCase().includes(q))
            .slice(0, 8);
        },
        render: createSuggestionRenderer(CommentMentionList),
      },
    }),
    Placeholder.configure({
      placeholder: props.placeholder || $t('Parašykite komentarą…'),
    }),
  ],
  editorProps: {
    attributes: {
      class: 'focus:outline-none px-3.5 py-2.5 min-h-12 text-zinc-800 dark:text-zinc-200',
    },
  },
  onUpdate: ({ editor: instance }) => {
    isEmpty.value = instance.isEmpty;
  },
});

function submit() {
  const instance = editor.value;
  if (!instance || instance.isEmpty || props.submitting) {
    return;
  }
  emit('submit', instance.getHTML());
}

function expand() {
  if (!props.collapsible) {
    return;
  }
  isExpanded.value = true;
  void nextTick(() => {
    editor.value?.commands.focus();
  });
}

function onFocusOut(event: FocusEvent) {
  if (!props.collapsible || !isExpanded.value) {
    return;
  }
  const target = event.relatedTarget as HTMLElement | null;
  // Keep the composer expanded when focus moves into a dialog (e.g. poll composer)
  // so the trigger stays mounted and the dialog can open properly.
  if (target?.closest('[role="dialog"]')) {
    return;
  }
  if (composerRef.value && (!target || !composerRef.value.contains(target)) && isEmpty.value) {
    isExpanded.value = false;
  }
}

/**
 * Clear the editor (called by the parent after a successful submit).
 */
function reset() {
  editor.value?.commands.clearContent(true);
  isEmpty.value = true;
  if (props.collapsible) {
    isExpanded.value = false;
  }
}

defineExpose({ reset });

onBeforeUnmount(() => {
  editor.value?.destroy();
});
</script>

<style scoped>
/* Make the writing surface feel deliberate rather than form-generic:
   comfortable size + line-height, crisp rendering (kerning/ligatures),
   softened text colour, and a branded caret + selection. */
.comment-composer :deep(.ProseMirror) {
  font-size: 0.9375rem; /* 15px */
  line-height: 1.65;
}

/* iOS Safari zooms the viewport when focusing a field smaller than 16px.
   Keep the comfortable 15px on larger screens but use 16px on phones to
   suppress the unwanted zoom (no accessibility cost — pinch-zoom still works). */
@media (max-width: 640px) {
  .comment-composer :deep(.ProseMirror) {
    font-size: 1rem; /* 16px */
  }
}

.comment-composer :deep(.ProseMirror) {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
  font-feature-settings: "kern", "liga", "calt";
  caret-color: var(--vusa-red, #bd2835);
}

.comment-composer :deep(.ProseMirror) ::selection {
  background: rgb(189 40 53 / 0.15);
}

.comment-composer :deep(.ProseMirror) > * + * {
  margin-top: 0.55rem;
}

.comment-composer :deep(.comment-mention) {
  border-radius: 0.3rem;
  padding: 0.05rem 0.3rem;
  font-weight: 600;
  color: var(--vusa-red, #bd2835);
  background: rgb(189 40 53 / 0.1);
}

.comment-composer :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
  color: rgb(161 161 170);
}

:global(.dark) .comment-composer :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  color: rgb(113 113 122);
}
</style>
