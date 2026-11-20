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
import { formatDateWith, isSameDay, parseDate } from '@/Utils/dateTime';

const props = defineProps<{
  startTime: string | Date;
  endTime: string | Date;
}>();

const locale = computed(() => usePage().props.app.locale);

const start = computed(() => parseDate(props.startTime) ?? new Date(Number.NaN));
const end = computed(() => parseDate(props.endTime) ?? new Date(Number.NaN));

const format = (date: Date, fields: Intl.DateTimeFormatOptions) =>
  formatDateWith(date, fields, { locale: locale.value });

/**
 * Drop whatever the two dates share, so the eye only has to read what actually differs:
 * a same-day booking is really a pair of times, and a same-month one is a pair of day numbers.
 * The full date-times stay one hover away, so nothing is lost.
 */
const compact = computed<{ primary: string; secondary?: string }>(() => {
  const sameDay = isSameDay(start.value, end.value);
  const sameMonth = format(start.value, { year: 'numeric', month: 'numeric' })
    === format(end.value, { year: 'numeric', month: 'numeric' });

  if (sameDay) {
    const day = format(start.value, { month: 'short', day: 'numeric' });
    const from = format(start.value, { hour: '2-digit', minute: '2-digit' });
    const to = format(end.value, { hour: '2-digit', minute: '2-digit' });

    return {
      primary: `${day}, ${from} → ${to}`,
      secondary: format(start.value, { year: 'numeric' }),
    };
  }

  const from = format(start.value, { month: 'short', day: 'numeric' });
  const to = sameMonth
    ? format(end.value, { day: 'numeric' })
    : format(end.value, { month: 'short', day: 'numeric' });

  const startYear = format(start.value, { year: 'numeric' });
  const endYear = format(end.value, { year: 'numeric' });

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

  return `${format(start.value, options)} → ${format(end.value, options)}`;
});
</script>
