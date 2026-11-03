<template>
  <div
    :class="cn(
      'flex w-16 shrink-0 flex-col items-center justify-center border-b-2 border-brand bg-background/90 px-2 py-2 leading-none backdrop-blur-sm',
      props.class,
    )"
    data-slot="date-plate"
  >
    <span class="text-[0.625rem] font-bold uppercase tracking-[0.16em] text-muted-foreground">
      {{ monthLabel }}
    </span>
    <span class="mt-0.5 text-xl font-bold text-foreground">{{ dayLabel }}</span>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';
import { formatDateWith, formatMonthAbbr } from '@/Utils/dateTime';

/**
 * The square day/month plate that overlays an event image.
 *
 * The public counterpart to `Patterns/DateBadge` — same information, different language: square
 * instead of `rounded-lg`, brand rule instead of a muted fill, and sized to sit legibly on top
 * of a photograph rather than inline in a list.
 */
const props = withDefaults(defineProps<{
  /** ISO string (what the API returns) or an already-parsed Date. */
  date: string | Date;
  class?: HTMLAttributes['class'];
}>(), {
  class: undefined,
});

const dayLabel = computed(() => formatDateWith(props.date, { day: '2-digit' }));

/** Month sits above the day, as on the printed key visuals. */
const monthLabel = computed(() => formatMonthAbbr(props.date));
</script>
