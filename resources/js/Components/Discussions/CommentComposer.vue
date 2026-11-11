<template>
  <div ref="root" class="comment-composer" data-slot="comment-composer" :data-expanded="expanded || undefined">
    <div
      :class="[
        'border bg-background transition-colors',
        expanded ? 'border-foreground/25 focus-within:border-foreground/45' : 'border-border hover:border-foreground/25',
      ]"
    >
      <!-- What is being written, and as whom -->
      <div v-if="expanded && (modes.length > 1 || duties.length)" class="flex flex-wrap items-stretch border-b border-border/70 px-1.5" data-slot="composer-header">
        <div v-if="modes.length > 1" class="flex shrink-0 items-stretch gap-0.5" role="tablist" :aria-label="$t('discussions.composer.modes')">
          <button
            v-for="option in modeOptions"
            :key="option.mode"
            type="button"
            role="tab"
            :aria-selected="mode === option.mode"
            :data-testid="`composer-mode-${option.mode}`"
            :class="[
              '-mb-px inline-flex h-9 items-center gap-1.5 border-b-2 px-2 text-xs font-semibold transition-colors pointer-coarse:min-h-11',
              mode === option.mode ? 'border-brand text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground',
            ]"
            @mousedown.prevent
            @click="setMode(option.mode)"
          >
            <component :is="option.icon" class="size-3.5" aria-hidden="true" />
            {{ option.label }}
          </button>
        </div>
        <!-- Above the tabs on a phone, so the active tab's rule stays on the bar's bottom edge. -->
        <div
          v-if="duties.length"
          :class="[
            'order-first flex w-full min-w-0 items-center py-1 sm:order-none sm:w-auto sm:flex-1 sm:basis-0 sm:justify-end',
            modes.length > 1 ? 'max-sm:-mx-1.5 max-sm:border-b max-sm:border-border/70 max-sm:px-1.5 sm:ml-3 sm:my-1.5 sm:border-l sm:border-border/70 sm:py-0 sm:pl-2' : '',
          ]"
        >
          <AuthorDutyPicker v-model="dutiableId" :duties />
        </div>
      </div>

      <input
        v-if="mode === 'topic' && expanded"
        ref="titleInput"
        v-model="title"
        type="text"
        maxlength="160"
        data-testid="composer-topic-title"
        :aria-label="$t('discussions.topic.title_label')"
        :placeholder="$t('discussions.topic.title_placeholder')"
        class="block w-full bg-transparent px-3.5 pt-3 text-base font-semibold text-foreground outline-none placeholder:font-normal placeholder:text-muted-foreground"
        @keydown.enter.prevent="editor?.commands.focus()"
      >

      <EditorContent :editor />
      <!-- Under the text, next to its toggle, so it never splits a topic's title from its body. -->
      <CommentToolbar v-if="editor && formattingOpen" ref="toolbar" :editor />

      <!-- Kept mounted so options survive a switch to another tab and back. -->
      <PollOptionsEditor v-show="mode === 'poll'" ref="pollEditor" :error="pollError" @dirty="value => pollDirty = value" />

      <!-- Attached images -->
      <ul v-if="images && attachments.images.value.length" class="flex flex-wrap gap-2 px-3.5 pb-3" :aria-label="$t('discussions.images.attached')">
        <li v-for="image in attachments.images.value" :key="image.key" class="relative size-16 overflow-hidden border border-border bg-muted">
          <img :src="image.preview" alt="" class="size-full object-cover" :class="image.uploaded ? undefined : 'opacity-50'">
          <Loader2 v-if="!image.uploaded" class="absolute inset-0 m-auto size-4 animate-spin text-foreground" aria-hidden="true" />
          <button
            type="button"
            class="absolute top-0 right-0 flex size-6 items-center justify-center bg-background/90 text-foreground hover:bg-background pointer-coarse:size-8"
            :aria-label="$t('discussions.images.remove')"
            @click="attachments.remove(image.key)"
          >
            <X class="size-3.5" />
          </button>
        </li>
      </ul>

      <!-- Footer -->
      <div v-if="expanded" class="flex flex-wrap items-center gap-x-1 gap-y-1 border-t border-border/70 px-1.5 py-1.5" data-slot="composer-footer">
        <div class="flex shrink-0 items-center">
          <SpotlightPopover
            :is-dismissed="!discoverFormatting || spotlight.isDismissed.value"
            :title="$t('discussions.formatting.title')"
            :description="$t('discussions.formatting.description')"
            @dismiss="spotlight.dismiss()"
          >
            <TiptapToolButton
              :label="$t('discussions.formatting.toggle')"
              :aria-pressed="formattingOpen"
              :class="formattingOpen ? 'bg-accent text-foreground' : undefined"
              data-testid="comment-formatting-toggle"
              @click="toggleFormatting"
            >
              <Type />
            </TiptapToolButton>
          </SpotlightPopover>
          <TiptapToolButton :label="$t('Paminėti')" data-testid="comment-mention" @click="insertMention">
            <AtSign />
          </TiptapToolButton>
          <template v-if="images">
            <TiptapToolButton :label="$t('discussions.images.add')" data-testid="comment-image" @click="fileInput?.click()">
              <ImagePlus />
            </TiptapToolButton>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" tabindex="-1" @change="onFilesPicked">
          </template>
        </div>

        <p
          v-if="notifiedNames.length"
          class="flex min-w-0 items-center gap-1 px-1 text-xs text-muted-foreground"
          :title="`${$t('Pranešimą apie paminėjimą gaus:')} ${notifiedNames.join(', ')}`"
          data-testid="mention-notice"
        >
          <Bell class="size-3.5 shrink-0" aria-hidden="true" />
          <span class="truncate">{{ notifiedNames.join(', ') }}</span>
        </p>

        <div class="ml-auto flex shrink-0 items-center gap-1">
          <Button
            v-if="showCancel || collapsible"
            type="button"
            variant="ghost"
            size="sm"
            class="text-muted-foreground"
            :aria-label="$t('Atšaukti')"
            data-testid="comment-cancel"
            @click="cancel"
          >
            <X />
            <span class="max-sm:sr-only">{{ $t('Atšaukti') }}</span>
          </Button>
          <Button type="button" variant="brand" size="sm" :disabled="!canSubmit" data-testid="comment-submit" @click="submit">
            <Loader2 v-if="submitting || attachments.uploading.value" class="animate-spin" />
            <component :is="submitIcon" v-else />
            {{ resolvedSubmitLabel }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, useTemplateRef, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import Mention from '@tiptap/extension-mention';
import { Placeholder } from '@tiptap/extensions';
import type { Editor } from '@tiptap/core';
import { PluginKey } from '@tiptap/pm/state';
import { onClickOutside } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { AtSign, BarChart3, Bell, ImagePlus, Loader2, MessageSquare, MessageSquarePlus, MessagesSquare, Send, Type, X } from '@lucide/vue';

import AuthorDutyPicker from '@/Components/Discussions/AuthorDutyPicker.vue';
import CommentMentionList from '@/Components/Discussions/CommentMentionList.vue';
import CommentToolbar from '@/Components/Discussions/CommentToolbar.vue';
import PollOptionsEditor from '@/Components/Discussions/PollOptionsEditor.vue';
import { useComposerImages } from '@/Components/Discussions/useComposerImages';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import TiptapToolButton from '@/Components/TipTap/TiptapToolButton.vue';
import { createSuggestionRenderer } from '@/Components/TipTap/composables/createSuggestionRenderer';
import { createCommentExtensions } from '@/Components/TipTap/extensions/presets';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import type { CommentAuthorDuty, CommentDraft, ComposerMode, MentionableUser } from '@/Types/discussions';
import './comment-content.css';

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
  /** Rests as a single line and opens on focus; the panel's root composer. */
  collapsible?: boolean;
  modes?: ComposerMode[];
  images?: boolean;
  duties?: CommentAuthorDuty[];
  defaultDutiableId?: string | null;
}>(), {
  mentionables: () => [],
  submitting: false,
  showCancel: false,
  autofocus: false,
  content: '',
  collapsible: false,
  modes: () => ['comment'],
  images: false,
  duties: () => [],
  defaultDutiableId: null,
});

