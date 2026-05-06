<template>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger as-child>
        <span class="cursor-help truncate block">
          {{ displayValue }}
        </span>
      </TooltipTrigger>
      <TooltipContent side="top" align="start">
        <p>{{ tooltipValue }}</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { LocaleEnum } from '@/Types/enums';
import { formatRelativeTime, formatStaticTime } from '@/Utils/IntlTime';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/Components/ui/tooltip';

const props = withDefaults(defineProps<{
  date?: string | Date | null;
  mode?: 'absolute' | 'relative';
  format?: Intl.DateTimeFormatOptions;
  relativeOptions?: Intl.RelativeTimeFormatOptions;
  locale?: LocaleEnum;
}>(), {
  mode: 'absolute',
  format: () => ({ year: 'numeric', month: '2-digit', day: '2-digit' }),
  relativeOptions: () => ({ numeric: 'auto' }),
  locale: LocaleEnum.LT,
});

const displayValue = computed(() => {
  if (!props.date) return '—';

  if (props.mode === 'relative') {
    return formatRelativeTime(props.date, props.relativeOptions, props.locale);
  }

  return formatStaticTime(props.date, props.format, props.locale);
});

const tooltipValue = computed(() => {
  if (!props.date) return undefined;

  if (props.mode === 'relative') {
    return formatStaticTime(props.date, {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }, props.locale);
  }

  return formatRelativeTime(props.date, { numeric: 'auto' }, props.locale);
});
</script>
