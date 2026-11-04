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

import {
  DEFAULT_LOCALE,
  formatDate,
  formatDateTime,
  formatDateWith,
  formatNearDate,
  type DateInput,
} from '@/Utils/dateTime';

const props = withDefaults(defineProps<{
  date?: DateInput;
  mode?: 'absolute' | 'relative';
  /** Intl fields for an absolute date; the YYYY-MM-DD table date when omitted. */
  format?: Intl.DateTimeFormatOptions;
  locale?: string;
}>(), {
  date: undefined,
  mode: 'absolute',
  format: undefined,
  locale: DEFAULT_LOCALE,
});

const displayValue = computed(() => {
  const value = props.mode === 'relative'
    ? formatNearDate(props.date, { locale: props.locale })
    : props.format
      ? formatDateWith(props.date, props.format, { locale: props.locale })
      : formatDate(props.date);

  return value || '—';
});

/**
 * Only relative dates hide something: "prieš 2 d." does not say which day.
 * An absolute date already reads in full, so it gets no tooltip.
 */
const tooltipValue = computed(() => {
  if (props.mode !== 'relative') return undefined;

  return formatDateTime(props.date, { locale: props.locale }) || undefined;
});
</script>
