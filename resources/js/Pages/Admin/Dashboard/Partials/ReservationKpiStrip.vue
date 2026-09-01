<template>
  <div class="grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
    <StatCard
      v-for="tile in tiles"
      :key="tile.status"
      interactive
      :label="tile.label"
      :value="tile.count"
      :caption="tile.caption"
      :dot="tile.dot"
      :active="modelValue === tile.status"
      :muted="tile.count === 0"
      @click="toggle(tile.status)"
    >
      <template #badge>
        <span
          v-if="tile.unresolvedCount > 0"
          :title="$t('reservations.dashboard.kpi.unresolved_badge_title', { count: tile.unresolvedCount })"
          class="inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-400"
        >
          <TriangleAlert class="size-3" />
          {{ tile.unresolvedCount }}
        </span>
      </template>
    </StatCard>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { TriangleAlert } from 'lucide-vue-next';

import { StatCard } from '@/Components/Patterns';
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
  },
  {
    status: 'returned' as const,
    label: $t('reservations.dashboard.kpi.returned'),
    count: props.stats.returnedLast30Days,
    unresolvedCount: 0,
    caption: $t('reservations.dashboard.kpi.returned_caption'),
    dot: getStatusDotClass('returned'),
  },
]);
</script>
