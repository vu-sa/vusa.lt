<template>
  <div class="mt-2 space-y-1.5">
    <!-- Meta row -->
    <div class="flex items-center justify-between text-xs text-muted-foreground">
      <span>{{ $t('Balsų: :count', { count: poll.total_votes }) }}</span>
      <span v-if="poll.is_closed" class="inline-flex items-center gap-1 font-medium text-muted-foreground">
        <Lock class="h-3 w-3" />
        {{ $t('Apklausa uždaryta') }}
      </span>
      <span v-else-if="closesLabel" class="inline-flex items-center gap-1">
        <Clock class="h-3 w-3" />
        {{ closesLabel }}
      </span>
      <span v-else-if="poll.allow_multiple">{{ $t('Galima rinktis kelis') }}</span>
    </div>

    <!-- Options -->
    <button
      v-for="option in poll.options"
      :key="option.id"
      type="button"
      :disabled="poll.is_closed"
      :class="[
        'relative w-full overflow-hidden border px-3 py-2 text-left transition-colors pointer-coarse:min-h-11',
        isMine(option.id)
          ? 'border-brand/50 bg-brand/5'
          : 'border-border hover:bg-accent',
        poll.is_closed ? 'cursor-default' : 'cursor-pointer',
      ]"
      @click="!poll.is_closed && $emit('vote', option.id)"
    >
      <!-- Fill bar -->
      <span
        class="absolute inset-y-0 left-0 -z-0 bg-brand/10 transition-[width] duration-300"
        :style="{ width: `${percentage(option.id)}%` }"
        aria-hidden="true"
      />
      <span class="relative z-10 flex items-center justify-between gap-3 text-sm">
        <span class="flex min-w-0 items-center gap-2">
          <component :is="isMine(option.id) ? CheckCircle2 : Circle" class="h-4 w-4 shrink-0" :class="isMine(option.id) ? 'text-brand' : 'text-muted-foreground'" />
          <span class="truncate font-medium text-foreground">{{ option.label }}</span>
        </span>
        <span class="shrink-0 tabular-nums text-xs text-muted-foreground">
          {{ count(option.id) }} · {{ percentage(option.id) }}%
        </span>
      </span>

      <!-- Voters -->
      <span v-if="voters(option.id).length" class="relative z-10 mt-1 block truncate text-left text-xs text-muted-foreground">
        {{ voters(option.id).map(v => v.name).filter(Boolean).join(', ') }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CheckCircle2, Circle, Clock, Lock } from 'lucide-vue-next';

import { formatRelativeTime } from '@/Utils/IntlTime';
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
  if (props.poll.total_votes === 0) {
    return 0;
  }
  return Math.round((count(optionId) / props.poll.total_votes) * 100);
}

/**
 * `my_option_ids` is recomputed from voters on broadcast (the broadcast payload
 * carries no per-user truth) — but the resource fills it for the requester, so
 * we trust it directly.
 */
function isMine(optionId: string): boolean {
  return props.poll.my_option_ids.includes(optionId);
}

const closesLabel = computed(() => {
  if (!props.poll.closes_at || props.poll.is_closed) {
    return '';
  }
  return $t('Uždaroma :time', { time: formatRelativeTime(new Date(props.poll.closes_at)) });
});
</script>
