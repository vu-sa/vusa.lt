<template>
  <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800" :class="comment.is_resolved ? 'bg-zinc-50/60 dark:bg-zinc-900/40' : 'bg-white dark:bg-zinc-900'">
    <CommentItem
      :comment="comment"
      :mentionables="mentionables"
      :submitting="submitting"
      @reply="replyOpen = true"
      @update="(id, body) => $emit('update', id, body)"
      @delete="(id) => $emit('delete', id)"
      @resolve="(id) => $emit('resolve', id)"
      @unresolve="(id) => $emit('unresolve', id)"
      @toggle-reaction="(id, emoji) => $emit('toggle-reaction', id, emoji)"
      @vote="(id, optionId) => $emit('vote', id, optionId)"
    />

    <!-- Replies -->
    <div v-if="comment.replies?.length" class="mt-3 space-y-3 border-l-2 border-zinc-100 pl-3 dark:border-zinc-800">
      <CommentItem
        v-for="reply in comment.replies"
        :key="reply.id"
        :comment="reply"
        :mentionables="mentionables"
        :submitting="submitting"
        is-reply
        :can-reply="false"
        :poll-context="comment.kind === 'poll' ? comment.poll : null"
        @update="(id, body) => $emit('update', id, body)"
        @delete="(id) => $emit('delete', id)"
        @toggle-reaction="(id, emoji) => $emit('toggle-reaction', id, emoji)"
      />
    </div>

    <!-- Reply composer -->
    <CommentComposer
      v-if="replyOpen"
      class="mt-3 pl-3"
      :mentionables="mentionables"
      :placeholder="$t('Atsakykite…')"
      :submit-label="$t('Atsakyti')"
      :submitting="submitting"
      show-cancel
      autofocus
      @submit="onReplySubmit"
      @cancel="replyOpen = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import CommentItem from '@/Components/Discussions/CommentItem.vue';
import type { CommentData, MentionableUser } from '@/Types/discussions';

const props = withDefaults(defineProps<{
  comment: CommentData;
  mentionables?: MentionableUser[];
  submitting?: boolean;
}>(), {
  mentionables: () => [],
  submitting: false,
});

const emit = defineEmits<{
  reply: [parentId: string, body: string];
  update: [id: string, body: string];
  delete: [id: string];
  resolve: [id: string];
  unresolve: [id: string];
  'toggle-reaction': [id: string, emoji: string];
  vote: [id: string, optionId: string];
}>();

const replyOpen = ref(false);

function onReplySubmit(html: string) {
  emit('reply', props.comment.id, html);
  replyOpen.value = false;
}
</script>
