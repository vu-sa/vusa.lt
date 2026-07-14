<template>
  <div :class="['group/comment flex gap-2.5', isReply ? '' : '']">
    <UserAvatar :user="(comment.user as any)" :size="32" class="mt-0.5 shrink-0" />

    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2 text-sm">
        <span class="font-medium text-foreground">{{ comment.user.name }}</span>
        <span class="text-xs text-muted-foreground">{{ relativeTime }}</span>
        <span v-if="comment.edited_at" class="text-xs text-muted-foreground/70">({{ $t('redaguota') }})</span>
        <span
          v-if="votedOptionLabel"
          class="inline-flex items-center gap-1 rounded-full bg-vusa-red/10 px-1.5 py-0.5 text-xs font-medium text-vusa-red"
        >
          <CheckCircle2 class="h-3 w-3" />
          {{ $t('Balsavo: :option', { option: votedOptionLabel }) }}
        </span>
        <span
          v-if="comment.is_resolved"
          class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400"
        >
          <CheckCircle2 class="h-3 w-3" />
          {{ $t('Išspręsta') }}
        </span>

        <DropdownMenu v-if="hasActions">
          <DropdownMenuTrigger as-child>
            <button
              type="button"
              class="ml-auto flex h-6 w-6 items-center justify-center rounded-md text-muted-foreground opacity-0 transition-opacity hover:bg-zinc-100 group-hover/comment:opacity-100 dark:hover:bg-zinc-800"
            >
              <MoreHorizontal class="h-4 w-4" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem v-if="comment.can.update" @click="startEdit">
              <Pencil class="mr-2 h-4 w-4" />
              {{ $t('Redaguoti') }}
            </DropdownMenuItem>
            <DropdownMenuItem v-if="canResolve && !comment.is_resolved" @click="$emit('resolve', comment.id)">
              <CheckCircle2 class="mr-2 h-4 w-4" />
              {{ $t('Pažymėti išspręsta') }}
            </DropdownMenuItem>
            <DropdownMenuItem v-if="canResolve && comment.is_resolved" @click="$emit('unresolve', comment.id)">
              <RotateCcw class="mr-2 h-4 w-4" />
              {{ $t('Atžymėti') }}
            </DropdownMenuItem>
            <DropdownMenuItem v-if="comment.can.delete" class="text-destructive focus:text-destructive" @click="$emit('delete', comment.id)">
              <Trash2 class="mr-2 h-4 w-4" />
              {{ $t('Ištrinti') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <!-- Edit mode -->
      <CommentComposer
        v-if="editing"
        class="mt-1.5"
        :mentionables
        :content="comment.body"
        :submit-label="$t('Išsaugoti')"
        :submitting
        show-cancel
        autofocus
        @submit="onEditSubmit"
        @cancel="editing = false"
      />

      <!-- Read mode -->
      <div
        v-else
        class="prose prose-sm mt-0.5 max-w-none text-sm text-foreground dark:prose-invert [&_.comment-mention]:rounded [&_.comment-mention]:bg-vusa-red/10 [&_.comment-mention]:px-1 [&_.comment-mention]:font-medium [&_.comment-mention]:text-vusa-red"
        v-html="comment.body"
      />

      <!-- Poll -->
      <PollBlock
        v-if="!editing && comment.kind === 'poll' && comment.poll"
        :poll="comment.poll"
        @vote="(optionId) => $emit('vote', comment.id, optionId)"
      />

      <div v-if="!editing" class="mt-1.5 flex items-center gap-3">
        <CommentReactions :reactions="comment.reactions" @toggle="(emoji) => $emit('toggle-reaction', comment.id, emoji)" />
        <button
          v-if="canReply"
          type="button"
          class="inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
          @click="$emit('reply', comment.id)"
        >
          <CornerDownRight class="h-3.5 w-3.5" />
          {{ $t('Atsakyti') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CheckCircle2, CornerDownRight, MoreHorizontal, Pencil, RotateCcw, Trash2 } from 'lucide-vue-next';

import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import CommentReactions from '@/Components/Discussions/CommentReactions.vue';
import PollBlock from '@/Components/Discussions/PollBlock.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { formatRelativeTime } from '@/Utils/IntlTime';
import type { CommentData, MentionableUser, PollData } from '@/Types/discussions';

const props = withDefaults(defineProps<{
  comment: CommentData;
  mentionables?: MentionableUser[];
  isReply?: boolean;
  canReply?: boolean;
  submitting?: boolean;
  // For a reply: the parent poll, used to badge the author's chosen option.
  pollContext?: PollData | null;
}>(), {
  mentionables: () => [],
  isReply: false,
  canReply: true,
  submitting: false,
  pollContext: null,
});

const emit = defineEmits<{
  'reply': [id: string];
  'update': [id: string, body: string];
  'delete': [id: string];
  'resolve': [id: string];
  'unresolve': [id: string];
  'toggle-reaction': [id: string, emoji: string];
  'vote': [id: string, optionId: string];
}>();

const editing = ref(false);

// When this comment is a reply under a poll, surface which option its author
// voted for (their reply is the "why" behind that vote).
const votedOptionLabel = computed(() => {
  if (!props.pollContext) {
    return '';
  }
  const tally = props.pollContext.tallies.find(t => t.voters.some(v => v.id === props.comment.user.id));
  if (!tally) {
    return '';
  }
  return props.pollContext.options.find(o => o.id === tally.option_id)?.label ?? '';
});

// Resolve applies to thread roots only.
const canResolve = computed(() => props.comment.can.resolve && !props.isReply);
const hasActions = computed(() => props.comment.can.update || props.comment.can.delete || canResolve.value);

const relativeTime = computed(() => (props.comment.created_at ? formatRelativeTime(new Date(props.comment.created_at)) : ''));

function startEdit() {
  editing.value = true;
}

function onEditSubmit(html: string) {
  emit('update', props.comment.id, html);
  editing.value = false;
}
</script>
