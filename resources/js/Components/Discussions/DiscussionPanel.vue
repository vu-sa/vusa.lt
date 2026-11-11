<template>
  <section class="flex flex-col gap-5" data-slot="discussion-panel" :data-layout="layout" :aria-labelledby="showTitle ? titleId : undefined">
    <header v-if="showTitle || otherViewers.length" class="flex min-h-6 flex-wrap items-center justify-between gap-x-4 gap-y-2">
      <h2 v-if="showTitle" :id="titleId" class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em] text-foreground">
        <MessagesSquare class="size-4 shrink-0 text-brand" aria-hidden="true" />
        {{ resolvedTitle }}
        <span v-if="rootCount" class="font-semibold text-muted-foreground">{{ rootCount }}</span>
      </h2>
      <p v-if="otherViewers.length" class="ml-auto flex items-center gap-2 text-xs text-muted-foreground" data-testid="discussion-viewers">
        <span class="flex items-center" aria-hidden="true">
          <UserAvatar
            v-for="(member, index) in otherViewers.slice(0, 3)"
            :key="member.id"
            :user="member"
            :size="20"
            :class="index > 0 ? '-ml-1.5 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : undefined"
          />
        </span>
        {{ $tChoice('discussions.viewing_now', otherViewers.length, { count: String(otherViewers.length) }) }}
      </p>
    </header>

    <div class="flex items-start gap-3">
      <div v-if="currentUser" class="mt-1 hidden shrink-0 sm:block">
        <UserAvatar :user="currentUser" :size="32" />
      </div>
      <CommentComposer
        ref="rootComposer"
        class="min-w-0 flex-1"
        collapsible
        discover-formatting
        :placeholder="composerPlaceholder"
        :mentionables
        :modes="composerModes"
        :images="capabilities.images"
        :duties="meta.me.duties"
        :default-dutiable-id="meta.me.default_dutiable_id"
        :submitting="posting"
        @submit="onPost"
      />
    </div>

    <div v-if="loading" class="space-y-4" aria-busy="true">
      <div v-for="n in 2" :key="n" class="flex gap-3">
        <Skeleton class="size-8 shrink-0" />
        <div class="flex-1 space-y-2">
          <Skeleton class="h-3 w-40" />
          <Skeleton class="h-3 w-full" />
        </div>
      </div>
    </div>

    <template v-else>
      <p v-if="!rootCount" class="border-t border-border pt-4 text-sm text-muted-foreground" data-testid="discussion-empty">
        {{ emptyText }}
      </p>

      <!-- Topics -->
      <div v-if="layout !== 'conversation' && topics.length" class="space-y-2" data-slot="discussion-topics">
        <h3 v-if="plainComments.length" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
          {{ $t('discussions.topic.heading') }} · {{ topics.length }}
        </h3>
        <DiscussionTopic
          v-for="topic in visibleTopics"
          :key="topic.id"
          :topic
          :mentionables
          :submitting="mutating"
          :default-open="topic.id === openTopicId"
          :images="capabilities.images"
          :duties="meta.me.duties"
          :default-dutiable-id="meta.me.default_dutiable_id"
          @reply="onReply"
          @update="discussion.update"
          @delete="discussion.remove"
          @toggle-reaction="discussion.toggleReaction"
          @close="discussion.close"
          @reopen="discussion.reopen"
        />
        <button
          v-if="closedTopics.length"
          type="button"
          data-testid="toggle-closed-topics"
          :aria-expanded="showClosed"
          class="inline-flex items-center gap-1.5 py-1 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground pointer-coarse:min-h-11"
          @click="showClosed = !showClosed"
        >
          <ChevronDown :class="['size-3.5 transition-transform', showClosed ? 'rotate-180' : '']" aria-hidden="true" />
          {{ showClosed ? $t('discussions.topic.hide_closed') : $tChoice('discussions.topic.show_closed', closedTopics.length, { count: String(closedTopics.length) }) }}
        </button>
      </div>

      <!-- Comments -->
      <div v-if="plainComments.length" data-slot="discussion-comments">
        <h3 v-if="layout !== 'conversation' && topics.length" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
          {{ $t('discussions.comments_heading') }} · {{ plainComments.length }}
        </h3>
        <div class="divide-y divide-border">
          <div v-for="comment in plainComments" :key="comment.id" class="py-4 first:pt-3">
            <CommentThread
              :comment
              :mentionables
              :submitting="mutating"
              :images="capabilities.images"
              :duties="meta.me.duties"
              :default-dutiable-id="meta.me.default_dutiable_id"
              @reply="onReply"
              @update="discussion.update"
              @delete="discussion.remove"
              @toggle-reaction="discussion.toggleReaction"
              @vote="discussion.vote"
            />
          </div>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref, useId, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ChevronDown, MessagesSquare } from '@lucide/vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CommentThread from '@/Components/Discussions/CommentThread.vue';
