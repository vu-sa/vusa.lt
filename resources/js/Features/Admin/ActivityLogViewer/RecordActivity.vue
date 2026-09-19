<template>
  <section aria-labelledby="record-activity-title">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 id="record-activity-title" class="text-xl font-semibold text-foreground">
          {{ $t('Veikla') }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ $t('Komentarai ir įrašo pakeitimai vienoje vietoje.') }}
        </p>
      </div>
      <div class="flex border border-border p-1">
        <Button
          v-for="option in filterOptions"
          :key="option.value"
          size="sm"
          :variant="filter === option.value ? 'secondary' : 'ghost'"
          @click="filter = option.value"
        >
          {{ option.label }}
        </Button>
      </div>
    </div>

    <div v-if="filter !== 'changes'" class="mt-6 flex items-center gap-3 border-b border-border pb-6">
      <UserAvatar v-if="currentUser" :user="currentUser" :size="32" class="shrink-0" />
      <CommentComposer
        ref="rootComposer"
        class="min-w-0 flex-1"
        :mentionables="discussion.mentionables.value"
        :submitting="discussion.posting.value"
        collapsible
        @submit="postComment"
      >
        <template #leading>
          <Dialog v-model:open="pollDialogOpen">
            <DialogTrigger as-child>
              <Button variant="outline" size="xs">
                <BarChart3 class="size-3.5" />
                {{ $t('Apklausa') }}
              </Button>
            </DialogTrigger>
            <DialogContent class="max-w-lg">
              <DialogHeader>
                <DialogTitle>{{ $t('Sukurti apklausą') }}</DialogTitle>
                <DialogDescription>
                  {{ $t('Sukurkite apklausą ir gaukite komandos atsakymus.') }}
                </DialogDescription>
              </DialogHeader>
              <PollComposer
                :mentionables="discussion.mentionables.value"
                :submitting="discussion.posting.value"
                @submit="createPoll"
                @cancel="pollDialogOpen = false"
              />
            </DialogContent>
          </Dialog>
        </template>
      </CommentComposer>
    </div>

    <div v-if="loading" class="mt-6 space-y-3" aria-busy="true">
      <Skeleton v-for="index in 3" :key="index" class="h-20 w-full" />
    </div>

    <div v-else-if="items.length === 0" class="mt-6 border-y border-border py-8 text-center text-sm text-muted-foreground">
      {{ $t('activity.empty') }}
    </div>

    <div v-else class="mt-6 divide-y divide-border border-y border-border">
      <div v-for="item in items" :key="item.key" class="py-5">
        <CommentThread
          v-if="item.kind === 'comment'"
          :comment="item.comment"
          :mentionables="discussion.mentionables.value"
          :submitting="discussion.mutating.value"
          @reply="discussion.post"
          @update="discussion.update"
          @delete="discussion.remove"
          @resolve="discussion.resolve"
          @unresolve="discussion.unresolve"
          @toggle-reaction="discussion.toggleReaction"
          @vote="discussion.vote"
        />
        <ActivityLogEntry v-else :entry="item.entry" />
      </div>
    </div>

    <Button
      v-if="filter !== 'comments' && activity.hasMore.value"
      variant="outline"
      class="mt-4 w-full"
      :disabled="activity.loadingMore.value"
      @click="activity.loadMore"
    >
      {{ activity.loadingMore.value ? $t('activity.loading') : $t('activity.load_more') }}
    </Button>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BarChart3 } from 'lucide-vue-next';

import ActivityLogEntry from './ActivityLogEntry.vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import CommentThread from '@/Components/Discussions/CommentThread.vue';
import PollComposer from '@/Components/Discussions/PollComposer.vue';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';
import { Skeleton } from '@/Components/ui/skeleton';
import { useActivityLog } from '@/Composables/useActivityLog';
import { useDiscussionThread } from '@/Composables/useDiscussionThread';
import type { ActivityEntry } from '@/Types/activityLog';
import type { CommentData, PollDraft } from '@/Types/discussions';

const props = defineProps<{
  subjectType: string;
  subjectId: string;
  commentableType: string;
  commentableId: string;
}>();

type Filter = 'all' | 'comments' | 'changes';
type TimelineItem
  = | { kind: 'comment'; key: string; date: string | null; comment: CommentData }
    | { kind: 'activity'; key: string; date: string | null; entry: ActivityEntry };

const filter = ref<Filter>('all');
const pollDialogOpen = ref(false);
const rootComposer = ref<InstanceType<typeof CommentComposer> | null>(null);
const currentUser = computed(() => (usePage().props.auth as { user?: App.Entities.User } | undefined)?.user ?? null);
const discussion = useDiscussionThread(props.commentableType, props.commentableId);
const activity = useActivityLog(props.subjectType, props.subjectId);

const filterOptions = computed(() => [
  { value: 'all' as const, label: $t('Visi') },
  { value: 'comments' as const, label: $t('Komentarai') },
  { value: 'changes' as const, label: $t('Pakeitimai') },
]);

const loading = computed(() => discussion.loading.value || activity.loading.value);
const items = computed<TimelineItem[]>(() => {
  const comments: TimelineItem[] = filter.value === 'changes'
    ? []
    : discussion.comments.value.map(comment => ({
        kind: 'comment',
        key: `comment-${comment.id}`,
        date: comment.created_at,
        comment,
      }));
  const changes: TimelineItem[] = filter.value === 'comments'
    ? []
    : activity.entries.value.map(entry => ({
        kind: 'activity',
        key: `activity-${entry.id}`,
        date: entry.created_at,
        entry,
      }));

  return [...comments, ...changes].sort((left, right) => {
    const leftTime = left.date ? new Date(left.date).getTime() : 0;
    const rightTime = right.date ? new Date(right.date).getTime() : 0;
    return rightTime - leftTime;
  });
});

const postComment = async (html: string) => {
  const comment = await discussion.post(html);
  if (comment) {
    rootComposer.value?.reset();
  }
};

const createPoll = async (html: string, poll: PollDraft) => {
  if (await discussion.createPoll(html, poll)) {
    pollDialogOpen.value = false;
  }
};

onMounted(() => {
  void Promise.all([discussion.load(), activity.load()]);
});
</script>
