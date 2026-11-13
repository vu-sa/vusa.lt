<template>
  <!-- The week before the season: a fir and the time left, nothing more. -->
  <div
    v-if="remaining"
    :class="['flex items-center gap-2', variant === 'hero' ? 'h-full items-end pb-7' : 'border-b border-border pb-4']"
    :data-slot="`season-countdown-${variant}`"
  >
    <span class="flex items-center gap-2 text-sm font-bold text-foreground tabular-nums" role="timer" data-slot="season-countdown-time">
      <FirIcon class="size-4 shrink-0 text-muted-foreground" />
      {{ remaining }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { useIntervalFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import { FirIcon } from '../FirIcon';

const props = withDefaults(defineProps<{
  /** When the season starts (ISO, Vilnius offset). */
  startsAt: string;
  variant?: 'hero' | 'row';
}>(), {
  variant: 'hero',
});

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

const now = ref(Date.now());
useIntervalFn(() => {
  now.value = Date.now();
}, MINUTE);

/** "4 d. 13 val.", and minutes once less than an hour is left; empty once the season has started. */
const remaining = computed(() => {
  const left = Date.parse(props.startsAt) - now.value;
  if (!(left > 0)) {
    return '';
  }
  const days = Math.floor(left / DAY);
  const hours = Math.floor((left % DAY) / HOUR);
  if (days > 0) {
    return $t('season_countdown.days_hours', { days: String(days), hours: String(hours) });
  }
  return hours > 0
    ? $t('season_countdown.hours', { hours: String(hours) })
    : $t('season_countdown.minutes', { minutes: String(Math.max(1, Math.ceil((left % HOUR) / MINUTE))) });
});
</script>
