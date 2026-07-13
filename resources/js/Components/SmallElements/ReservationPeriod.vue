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
          <div
            v-if="overdue && lateDays > 0"
            class="mt-0.5 inline-flex items-center gap-1 whitespace-nowrap text-xs font-medium text-amber-600 dark:text-amber-400"
          >
            <TriangleAlert class="size-3 shrink-0" />
            {{ $tChoice('reservations.overdue_days', lateDays, { count: lateDays }) }}
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
import { transChoice as $tChoice } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { TriangleAlert } from 'lucide-vue-next';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { formatStaticTime, isSameDay } from '@/Utils/IntlTime';

const props = defineProps<{
  startTime: string | Date;
  endTime: string | Date;
  /** Renders the "N days overdue" sub-line. Callers derive this from ReservationStatus. */
  overdue?: boolean;
}>();

const locale = computed(() => usePage().props.app.locale);

const start = computed(() => new Date(props.startTime));
const end = computed(() => new Date(props.endTime));

const lateDays = computed(() => {
  const elapsedMs = Date.now() - end.value.getTime();

  return Math.max(0, Math.floor(elapsedMs / (1000 * 60 * 60 * 24)));
});

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
