<template>
  <div data-slot="comment-thread">
    <CommentItem
      v-if="!hideRoot"
      :comment
      :mentionables
      :submitting
      :reply-active="replyOpen && replyTarget === comment.id"
      @reply="toggleReply"
      @update="(id, body) => $emit('update', id, body)"
      @delete="(id) => $emit('delete', id)"
      @toggle-reaction="(id, emoji) => $emit('toggle-reaction', id, emoji)"
      @vote="(id, optionId) => $emit('vote', id, optionId)"
    />

    <div v-if="replies.length || replyOpen" :class="['space-y-4 border-l border-border pl-4', hideRoot ? '' : 'mt-3 ml-4']" data-slot="comment-replies">
      <button
        v-if="hiddenCount > 0"
        type="button"
        data-testid="show-earlier-replies"
        class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground pointer-coarse:min-h-11"
        @click="showAll = true"
      >
        <ChevronUp class="size-3.5" aria-hidden="true" />
        {{ $tChoice('discussions.show_earlier_replies', hiddenCount, { count: String(hiddenCount) }) }}
      </button>
      <CommentItem
        v-for="reply in visibleReplies"
        :key="reply.id"
        :comment="reply"
        :mentionables
        :submitting
        is-reply
        :can-reply="!expandReplies"
        :reply-active="replyOpen && replyTarget === reply.id"
        :poll-context="comment.kind === 'poll' ? comment.poll : null"
        @reply="toggleReply"
        @update="(id, body) => $emit('update', id, body)"
        @delete="(id) => $emit('delete', id)"
        @toggle-reaction="(id, emoji) => $emit('toggle-reaction', id, emoji)"
      />

      <CommentComposer
        v-if="replyOpen"
        ref="replyComposer"
        :mentionables
        :placeholder="$t('Atsakyk…')"
        :submit-label="$t('Atsakyti')"
        :submitting
        :images
        :duties
        :default-dutiable-id
        show-cancel
        autofocus
        @submit="onReplySubmit"
        @cancel="closeReply"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronUp } from '@lucide/vue';

import CommentItem from '@/Components/Discussions/CommentItem.vue';
import type { CommentAuthorDuty, CommentData, CommentDraft, MentionableUser } from '@/Types/discussions';

const CommentComposer = defineAsyncComponent(() => import('@/Components/Discussions/CommentComposer.vue'));

/** Replies past this many fold behind "Rodyti ankstesnius"; the latest stay in view. */
const VISIBLE_REPLIES = 2;

const props = withDefaults(defineProps<{
  comment: CommentData;
  mentionables?: MentionableUser[];
  submitting?: boolean;
  /** A topic draws its own opening post, so the thread shows replies only. */
  hideRoot?: boolean;
  /** Topics show every reply once opened. */
  expandReplies?: boolean;
  images?: boolean;
  duties?: CommentAuthorDuty[];
  defaultDutiableId?: string | null;
}>(), {
  mentionables: () => [],
  submitting: false,
  hideRoot: false,
  expandReplies: false,
  images: false,
  duties: () => [],
  defaultDutiableId: null,
});

const emit = defineEmits<{
  'reply': [parentId: string, draft: CommentDraft];
  'update': [id: string, body: string];
  'delete': [id: string];
  'toggle-reaction': [id: string, emoji: string];
  'vote': [id: string, optionId: string];
}>();

const replyOpen = ref(false);
// Replies all join the root's thread; this only marks which comment's button opened the box.
const replyTarget = ref<string | null>(null);
const showAll = ref(false);
const replyComposer = ref<{ focus: () => void } | null>(null);

const replies = computed(() => props.comment.replies ?? []);
const hiddenCount = computed(() => (showAll.value || props.expandReplies ? 0 : Math.max(0, replies.value.length - VISIBLE_REPLIES)));
const visibleReplies = computed(() => replies.value.slice(hiddenCount.value));

function openReply(target: string = props.comment.id) {
  replyTarget.value = target;
  replyOpen.value = true;
  replyComposer.value?.focus();
}

function closeReply() {
  replyOpen.value = false;
  replyTarget.value = null;
}

function toggleReply(target: string) {
  if (replyOpen.value && replyTarget.value === target) {
    closeReply();
    return;
  }
  openReply(target);
}

function onReplySubmit(draft: CommentDraft) {
  emit('reply', props.comment.id, draft);
  closeReply();
}

defineExpose({ openReply, closeReply, replyOpen });
</script>
