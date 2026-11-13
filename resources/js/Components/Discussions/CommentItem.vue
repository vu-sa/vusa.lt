<template>
  <div v-if="comment.is_erased" class="flex items-center gap-2.5 py-1 text-sm text-muted-foreground" data-testid="comment-erased">
    <Ban class="size-4 shrink-0" aria-hidden="true" />
    <span class="italic">{{ $t('Komentaras ištrintas') }}</span>
  </div>
  <article
    v-else
    class="group/comment relative flex gap-3"
    data-slot="comment-item"
    :data-comment-id="comment.id"
  >
    <UserAvatar :user="avatarUser" :size="isReply ? 28 : 32" class="mt-0.5 shrink-0" />

    <div class="min-w-0 flex-1">
      <header :class="['flex items-start gap-2', headerReserve]">
        <div class="min-w-0 flex-1">
          <p class="flex flex-wrap items-baseline gap-x-2 text-sm leading-snug">
            <span class="font-semibold text-foreground">{{ comment.user.name }}</span>
            <time class="text-xs text-muted-foreground" :datetime="comment.created_at ?? undefined" :title="absoluteTime">{{ relativeTime }}</time>
            <span v-if="comment.edited_at" class="text-xs text-muted-foreground">· {{ $t('redaguota') }}</span>
            <span v-if="votedOptionLabel" class="inline-flex items-center gap-1 text-xs font-semibold text-brand">
              <CheckCircle2 class="size-3" aria-hidden="true" />
              {{ $t('Balsavo: :option', { option: votedOptionLabel }) }}
            </span>
          </p>
          <p v-if="dutyLabel" class="truncate text-xs text-muted-foreground" :title="dutyLabel" data-testid="comment-author-duty">
            {{ dutyLabel }}
          </p>
        </div>
      </header>

      <CommentComposer
        v-if="editing"
        class="mt-2"
        :mentionables
        :content="comment.body"
        :submit-label="$t('Išsaugoti')"
        :submitting
        show-cancel
        autofocus
        quiet-mentions
        @submit="onEditSubmit"
        @cancel="editing = false"
      />
      <template v-else>
        <div v-if="comment.body" class="comment-content mt-1 max-w-none text-sm text-foreground" v-html="comment.body" />
        <CommentImages v-if="comment.images?.length" :images="comment.images" />
        <PollBlock v-if="comment.kind === 'poll' && comment.poll" :poll="comment.poll" @vote="(optionId) => $emit('vote', comment.id, optionId)" />

        <footer
          :class="['flex flex-wrap items-center gap-x-1 gap-y-1', comment.reactions.length ? 'mt-1.5' : 'pointer-coarse:mt-1.5']"
          data-slot="comment-footer"
        >
          <CommentReactions v-if="comment.reactions.length" :reactions="comment.reactions" @toggle="toggle" />

          <!-- Pinned to the comment's corner with a mouse; inline under the comment on touch. -->
          <div
            role="toolbar"
            :aria-label="$t('discussions.comment_actions')"
            data-slot="comment-action-bar"
            class="flex items-center pointer-fine:absolute pointer-fine:top-0 pointer-fine:right-0"
          >
            <ReactionPicker v-slot="{ toggle: togglePicker, open: pickerOpen, hover }" :mine="myReactions" @select="toggle">
              <button
                type="button"
                data-testid="comment-like"
                :aria-pressed="likedByMe"
                :aria-label="$t('discussions.reactions.like')"
                :class="[
                  actionClass,
                  likedByMe ? 'text-brand hover:text-brand' : 'text-muted-foreground hover:text-foreground',
                  // On touch the 👍 chip is already right beside it.
                  hasLikeChip ? 'pointer-coarse:hidden' : '',
                ]"
                @click="toggle('👍')"
              >
                <ThumbsUp :class="['size-4', likedByMe ? 'fill-current' : '']" />
              </button>
              <button
                type="button"
                data-testid="comment-react"
                data-reaction-trigger
                :aria-label="$t('discussions.reactions.add')"
                :aria-expanded="pickerOpen"
                :class="[actionClass, pickerOpen ? 'bg-accent text-foreground' : 'text-muted-foreground hover:text-foreground']"
                v-bind="hover"
                @click="togglePicker"
              >
                <SmilePlus class="size-4" />
              </button>
            </ReactionPicker>
            <button
              v-if="canReply"
              type="button"
              data-testid="comment-reply"
              :aria-label="$t('Atsakyti')"
              :aria-expanded="replyActive"
              :title="$t('Atsakyti')"
              :class="[
                actionClass,
                'pointer-coarse:w-auto pointer-coarse:gap-1 pointer-coarse:px-2 pointer-coarse:text-xs pointer-coarse:font-semibold',
                replyActive ? 'bg-brand/10 text-brand hover:bg-brand/15' : 'text-muted-foreground hover:text-foreground',
              ]"
              @click="$emit('reply', comment.id)"
            >
              <CornerDownRight class="size-4" aria-hidden="true" />
              <span class="pointer-fine:sr-only">{{ $t('Atsakyti') }}</span>
            </button>
            <DropdownMenu v-if="hasActions">
              <DropdownMenuTrigger as-child>
                <button type="button" data-testid="comment-actions" :aria-label="$t('discussions.comment_actions')" :class="[actionClass, 'text-muted-foreground hover:text-foreground']">
                  <MoreHorizontal class="size-4" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem v-if="comment.can.update" data-testid="comment-edit" @click="editing = true">
                  <Pencil class="size-4" />
                  {{ $t('Redaguoti') }}
                </DropdownMenuItem>
                <DropdownMenuItem v-if="comment.can.delete" data-testid="comment-delete" class="text-destructive focus:text-destructive" @click="$emit('delete', comment.id)">
                  <Trash2 class="size-4" />
                  {{ $t('Ištrinti') }}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </footer>
      </template>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Ban, CheckCircle2, CornerDownRight, MoreHorizontal, Pencil, SmilePlus, ThumbsUp, Trash2 } from '@lucide/vue';

