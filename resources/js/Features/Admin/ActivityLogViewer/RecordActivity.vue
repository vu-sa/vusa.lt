<template>
  <section aria-labelledby="record-activity-title">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 id="record-activity-title" class="text-xl font-semibold text-foreground">
          {{ $t('Veikla') }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ $t('activity.comments_lead') }}
        </p>
      </div>
    </div>

    <div class="mt-6 flex items-center gap-3 border-b border-border pb-6">
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
              <Button variant="outline" size="xs" voice="sentence">
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

    <p v-else-if="discussion.comments.value.length === 0" class="py-6 text-sm text-muted-foreground">
      {{ $t('activity.comments_empty') }}
    </p>

    <div v-else class="divide-y divide-border border-b border-border">
      <div v-for="comment in discussion.comments.value" :key="comment.id" class="py-5">
        <CommentThread
          :comment
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
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BarChart3 } from 'lucide-vue-next';

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
import { useDiscussionThread } from '@/Composables/useDiscussionThread';
import type { PollDraft } from '@/Types/discussions';

const props = defineProps<{
  commentableType: string;
  commentableId: string;
}>();

const pollDialogOpen = ref(false);
const rootComposer = ref<InstanceType<typeof CommentComposer> | null>(null);
const currentUser = computed(() => (usePage().props.auth as { user?: App.Entities.User } | undefined)?.user ?? null);
const discussion = useDiscussionThread(props.commentableType, props.commentableId);

const loading = computed(() => discussion.loading.value);

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
  void discussion.load();
});
</script>