import DiscussionTopic from '@/Components/Discussions/DiscussionTopic.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import { useDiscussionThread } from '@/Composables/useDiscussionThread';
import type { CommentData, CommentDraft, ComposerMode } from '@/Types/discussions';

const CommentComposer = defineAsyncComponent(() => import('@/Components/Discussions/CommentComposer.vue'));

const props = withDefaults(defineProps<{
  commentableType: string;
  commentableId: string;
  /** Overrides the heading the record type implies ("Komentarai", "Diskusijos"). */
  title?: string;
  /** Off where a surrounding tab already names the discussion. */
  showTitle?: boolean;
  /** Changes when the parent's people change, so the @mention list is refetched without a reload. */
  mentionablesKey?: string;
}>(), {
  title: undefined,
  showTitle: true,
  mentionablesKey: undefined,
});

const titleId = useId();
const currentUser = computed(() => (usePage().props.auth as { user?: App.Entities.User } | undefined)?.user ?? null);
const discussion = useDiscussionThread(props.commentableType, props.commentableId);
const { comments, mentionables, meta, loading, posting, mutating, members } = discussion;

const rootComposer = ref<{ reset: () => void } | null>(null);
const showClosed = ref(false);
const openTopicId = ref<string | null>(null);

const capabilities = computed(() => meta.value.capabilities);
const layout = computed(() => capabilities.value.layout);

const composerModes = computed<ComposerMode[]>(() => {
  const { topics, polls } = capabilities.value;
  const modes: ComposerMode[] = ['comment', ...(topics ? ['topic' as const] : [])];
  return polls ? [...modes, 'poll'] : modes;
});

const resolvedTitle = computed(() => props.title ?? (layout.value === 'topics' ? $t('discussions.title_topics') : $t('Komentarai')));

const composerPlaceholder = computed(() => (layout.value === 'topics'
  ? $t('discussions.topics_placeholder')
  : undefined));

const emptyText = computed(() => {
  if (layout.value === 'topics') return $t('discussions.empty_topics');
  if (layout.value === 'conversation') return $t('discussions.empty_conversation');
  return $t('discussions.empty_comments');
});

const visibleRoots = computed(() => comments.value.filter(comment => !comment.is_erased || (comment.replies?.length ?? 0) > 0));
const rootCount = computed(() => visibleRoots.value.length);

function lastActivity(comment: CommentData): string {
  return comment.replies?.at(-1)?.created_at ?? comment.created_at ?? '';
}

const isTopic = (comment: CommentData) => comment.kind === 'topic';
const isClosed = (comment: CommentData) => Boolean(comment.is_closed ?? comment.is_resolved);

const topics = computed(() => (layout.value === 'conversation' ? [] : visibleRoots.value.filter(isTopic)));
const openTopics = computed(() => topics.value.filter(topic => !isClosed(topic)).sort((a, b) => lastActivity(b).localeCompare(lastActivity(a))));
const closedTopics = computed(() => topics.value.filter(isClosed));
const visibleTopics = computed(() => (showClosed.value ? [...openTopics.value, ...closedTopics.value] : openTopics.value));
const plainComments = computed(() => (layout.value === 'conversation' ? visibleRoots.value : visibleRoots.value.filter(comment => !isTopic(comment))));

const otherViewers = computed(() => members.value.filter(member => member.id !== currentUser.value?.id));

async function onPost(draft: CommentDraft) {
  const comment = await discussion.post(draft);
  if (comment) {
    rootComposer.value?.reset();
    if (comment.kind === 'topic') openTopicId.value = comment.id;
  }
}

async function onReply(parentId: string, draft: CommentDraft) {
  await discussion.post(draft, parentId);
}

watch(() => props.mentionablesKey, () => {
  void discussion.reloadMentionables();
});

onMounted(() => {
  void discussion.load();
});
</script>
