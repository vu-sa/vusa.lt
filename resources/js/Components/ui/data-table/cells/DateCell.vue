<template>
  <CellTooltip :text="tooltipValue" :enabled="mode === 'relative'">
    <span :class="['block truncate', mode === 'relative' && 'cursor-help']">
      {{ displayValue }}
    </span>
  </CellTooltip>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import CellTooltip from './CellTooltip.vue';

import { LocaleEnum } from '@/Types/enums';
import { formatRelativeTime, formatStaticTime } from '@/Utils/IntlTime';

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

/**
 * Only relative dates hide something: "2 days ago" does not say which day.
 * An absolute date already reads in full, so it gets no tooltip.
 */
const tooltipValue = computed(() => {
  if (!props.date || props.mode !== 'relative') return undefined;

  return formatStaticTime(props.date, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }, props.locale);
});
</script>