const emit = defineEmits<{ submit: [draft: CommentDraft]; cancel: [] }>();

const root = useTemplateRef<HTMLElement>('root');
const toolbar = useTemplateRef<InstanceType<typeof CommentToolbar>>('toolbar');
const titleInput = useTemplateRef<HTMLInputElement>('titleInput');
const pollEditor = useTemplateRef<InstanceType<typeof PollOptionsEditor>>('pollEditor');
const fileInput = useTemplateRef<HTMLInputElement>('fileInput');

const mode = ref<ComposerMode>(props.modes[0] ?? 'comment');
const title = ref('');
const pollDirty = ref(false);
const pollError = ref('');
const focused = ref(false);
const formattingOpen = ref(false);
const isEmpty = ref(props.content.trim() === '');
const mentions = ref<{ id: string; label: string }[]>([]);
const dutiableId = ref<string | null>(props.defaultDutiableId);
const attachments = useComposerImages();
const spotlight = useFeatureSpotlight('comment-formatting-v2');

watch(() => props.defaultDutiableId, (value) => {
  dutiableId.value = value;
});
// The record's capabilities arrive after mount; an untouched composer follows them.
watch(() => props.modes.join(), () => {
  if (!isDirty.value || !props.modes.includes(mode.value)) mode.value = props.modes[0] ?? 'comment';
});

const isDirty = computed(() => !isEmpty.value || title.value.trim() !== '' || attachments.images.value.length > 0 || pollDirty.value);
const expanded = computed(() => !props.collapsible || focused.value || isDirty.value);

const modeOptions = computed(() => props.modes.map(option => ({
  mode: option,
  icon: { comment: MessageSquare, topic: MessagesSquare, poll: BarChart3 }[option],
  label: $t(`discussions.composer.mode_${option}`),
})));

