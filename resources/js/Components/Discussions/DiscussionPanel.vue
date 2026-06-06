<template>
  <div class="flex flex-col gap-3">
    <!-- Header -->
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2 text-sm font-medium text-foreground">
        <MessagesSquare class="h-4 w-4 text-muted-foreground" />
        <span>{{ $t('Diskusija') }}</span>
        <span v-if="rootCount" class="text-muted-foreground">({{ rootCount }})</span>
      </div>

      <div class="flex items-center gap-2">
        <span v-if="members.length > 1" class="text-xs text-muted-foreground">
          {{ $t(':count peržiūri', { count: members.length }) }}
        </span>
        <button
          type="button"
          :class="[
            'rounded-md px-2 py-1 text-xs transition-colors',
            showResolved ? 'text-muted-foreground hover:text-foreground' : 'bg-zinc-100 font-medium text-foreground dark:bg-zinc-800',
          ]"
          @click="showResolved = !showResolved"
        >
          {{ showResolved ? $t('Rodyti tik neišspręstus') : $t('Rodyti visus') }}
        </button>
      </div>
    </div>

    <!-- Root composer -->
    <PollComposer
      v-if="composerMode === 'poll'"
      :mentionables="mentionables"
      :submitting="posting"
      @submit="onCreatePoll"
      @cancel="composerMode = 'comment'"
    />
    <CommentComposer
      v-else
      ref="rootComposer"
      :mentionables="mentionables"
      :submitting="posting"
      @submit="onPost"
    >
      <template #leading>
        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-foreground dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
          @click="composerMode = 'poll'"
        >
          <BarChart3 class="h-3.5 w-3.5" />
          {{ $t('Apklausa') }}
        </button>
      </template>
    </CommentComposer>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3">
      <div v-for="n in 2" :key="n" class="animate-pulse rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
        <div class="flex gap-2.5">
          <div class="h-8 w-8 rounded-full bg-zinc-200 dark:bg-zinc-700" />
          <div class="flex-1 space-y-2">
            <div class="h-3 w-24 rounded bg-zinc-200 dark:bg-zinc-700" />
            <div class="h-3 w-full rounded bg-zinc-100 dark:bg-zinc-800" />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="!visibleComments.length" class="rounded-lg border border-dashed border-zinc-200 py-8 text-center text-sm text-muted-foreground dark:border-zinc-700">
      {{ showResolved || comments.length === 0 ? $t('Kol kas nėra komentarų. Pradėkite diskusiją.') : $t('Nėra neišspręstų komentarų.') }}
    </div>

    <!-- Threads -->
    <div v-else class="space-y-3">
      <CommentThread
        v-for="comment in visibleComments"
        :key="comment.id"
        :comment="comment"
        :mentionables="mentionables"
        :submitting="mutating"
        @reply="onReply"
        @update="onUpdate"
        @delete="onDelete"
        @resolve="onResolve"
        @unresolve="onUnresolve"
        @toggle-reaction="onToggleReaction"
        @vote="onPollVote"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { BarChart3, MessagesSquare } from 'lucide-vue-next';

import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import CommentThread from '@/Components/Discussions/CommentThread.vue';
import PollComposer from '@/Components/Discussions/PollComposer.vue';
import { useDiscussionApi } from '@/Composables/useDiscussionApi';
import { useDiscussionChannel } from '@/Composables/useDiscussionChannel';
import { useToasts } from '@/Composables/useToasts';
import type { CommentData, MentionableUser, PollDraft } from '@/Types/discussions';

const props = defineProps<{
  commentableType: string;
  commentableId: string;
}>();

const api = useDiscussionApi(props.commentableType, props.commentableId);
const toasts = useToasts();

const comments = ref<CommentData[]>([]);
const mentionables = ref<MentionableUser[]>([]);
const loading = ref(true);
const posting = ref(false);
const mutating = ref(false);
const showResolved = ref(true);
const composerMode = ref<'comment' | 'poll'>('comment');

const rootComposer = ref<InstanceType<typeof CommentComposer> | null>(null);

const rootCount = computed(() => comments.value.length);
const visibleComments = computed(() =>
  showResolved.value ? comments.value : comments.value.filter(comment => !comment.is_resolved),
);

// --- Real-time merge (idempotent upserts; the actor also receives its own event) ---

function upsertComment(incoming: CommentData) {
  if (!incoming.parent_id) {
    const index = comments.value.findIndex(comment => comment.id === incoming.id);
    if (index === -1) {
      comments.value.push(incoming);
    }
    else {
      // Preserve already-loaded replies when patching a root.
      comments.value[index] = { ...incoming, replies: incoming.replies ?? comments.value[index].replies };
    }
    return;
  }

  const root = comments.value.find(comment => comment.id === (incoming.thread_root_id ?? incoming.parent_id));
  if (!root) {
    return;
  }
  root.replies = root.replies ?? [];
  const replyIndex = root.replies.findIndex(reply => reply.id === incoming.id);
  if (replyIndex === -1) {
    root.replies.push(incoming);
  }
  else {
    root.replies[replyIndex] = incoming;
  }
}

function removeComment(id: string) {
  const rootIndex = comments.value.findIndex(comment => comment.id === id);
  if (rootIndex !== -1) {
    comments.value.splice(rootIndex, 1);
    return;
  }
  for (const root of comments.value) {
    if (root.replies?.some(reply => reply.id === id)) {
      root.replies = root.replies.filter(reply => reply.id !== id);
      return;
    }
  }
}

const { members, connect } = useDiscussionChannel(props.commentableType, props.commentableId, {
  onCreated: upsertComment,
  onUpdated: upsertComment,
  onResolved: upsertComment,
  onReaction: upsertComment,
  onPoll: upsertComment,
  onDeleted: ({ id }) => removeComment(id),
});

// --- Actions ---

async function onPost(html: string) {
  posting.value = true;
  try {
    const comment = await api.postComment(html);
    upsertComment(comment);
    rootComposer.value?.reset();
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
  finally {
    posting.value = false;
  }
}

async function onCreatePoll(html: string, poll: PollDraft) {
  posting.value = true;
  try {
    upsertComment(await api.createPoll(html, poll));
    composerMode.value = 'comment';
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
  finally {
    posting.value = false;
  }
}

async function onPollVote(id: string, optionId: string) {
  try {
    upsertComment(await api.togglePollVote(id, optionId));
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
}

async function onReply(parentId: string, html: string) {
  mutating.value = true;
  try {
    upsertComment(await api.postComment(html, parentId));
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
  finally {
    mutating.value = false;
  }
}

async function onUpdate(id: string, html: string) {
  mutating.value = true;
  try {
    upsertComment(await api.updateComment(id, html));
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
  finally {
    mutating.value = false;
  }
}

async function onDelete(id: string) {
  try {
    await api.deleteComment(id);
    removeComment(id);
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
}

async function onResolve(id: string) {
  try {
    upsertComment(await api.resolveComment(id));
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
}

async function onUnresolve(id: string) {
  try {
    upsertComment(await api.unresolveComment(id));
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
}

async function onToggleReaction(id: string, emoji: string) {
  try {
    upsertComment(await api.toggleReaction(id, emoji));
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
}

onMounted(async () => {
  try {
    const [thread, mentions] = await Promise.all([api.fetchThread(), api.fetchMentionables()]);
    comments.value = thread;
    mentionables.value = mentions;
  }
  catch (error) {
    toasts.error((error as Error).message);
  }
  finally {
    loading.value = false;
  }

  connect();
});
</script>
