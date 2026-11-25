<template>
  <!-- The week before the season: a fir and the time left, nothing more. -->
  <div
    v-if="remaining"
    :class="['flex items-center gap-2', variant === 'hero' ? 'h-full items-end pb-7' : 'border-b border-border pb-4']"
    :data-slot="`season-countdown-${variant}`"
  >
    <span class="flex items-center gap-2 text-sm font-semibold text-foreground tabular-nums">
      <!-- The easter egg: touch the fir and it snows, for you alone. -->
      <button
        type="button"
        :class="[
          '-m-1 flex cursor-pointer items-center justify-center p-1 text-muted-foreground hover:text-foreground',
          'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring',
          'pointer-coarse:size-11 pointer-coarse:-m-3.5',
        ]"
        :aria-label="gusting ? $t('season_countdown.snow_stop') : $t('season_countdown.snow')"
        data-slot="season-countdown-snow"
        @click="toggle"
      >
        <!-- Greens over while it snows; with reduced motion this is all the answer the tap gets. -->
        <FirIcon
          :class="[
            'size-4 shrink-0 transition-[fill,color] duration-700',
            gusting ? 'fill-(--season-fir-light) text-foreground dark:fill-(--season-fir-edge)' : 'fill-transparent',
          ]"
        />
      </button>
      <span role="timer" data-slot="season-countdown-time">{{ remaining }}</span>
    </span>
  </div>
</template>

<script setup lang="ts">
import { useIntervalFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import { FirIcon } from '../FirIcon';
import { useSeasonSnow } from '../useSeasonSnow';

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

const { gusting, toggle } = useSeasonSnow();

const now = ref(Date.now());
// Ticks well under a minute, so the minutes shown never lag a whole minute behind.
useIntervalFn(() => {
  now.value = Date.now();
}, 10_000);

/** "4 d. 13 val. 20 min.", rounded up to the minute; empty once the season has started. */
const remaining = computed(() => {
  const left = Date.parse(props.startsAt) - now.value;
  if (!(left > 0)) {
    return '';
  }
  const totalMinutes = Math.ceil(left / MINUTE);
  const days = Math.floor(totalMinutes / (DAY / MINUTE));
  const hours = Math.floor((totalMinutes % (DAY / MINUTE)) / (HOUR / MINUTE));
  const minutes = String(totalMinutes % (HOUR / MINUTE));
  if (days > 0) {
    return $t('season_countdown.days_hours_minutes', { days: String(days), hours: String(hours), minutes });
  }
  return hours > 0
    ? $t('season_countdown.hours_minutes', { hours: String(hours), minutes })
    : $t('season_countdown.minutes', { minutes });
});
</script>
