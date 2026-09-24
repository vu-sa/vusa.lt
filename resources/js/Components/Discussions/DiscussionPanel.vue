<template>
  <div class="flex flex-col gap-3">
    <!-- Header -->
    <div class="flex items-center justify-between gap-2">
      <div
        class="flex items-center gap-2 text-foreground"
        :class="framed ? 'text-xs font-semibold uppercase tracking-wide' : 'text-sm font-medium'"
      >
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
            'px-2 py-1 text-xs transition-colors pointer-coarse:min-h-11',
            showResolved ? 'text-muted-foreground hover:text-foreground' : 'bg-accent font-medium text-foreground',
          ]"
          @click="showResolved = !showResolved"
        >
          {{ showResolved ? $t('Rodyti tik neišspręstus') : $t('Rodyti visus') }}
        </button>
      </div>
    </div>

    <div :class="framed ? 'flex flex-col gap-4 border-y border-border py-4' : 'contents'">
      <!-- Root composer, attributed like every comment below it -->
      <div class="flex items-center gap-3">
        <UserAvatar v-if="currentUser" :user="currentUser" :size="32" class="shrink-0" />
        <CommentComposer
          ref="rootComposer"
          class="min-w-0 flex-1"
          :mentionables
          :submitting="posting"
          collapsible
          @submit="onPost"
        >
          <template #leading>
            <Dialog v-model:open="pollDialogOpen">
              <DialogTrigger as-child>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 border border-border bg-background px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                  <BarChart3 class="h-3.5 w-3.5" />
                  {{ $t('Apklausa') }}
                </button>
              </DialogTrigger>
              <DialogContent class="max-w-lg">
                <DialogHeader>
                  <DialogTitle>{{ $t('Sukurti apklausą') }}</DialogTitle>
                  <DialogDescription>
                    {{ $t('Sukurkite apklausą ir gaukite komandos atsakymus.') }}
                  </DialogDescription>
                </DialogHeader>
                <PollComposer
                  :mentionables
                  :submitting="posting"
                  @submit="onCreatePoll"
                  @cancel="pollDialogOpen = false"
                />
              </DialogContent>
            </Dialog>
          </template>
        </CommentComposer>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-3">
        <div v-for="n in 2" :key="n" class="animate-pulse border-b border-border py-3">
          <div class="flex gap-2.5">
            <!-- eslint-disable-next-line admin-redesign/no-legacy-utility -- avatars stay circular -->
            <div class="size-8 rounded-full bg-muted" />
            <div class="flex-1 space-y-2">
              <div class="h-3 w-24 bg-muted" />
              <div class="h-3 w-full bg-muted/60" />
            </div>
          </div>
        </div>
      </div>

      <!-- Threads -->
      <div v-else class="divide-y divide-border">
        <CommentThread
          v-for="comment in visibleComments"
          :key="comment.id"
          :comment
          :mentionables
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
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BarChart3, MessagesSquare } from 'lucide-vue-next';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import CommentThread from '@/Components/Discussions/CommentThread.vue';
import PollComposer from '@/Components/Discussions/PollComposer.vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';
import { useDiscussionThread } from '@/Composables/useDiscussionThread';
import type { PollDraft } from '@/Types/discussions';

const props = withDefaults(defineProps<{
  commentableType: string;
  commentableId: string;
  /** Draw the composer and threads inside a card, for pages built out of framed sections. */
  framed?: boolean;
}>(), {
  framed: false,
});

const currentUser = computed(() => (usePage().props.auth as { user?: App.Entities.User } | undefined)?.user ?? null);
const discussion = useDiscussionThread(props.commentableType, props.commentableId);
const { comments } = discussion;
const { mentionables } = discussion;
const { loading } = discussion;
const { posting } = discussion;
const { mutating } = discussion;
const { members } = discussion;
const showResolved = ref(true);
const pollDialogOpen = ref(false);

const rootComposer = ref<InstanceType<typeof CommentComposer> | null>(null);

const rootCount = computed(() => comments.value.length);
const visibleComments = computed(() =>
  showResolved.value ? comments.value : comments.value.filter(comment => !comment.is_resolved),
);

// --- Actions ---

async function onPost(html: string) {
  const comment = await discussion.post(html);
  if (comment) {
    rootComposer.value?.reset?.();
  }
}

async function onCreatePoll(html: string, poll: PollDraft) {
  if (await discussion.createPoll(html, poll)) {
    pollDialogOpen.value = false;
  }
}

async function onPollVote(id: string, optionId: string) {
  await discussion.vote(id, optionId);
}

async function onReply(parentId: string, html: string) {
  await discussion.post(html, parentId);
}

async function onUpdate(id: string, html: string) {
  await discussion.update(id, html);
}

async function onDelete(id: string) {
  await discussion.remove(id);
}

async function onResolve(id: string) {
  await discussion.resolve(id);
}

async function onUnresolve(id: string) {
  await discussion.unresolve(id);
}

async function onToggleReaction(id: string, emoji: string) {
  await discussion.toggleReaction(id, emoji);
}

onMounted(() => {
  void discussion.load();
});
</script>
