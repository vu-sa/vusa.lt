<template>
  <div class="comment-composer">
    <div class="border border-border bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20">
      <EditorContent :editor />
      <div class="flex items-center justify-between gap-2 border-t border-border px-2 py-1">
        <div class="flex shrink-0 items-center gap-1">
          <Button type="button" variant="ghost" size="xs" voice="sentence" class="text-muted-foreground" @click="insertMention">
            <AtSign class="size-3.5" />
            {{ $t('Paminėti') }}
          </Button>
          <template v-if="$slots.leading">
            <Separator orientation="vertical" class="mx-1 bg-border data-[orientation=vertical]:h-5" />
            <slot name="leading" />
          </template>
        </div>
        <p
          v-if="notifiedNames.length"
          class="flex min-w-0 flex-1 items-center justify-end gap-1 pr-2 text-xs text-muted-foreground"
          :title="`${$t('Pranešimą apie paminėjimą gaus:')} ${notifiedNames.join(', ')}`"
          data-testid="mention-notice"
        >
          <Bell class="size-3.5 shrink-0" aria-hidden="true" />
          <span class="sr-only sm:not-sr-only shrink-0">{{ $t('Pranešimą gaus:') }}</span>
          <span class="truncate font-medium text-foreground">{{ notifiedNames.join(', ') }}</span>
        </p>
        <div class="flex shrink-0 items-center gap-1.5">
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
import { type Component, computed, onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { EditorContent, useEditor, VueRenderer } from '@tiptap/vue-3';
import Mention from '@tiptap/extension-mention';
import { Placeholder } from '@tiptap/extensions';
import { StarterKit } from '@tiptap/starter-kit';
import type { SuggestionKeyDownProps, SuggestionProps } from '@tiptap/suggestion';
import { computePosition, flip, offset, shift } from '@floating-ui/dom';
import { trans as $t } from 'laravel-vue-i18n';
import { AtSign, Bell, Loader2, Send } from '@lucide/vue';

import CommentMentionList from '@/Components/Discussions/CommentMentionList.vue';
import { Button } from '@/Components/ui/button';
import { Separator } from '@/Components/ui/separator';
import type { MentionableUser } from '@/Types/discussions';

const props = withDefaults(defineProps<{
  mentionables?: MentionableUser[];
  placeholder?: string;
  submitLabel?: string;
  submitting?: boolean;
  showCancel?: boolean;
  autofocus?: boolean;
  content?: string;
  /** Edits never re-send mention notifications, so don't promise one. */
  quietMentions?: boolean;
}>(), {
  mentionables: () => [],
  submitting: false,
  showCancel: false,
  autofocus: false,
  content: '',
});

const emit = defineEmits<{ submit: [html: string]; cancel: [] }>();

const isEmpty = ref(props.content.trim() === '');
const mentions = ref<{ id: string; label: string }[]>([]);
const currentUserId = computed(() => (usePage().props.auth as { user?: { id: string } } | undefined)?.user?.id);

// The author is never notified of their own mention.
const notifiedNames = computed(() => (props.quietMentions
  ? []
  : [...new Map(mentions.value.filter(mention => mention.id !== currentUserId.value).map(mention => [mention.id, mention.label])).values()]));

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
      onStart: (sprops: SuggestionProps<MentionableUser, { id: string; label: string }>) => {
        component = new VueRenderer(listComponent, { props: sprops, editor: sprops.editor });
        dropdown = component.element as HTMLElement;
        dropdown.style.position = 'fixed';
        dropdown.style.zIndex = '9999';
        document.body.appendChild(dropdown);
        reposition(sprops.clientRect);
      },
      onUpdate: (sprops: SuggestionProps<MentionableUser, { id: string; label: string }>) => {
        component?.updateProps(sprops);
        reposition(sprops.clientRect);
      },
      onKeyDown: (sprops: SuggestionKeyDownProps) => {
        if (sprops.event.key === 'Escape') {
          return true;
        }
        const mentionList = component?.ref as { onKeyDown?: (props: SuggestionKeyDownProps) => boolean } | null;

        return mentionList?.onKeyDown?.(sprops) ?? false;
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
        HTMLAttributes: { class: 'text-brand underline font-medium' },
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
      placeholder: props.placeholder || $t('Parašyk komentarą…'),
    }),
  ],
  editorProps: {
    attributes: {
      class: 'focus:outline-none px-3.5 py-2.5 min-h-10 text-foreground',
    },
  },
  onUpdate: ({ editor: instance }) => {
    isEmpty.value = instance.isEmpty;
    const found: { id: string; label: string }[] = [];
    instance.state.doc.descendants((node) => {
      if (node.type.name === 'mention') {
        found.push({ id: String(node.attrs.id), label: String(node.attrs.label ?? node.attrs.id) });
      }
    });
    mentions.value = found;
  },
});

function submit() {
  const instance = editor.value;
  if (!instance || instance.isEmpty || props.submitting) {
    return;
  }
  emit('submit', instance.getHTML());
}

/** The suggestion plugin only triggers on an `@` that starts a word, so pad it after other text. */
function insertMention() {
  const instance = editor.value;
  if (!instance) {
    return;
  }
  const { from } = instance.state.selection;
  const before = instance.state.doc.textBetween(Math.max(0, from - 1), from, '\n', '\n');
  const prefix = before === '' || /\s/.test(before) ? '' : ' ';
  instance.chain().focus().insertContent(`${prefix}@`).run();
}

/**
 * Clear the editor (called by the parent after a successful submit).
 */
function reset() {
  editor.value?.commands.clearContent(true);
  isEmpty.value = true;
  mentions.value = [];
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
  caret-color: var(--brand);
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
  color: var(--brand);
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
