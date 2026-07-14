<template>
  <TooltipProvider>
    <Tooltip :delay-duration="200">
      <TooltipTrigger as-child>
        <div class="cursor-help leading-tight">
          <div class="whitespace-nowrap text-sm">
            {{ compact.primary }}
          </div>
          <div v-if="compact.secondary" class="text-xs text-muted-foreground">
            {{ compact.secondary }}
          </div>
        </div>
      </TooltipTrigger>
      <TooltipContent>
        <span class="whitespace-nowrap">{{ exact }}</span>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { formatStaticTime, isSameDay } from '@/Utils/IntlTime';

const props = defineProps<{
  startTime: string | Date;
  endTime: string | Date;
}>();

const locale = computed(() => usePage().props.app.locale);

const start = computed(() => new Date(props.startTime));
const end = computed(() => new Date(props.endTime));

/**
 * Drop whatever the two dates share, so the eye only has to read what actually differs:
 * a same-day booking is really a pair of times, and a same-month one is a pair of day numbers.
 * The full date-times stay one hover away, so nothing is lost.
 */
const compact = computed<{ primary: string; secondary?: string }>(() => {
  const sameDay = isSameDay(start.value, end.value);
  const sameMonth = start.value.getMonth() === end.value.getMonth()
    && start.value.getFullYear() === end.value.getFullYear();

  if (sameDay) {
    const day = formatStaticTime(start.value, { month: 'short', day: 'numeric' }, locale.value);
    const from = formatStaticTime(start.value, { hour: '2-digit', minute: '2-digit' }, locale.value);
    const to = formatStaticTime(end.value, { hour: '2-digit', minute: '2-digit' }, locale.value);

    return {
      primary: `${day}, ${from} → ${to}`,
      secondary: formatStaticTime(start.value, { year: 'numeric' }, locale.value),
    };
  }

  const from = formatStaticTime(start.value, { month: 'short', day: 'numeric' }, locale.value);
  const to = sameMonth
    ? formatStaticTime(end.value, { day: 'numeric' }, locale.value)
    : formatStaticTime(end.value, { month: 'short', day: 'numeric' }, locale.value);

  const startYear = formatStaticTime(start.value, { year: 'numeric' }, locale.value);
  const endYear = formatStaticTime(end.value, { year: 'numeric' }, locale.value);

  return {
    primary: `${from} → ${to}`,
    secondary: startYear === endYear ? startYear : `${startYear}–${endYear}`,
  };
});

const exact = computed(() => {
  const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  };

  return `${formatStaticTime(start.value, options, locale.value)} → ${formatStaticTime(end.value, options, locale.value)}`;
});
</script>