import './comment-content.css';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import CommentImages from '@/Components/Discussions/CommentImages.vue';
import CommentReactions from '@/Components/Discussions/CommentReactions.vue';
import PollBlock from '@/Components/Discussions/PollBlock.vue';
import ReactionPicker from '@/Components/Discussions/ReactionPicker.vue';
import { authorDutyLabel, authorDutyPeriod, endedBefore } from '@/Components/Discussions/authorDuty';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import type { CommentData, CommentDraft, MentionableUser, PollData } from '@/Types/discussions';

const CommentComposer = defineAsyncComponent(() => import('@/Components/Discussions/CommentComposer.vue'));

const props = withDefaults(defineProps<{
  comment: CommentData;
  mentionables?: MentionableUser[];
  isReply?: boolean;
  canReply?: boolean;
  submitting?: boolean;
  /** This comment's reply box is open, so its reply button reads as pressed. */
  replyActive?: boolean;
  /** For a reply under a poll: badges the option its author voted for. */
  pollContext?: PollData | null;
}>(), {
  mentionables: () => [],
  isReply: false,
  canReply: true,
  replyActive: false,
  submitting: false,
  pollContext: null,
});

const emit = defineEmits<{
  'reply': [id: string];
  'update': [id: string, body: string];
  'delete': [id: string];
  'toggle-reaction': [id: string, emoji: string];
  'vote': [id: string, optionId: string];
}>();

const editing = ref(false);

const avatarUser = computed(() => ({
  id: props.comment.user.id ?? '',
  name: props.comment.user.name ?? '',
  profile_photo_path: props.comment.user.profile_photo_path,
}));

const dutyLabel = computed(() => {
  const duty = props.comment.author_duty;
  if (!duty) return '';
  const label = authorDutyLabel(duty, props.comment.user);
  return endedBefore(duty, props.comment.created_at) ? `${label} · ${authorDutyPeriod(duty)}` : label;
});

const votedOptionLabel = computed(() => {
  if (!props.pollContext) {
    return '';
  }
  const tally = props.pollContext.tallies.find(t => t.voters.some(v => v.id === props.comment.user.id));
  return tally ? props.pollContext.options.find(o => o.id === tally.option_id)?.label ?? '' : '';
});

const hasActions = computed(() => props.comment.can.update || props.comment.can.delete);
// The pinned bar sits over the header's right end; keep the name and duty clear of its buttons.
const headerReserve = computed(() => ({
  2: 'pointer-fine:pr-18',
  3: 'pointer-fine:pr-26',
  4: 'pointer-fine:pr-34',
} as Record<number, string>)[2 + Number(props.canReply) + Number(hasActions.value)]);
const myReactions = computed(() => props.comment.reactions.filter(tally => tally.reacted_by_me).map(tally => tally.emoji));
const likedByMe = computed(() => myReactions.value.includes('👍'));
const hasLikeChip = computed(() => props.comment.reactions.some(tally => tally.emoji === '👍'));
const actionClass = 'flex size-8 items-center justify-center transition-[color,background-color,transform] duration-150 motion-safe:active:scale-90 hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring pointer-coarse:size-11';

function toggle(emoji: string) {
  emit('toggle-reaction', props.comment.id, emoji);
}

const { formatNearDate, formatDateTime } = useDateFormatter();
const relativeTime = computed(() => formatNearDate(props.comment.created_at));
const absoluteTime = computed(() => (props.comment.created_at ? formatDateTime(props.comment.created_at) : undefined));

function onEditSubmit(draft: CommentDraft) {
  emit('update', props.comment.id, draft.body);
  editing.value = false;
}
</script>
