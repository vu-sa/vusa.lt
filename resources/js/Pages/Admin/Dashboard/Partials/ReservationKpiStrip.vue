<template>
  <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
    <button
      v-for="tile in tiles"
      :key="tile.status"
      type="button"
      :aria-pressed="modelValue === tile.status"
      :class="[
        'group flex flex-col items-start rounded-lg border px-3 py-2 text-left transition-colors',
        // Without a border these read as floating text against the dark background.
        'border-zinc-200 dark:border-zinc-800',
        'hover:bg-zinc-50 dark:hover:bg-zinc-800/50',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 dark:focus-visible:ring-zinc-300',
        // A selected tile is a live filter, so it has to be unmistakable.
        modelValue === tile.status
          ? 'border-zinc-900 bg-zinc-100 dark:border-zinc-100 dark:bg-zinc-800'
          : '',
        tile.count === 0 ? 'opacity-60' : '',
      ]"
      @click="toggle(tile.status)"
    >
      <span
        :class="[
          'flex items-center gap-2 text-sm',
          modelValue === tile.status ? 'font-medium text-foreground' : 'text-muted-foreground',
        ]"
      >
        <span :class="['size-2 shrink-0 rounded-full', tile.dot]" />
        {{ tile.label }}
        <span
          v-if="tile.unresolvedCount > 0"
          :title="$t('reservations.dashboard.kpi.unresolved_badge_title', { count: tile.unresolvedCount })"
          class="inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-400"
        >
          <TriangleAlert class="size-3" />
          {{ tile.unresolvedCount }}
        </span>
      </span>
      <span :class="['mt-1 text-4xl font-bold tabular-nums', tile.emphasis]">
        {{ tile.count }}
      </span>
      <span class="mt-0.5 text-xs text-muted-foreground">
        {{ tile.caption }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { TriangleAlert } from 'lucide-vue-next';

import { getStatusDotClass, type KpiStatus, type ReservationStats, type StatusFilter } from '@/Utils/ReservationStatus';

const props = defineProps<{
  stats: ReservationStats;
}>();

/**
 * The status filter the strip drives, shared with the status dropdown (which offers states the
 * strip has no tile for). Clicking the active tile returns to the default "active" view.
 */
const modelValue = defineModel<StatusFilter>({ default: 'active' });

const toggle = (status: KpiStatus) => {
  modelValue.value = modelValue.value === status ? 'active' : status;
};

const tiles = computed(() => [
  {
    status: 'created' as const,
    label: $t('reservations.dashboard.kpi.awaiting'),
    count: props.stats.awaiting,
    unresolvedCount: props.stats.awaitingUnresolved,
    caption: $tChoice('reservations.dashboard.kpi.awaiting_caption', props.stats.awaitingDueSoon, {
      count: props.stats.awaitingDueSoon,
    }),
    dot: getStatusDotClass('created'),
    emphasis: '',
  },
  {
    status: 'lent' as const,
    label: $t('reservations.dashboard.kpi.lent'),
    count: props.stats.lent,
    unresolvedCount: props.stats.lentUnresolved,
    caption: $tChoice('reservations.dashboard.kpi.lent_caption', props.stats.lentQuantity, {
      count: props.stats.lentQuantity,
    }),
    dot: getStatusDotClass('lent'),
    emphasis: '',
  },
  {
    status: 'returned' as const,
    label: $t('reservations.dashboard.kpi.returned'),
    count: props.stats.returnedLast30Days,
    unresolvedCount: 0,
    caption: $t('reservations.dashboard.kpi.returned_caption'),
    dot: getStatusDotClass('returned'),
    emphasis: '',
  },
]);
</script>
