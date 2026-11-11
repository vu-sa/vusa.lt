<template>
  <article
    class="border border-border bg-background"
    data-slot="discussion-topic"
    :data-topic-id="topic.id"
    :data-closed="closed || undefined"
  >
    <!-- The base h3 style tightens tracking; the header reads as body text. -->
    <h3 class="m-0 text-base font-normal tracking-normal">
      <button
        type="button"
        :aria-expanded="open"
        :aria-controls="bodyId"
        data-testid="topic-toggle"
        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-accent/50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring pointer-coarse:min-h-11"
        @click="open = !open"
      >
        <component
          :is="closed ? CircleCheck : MessagesSquare"
          :class="['mt-0.5 size-4 shrink-0', closed ? 'text-status-success' : 'text-brand']"
          aria-hidden="true"
        />
        <span class="min-w-0 flex-1">
          <span :class="['block text-[0.9375rem] leading-snug font-semibold', closed ? 'text-muted-foreground' : 'text-foreground']">{{ topic.title }}</span>
          <span class="mt-1 block text-xs text-muted-foreground">
            {{ topic.user.name ?? $t('Komentaras ištrintas') }}
            <span aria-hidden="true"> · </span>{{ lastActivityLabel }}
          </span>
        </span>
        <span class="flex shrink-0 items-center gap-3 pt-0.5">
          <StatusBadge v-if="closed" :status="discussionTopicStatuses.closed" class="hidden sm:inline-flex" />
          <span v-if="participants.length > 1" class="hidden items-center sm:flex" aria-hidden="true">
            <UserAvatar
              v-for="(participant, index) in participants.slice(0, 3)"
              :key="participant.id"
              :user="participant"
              :size="20"
              :class="index > 0 ? '-ml-1.5 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : undefined"
            />
          </span>
          <span class="inline-flex items-center gap-1 text-xs tabular-nums text-muted-foreground" :title="$tChoice('discussions.topic.replies', replyCount, { count: String(replyCount) })">
            <MessageSquare class="size-3.5" aria-hidden="true" />
            {{ replyCount }}
            <span class="sr-only">{{ $tChoice('discussions.topic.replies', replyCount, { count: String(replyCount) }) }}</span>
          </span>
          <ChevronDown :class="['size-4 text-muted-foreground transition-transform', open ? 'rotate-180' : '']" aria-hidden="true" />
        </span>
      </button>
    </h3>

    <div v-if="open" :id="bodyId" class="space-y-4 border-t border-border px-4 pt-4 pb-3" data-slot="topic-body">
      <CommentItem
        :comment="topic"
        :mentionables
        :submitting
        :can-reply="false"
        @update="(id, body) => $emit('update', id, body)"
        @delete="(id) => $emit('delete', id)"
        @toggle-reaction="(id, emoji) => $emit('toggle-reaction', id, emoji)"
      />

      <CommentThread
        ref="thread"
        :comment="topic"
        :mentionables
        :submitting
        :images
        :duties
        :default-dutiable-id
        hide-root
        expand-replies
        @reply="(parentId, draft) => $emit('reply', parentId, draft)"
        @update="(id, body) => $emit('update', id, body)"
        @delete="(id) => $emit('delete', id)"
        @toggle-reaction="(id, emoji) => $emit('toggle-reaction', id, emoji)"
      />

      <div class="flex flex-wrap items-center gap-1 border-t border-border/70 pt-3">
        <Button v-if="!closed && !thread?.replyOpen" type="button" variant="outline" size="sm" data-testid="topic-reply" @click="thread?.openReply()">
          <CornerDownRight />
          {{ $t('discussions.topic.reply') }}
        </Button>
        <Button
          v-if="topic.can.resolve"
          type="button"
          variant="ghost"
          size="sm"
          class="text-muted-foreground"
          :data-testid="closed ? 'topic-reopen' : 'topic-close'"
          @click="closed ? $emit('reopen', topic.id) : $emit('close', topic.id)"
        >
          <component :is="closed ? RotateCcw : CircleCheck" />
          {{ closed ? $t('discussions.topic.reopen') : $t('discussions.topic.close') }}
        </Button>
        <p v-if="closed" class="ml-auto text-xs text-muted-foreground">{{ $t('discussions.topic.closed_hint') }}</p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, ref, useId, useTemplateRef, watch } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronDown, CircleCheck, CornerDownRight, MessageSquare, MessagesSquare, RotateCcw } from '@lucide/vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CommentItem from '@/Components/Discussions/CommentItem.vue';
import CommentThread from '@/Components/Discussions/CommentThread.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { discussionTopicStatuses } from '@/Constants/statusIcons';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import type { CommentAuthorDuty, CommentData, CommentDraft, MentionableUser } from '@/Types/discussions';

const props = withDefaults(defineProps<{
  topic: CommentData;
  mentionables?: MentionableUser[];
  submitting?: boolean;
  defaultOpen?: boolean;
  images?: boolean;
  duties?: CommentAuthorDuty[];
  defaultDutiableId?: string | null;
}>(), {
  mentionables: () => [],
  submitting: false,
  defaultOpen: false,
  images: false,
  duties: () => [],
  defaultDutiableId: null,
});

defineEmits<{
  'reply': [parentId: string, draft: CommentDraft];
  'update': [id: string, body: string];
  'delete': [id: string];
  'toggle-reaction': [id: string, emoji: string];
  'close': [id: string];
  'reopen': [id: string];
}>();

const open = ref(props.defaultOpen);
// The panel learns a new topic's id only after the topic is already listed.
watch(() => props.defaultOpen, (value) => {
  if (value) open.value = true;
});
const bodyId = useId();
const thread = useTemplateRef<InstanceType<typeof CommentThread>>('thread');
const { formatNearDate } = useDateFormatter();

const closed = computed(() => Boolean(props.topic.is_closed ?? props.topic.is_resolved));
const replies = computed(() => (props.topic.replies ?? []).filter(reply => !reply.is_erased));
const replyCount = computed(() => replies.value.length);

const participants = computed(() => {
  const seen = new Map<string, { id: string; name: string; profile_photo_path: string | null }>();
  for (const comment of [props.topic, ...replies.value]) {
    if (comment.user.id && !seen.has(comment.user.id)) {
      seen.set(comment.user.id, { id: comment.user.id, name: comment.user.name ?? '', profile_photo_path: comment.user.profile_photo_path });
    }
  }
  return [...seen.values()];
});

const lastActivityLabel = computed(() => {
  const last = replies.value.at(-1);
  return last
    ? $t('discussions.topic.last_reply', { time: formatNearDate(last.created_at) })
    : formatNearDate(props.topic.created_at);
});

// A closed topic takes no replies, so a reply box left open would only fail on send.
watch(closed, (isClosed) => {
  if (isClosed) thread.value?.closeReply();
});

defineExpose({
  open: () => {
    open.value = true;
  },
});
</script>
