<template>
  <div
    :class="cn(
      'flex size-10 shrink-0 flex-col items-center justify-center rounded-lg bg-muted leading-none',
      props.class,
    )"
    data-slot="date-badge"
  >
    <span class="text-[10px] font-medium uppercase text-muted-foreground">
      {{ monthLabel }}
    </span>
    <span class="text-base font-bold text-foreground">
      {{ dayLabel }}
    </span>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';
import { formatDateWith, formatMonthAbbr } from '@/Utils/dateTime';

const props = withDefaults(defineProps<{
  /** Accepts an ISO string (what the API returns) or an already-parsed Date. */
  date: string | Date;
  class?: HTMLAttributes['class'];
}>(), {
  class: undefined,
});

const monthLabel = computed(() => formatMonthAbbr(props.date));
const dayLabel = computed(() => formatDateWith(props.date, { day: 'numeric' }));
</script>