const placeholderText = computed(() => {
  if (props.placeholder && (!expanded.value || (mode.value === 'comment' && !props.collapsible))) return props.placeholder;
  if (mode.value === 'topic') return $t('discussions.topic.body_placeholder');
  if (mode.value === 'poll') return $t('discussions.poll.question_placeholder');
  return $t('Parašyk komentarą…');
});

const submitIcon = computed(() => ({ comment: Send, topic: MessageSquarePlus, poll: BarChart3 }[mode.value]));

const resolvedSubmitLabel = computed(() => {
  if (mode.value === 'topic') return $t('discussions.topic.submit');
  if (mode.value === 'poll') return $t('discussions.poll.submit');
  return props.submitLabel || $t('Komentuoti');
});

const canSubmit = computed(() => !props.submitting
  && !attachments.uploading.value
  && !isEmpty.value
  && (mode.value !== 'topic' || title.value.trim() !== ''));

const currentUserId = computed(() => (usePage().props.auth as { user?: { id: string } } | undefined)?.user?.id);

// The author is never notified of their own mention.
const notifiedNames = computed(() => (props.quietMentions
  ? []
  : [...new Map(mentions.value.filter(mention => mention.id !== currentUserId.value).map(mention => [mention.id, mention.label])).values()]));

const mentionPluginKey = new PluginKey('commentMention');

const editor = useEditor({
  autofocus: props.autofocus,
  content: props.content,
  extensions: [
    ...createCommentExtensions(),
    Placeholder.configure({ placeholder: () => placeholderText.value }),
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
      const modifier = event.ctrlKey || event.metaKey;
      if (modifier && event.key === 'Enter') {
        event.preventDefault();
        submit();
        return true;
      }
      if (modifier && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        formattingOpen.value = true;
        void nextTick(() => toolbar.value?.openLink());
        return true;
      }
      if (event.key === 'Escape' && props.collapsible && !isDirty.value) {
        (event.target as HTMLElement | null)?.blur();
        focused.value = false;
        return true;
      }
      return false;
    },
    handlePaste: (_view, event) => {
      const files = [...(event.clipboardData?.files ?? [])].filter(file => file.type.startsWith('image/'));
      if (!props.images || !files.length) return false;
      attachments.add(files);
      return true;
    },
    attributes: {
      class: 'comment-content focus:outline-none px-3.5 py-2.5 min-h-10 text-foreground',
    },
  },
  onCreate: ({ editor: instance }) => updateState(instance),
  onUpdate: ({ editor: instance }) => updateState(instance),
  onFocus: () => { focused.value = true; },
});

// Placeholder decorations only redraw on a transaction.
watch(placeholderText, () => {
  const instance = editor.value;
  if (instance && !instance.isDestroyed) instance.view.dispatch(instance.state.tr);
});

// Popovers and menus render in a portal, so a click inside one is not "outside" the composer.
onClickOutside(root, () => {
  if (!isDirty.value) {
    focused.value = false;
    formattingOpen.value = false;
  }
}, { ignore: ['[data-reka-popper-content-wrapper]', '[role=dialog]', '[role=menu]'] });

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

function setMode(next: ComposerMode) {
  mode.value = next;
  pollError.value = '';
  void nextTick(() => (next === 'topic' ? titleInput.value?.focus() : editor.value?.commands.focus()));
}

function toggleFormatting() {
  formattingOpen.value = !formattingOpen.value;
  void spotlight.dismiss();
}

function onFilesPicked(event: Event) {
  const input = event.target as HTMLInputElement;
  attachments.add([...(input.files ?? [])]);
  input.value = '';
}

function submit() {
  const instance = editor.value;
  if (!instance || !canSubmit.value) {
    return;
  }

  const draft: CommentDraft = {
    body: instance.getHTML(),
    mode: mode.value,
    imageIds: attachments.imageIds.value,
    dutiableId: dutiableId.value,
  };

  if (mode.value === 'topic') {
    draft.title = title.value.trim();
  }

  if (mode.value === 'poll') {
    const poll = pollEditor.value?.draft();
    if (!poll) {
      pollError.value = $t('Reikia bent dviejų variantų.');
      return;
    }
    pollError.value = '';
    draft.poll = poll;
  }

  emit('submit', draft);
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
  title.value = '';
  pollError.value = '';
  pollEditor.value?.reset();
  attachments.clear();
  // Back to the suggested duty: a one-off choice (a former duty, say) must not carry over.
  dutiableId.value = props.defaultDutiableId;
  mode.value = props.modes[0] ?? 'comment';
  formattingOpen.value = false;
  focused.value = false;
}

function cancel() {
  if (props.collapsible) {
    reset();
    editor.value?.commands.blur();
  }
  emit('cancel');
}

defineExpose({ reset, focus: () => editor.value?.commands.focus() });

onBeforeUnmount(() => {
  attachments.clear();
  editor.value?.destroy();
});
</script>
