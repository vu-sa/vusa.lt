<template>
  <div class="mt-2 max-w-xl" data-slot="poll-block">
    <ul class="space-y-1" :aria-label="$t('discussions.poll.options')">
      <li v-for="option in poll.options" :key="option.id">
        <button
          type="button"
          :disabled="poll.is_closed"
          :aria-pressed="isMine(option.id)"
          data-testid="poll-option-vote"
          :class="[
            'relative flex w-full items-center gap-2.5 overflow-hidden border px-3 py-2 text-left text-sm transition-colors pointer-coarse:min-h-11',
            isMine(option.id) ? 'border-brand/60' : 'border-border',
            poll.is_closed ? 'cursor-default' : 'hover:border-foreground/40',
          ]"
          @click="$emit('vote', option.id)"
        >
          <span
            :class="['absolute inset-y-0 left-0 transition-[width] duration-300', isMine(option.id) ? 'bg-brand/12' : 'bg-muted']"
            :style="{ width: `${percentage(option.id)}%` }"
            aria-hidden="true"
          />
          <component
            :is="indicator(option.id)"
            :class="['relative size-4 shrink-0', isMine(option.id) ? 'text-brand' : 'text-muted-foreground']"
            aria-hidden="true"
          />
          <span class="relative min-w-0 flex-1 font-medium text-foreground">{{ option.label }}</span>
          <span v-if="voters(option.id).length" class="relative hidden items-center sm:flex" aria-hidden="true">
            <UserAvatar
              v-for="(voter, index) in voters(option.id).slice(0, 4)"
              :key="voter.id"
              :user="{ id: voter.id, name: voter.name ?? '' }"
              :size="18"
              :class="index > 0 ? '-ml-1.5 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : undefined"
            />
          </span>
          <span class="relative w-16 shrink-0 text-right text-xs tabular-nums text-muted-foreground">
            <span class="font-semibold text-foreground">{{ percentage(option.id) }}%</span> · {{ count(option.id) }}
          </span>
        </button>
      </li>
    </ul>

    <p class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground">
      <span>{{ $tChoice('discussions.poll.votes_count', poll.total_votes, { count: String(poll.total_votes) }) }}</span>
      <template v-if="poll.allow_multiple">
        <span aria-hidden="true">·</span>
        <span>{{ $t('discussions.poll.allow_multiple') }}</span>
      </template>
      <template v-if="poll.is_closed">
        <span aria-hidden="true">·</span>
        <span class="inline-flex items-center gap-1 font-semibold"><Lock class="size-3" aria-hidden="true" />{{ $t('discussions.poll.closed') }}</span>
      </template>
      <template v-else-if="closesLabel">
        <span aria-hidden="true">·</span>
        <span>{{ closesLabel }}</span>
      </template>
      <template v-if="voterNames">
        <span aria-hidden="true">·</span>
        <Popover>
          <PopoverTrigger as-child>
            <button type="button" class="font-semibold underline decoration-border underline-offset-4 hover:text-foreground pointer-coarse:min-h-11">
              {{ $t('discussions.poll.who_voted') }}
            </button>
          </PopoverTrigger>
          <PopoverContent align="start" class="w-[min(20rem,calc(100vw-2rem))] p-0">
            <dl class="divide-y divide-border text-sm">
              <div v-for="option in poll.options" :key="option.id" class="px-3 py-2">
                <dt class="text-xs font-semibold text-muted-foreground">{{ option.label }} · {{ count(option.id) }}</dt>
                <dd class="mt-0.5">{{ voters(option.id).map(voter => voter.name).filter(Boolean).join(', ') || '—' }}</dd>
              </div>
            </dl>
          </PopoverContent>
        </Popover>
      </template>
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { CheckCircle2, CheckSquare, Circle, Lock, Square } from '@lucide/vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import type { PollData, PollVoter } from '@/Types/discussions';

const props = defineProps<{ poll: PollData }>();

defineEmits<{ vote: [optionId: string] }>();

const talliesByOption = computed(() =>
  Object.fromEntries(props.poll.tallies.map(tally => [tally.option_id, tally])),
);

function count(optionId: string): number {
  return talliesByOption.value[optionId]?.count ?? 0;
}

function voters(optionId: string): PollVoter[] {
  return talliesByOption.value[optionId]?.voters ?? [];
}

function percentage(optionId: string): number {
  return props.poll.total_votes === 0 ? 0 : Math.round((count(optionId) / props.poll.total_votes) * 100);
}

function isMine(optionId: string): boolean {
  return props.poll.my_option_ids.includes(optionId);
}

function indicator(optionId: string) {
  if (props.poll.allow_multiple) {
    return isMine(optionId) ? CheckSquare : Square;
  }
  return isMine(optionId) ? CheckCircle2 : Circle;
}

const voterNames = computed(() => props.poll.tallies.some(tally => tally.voters.length > 0));

const { formatNearDate } = useDateFormatter();

const closesLabel = computed(() => (props.poll.closes_at && !props.poll.is_closed
  ? $t('Uždaroma :time', { time: formatNearDate(props.poll.closes_at) })
  : ''));
</script>
