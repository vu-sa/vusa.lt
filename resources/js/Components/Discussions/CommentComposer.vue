<template>
  <div class="comment-composer">
    <div class="border border-border bg-background focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20">
      <CommentToolbar v-if="editor" ref="toolbar" :editor :discover-formatting />
      <EditorContent :editor />
      <div class="flex flex-wrap items-center justify-between gap-2 border-t border-border px-2 py-1">
        <div class="flex shrink-0 items-center gap-1">
          <Button data-testid="comment-mention" type="button" variant="ghost" size="xs" voice="sentence" class="text-muted-foreground" @click="insertMention">
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
import { computed, onBeforeUnmount, ref, useTemplateRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import Mention from '@tiptap/extension-mention';
import type { Editor } from '@tiptap/core';
import { PluginKey } from '@tiptap/pm/state';
import { trans as $t } from 'laravel-vue-i18n';
import { AtSign, Bell, Loader2, Send } from '@lucide/vue';

import CommentToolbar from '@/Components/Discussions/CommentToolbar.vue';
import { createCommentExtensions } from '@/Components/TipTap/extensions/presets';
import { createSuggestionRenderer } from '@/Components/TipTap/composables/createSuggestionRenderer';
import './comment-content.css';
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
  discoverFormatting?: boolean;
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

const toolbar = useTemplateRef<InstanceType<typeof CommentToolbar>>('toolbar');
const mentionPluginKey = new PluginKey('commentMention');

const editor = useEditor({
  autofocus: props.autofocus,
  content: props.content,
  extensions: [
    ...createCommentExtensions({ placeholder: props.placeholder || $t('Parašyk komentarą…') }),
    Mention.configure({
      HTMLAttributes: { class: 'comment-mention' },
      suggestion: {
        char: '@',
        pluginKey: mentionPluginKey,
        items: ({ query }: { query: string }) => {
          const q = query.toLowerCase();
          return props.mentionables
            .filter(user => user.name.toLowerCase().includes(q))
            .slice(0, 8);
        },
        render: createSuggestionRenderer<MentionableUser, { id: string; label: string }>(CommentMentionList, mentionPluginKey),
      },
    }),
  ],
  editorProps: {
    handleKeyDown: (_view, event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        toolbar.value?.openLink();
        return true;
      }
      return false;
    },
    attributes: {
      class: 'comment-content focus:outline-none px-3.5 py-2.5 min-h-10 text-foreground',
    },
  },
  onCreate: ({ editor: instance }) => updateState(instance),
  onUpdate: ({ editor: instance }) => updateState(instance),
});

function updateState(instance: Editor) {
  isEmpty.value = instance.isEmpty;
  const found: { id: string; label: string }[] = [];
  instance.state.doc.descendants((node) => {
    if (node.type.name === 'mention') {
      found.push({ id: String(node.attrs.id), label: String(node.attrs.label ?? node.attrs.id) });
    }
  });
  mentions.value = found;
}

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
