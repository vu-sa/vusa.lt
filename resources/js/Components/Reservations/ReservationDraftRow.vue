<template>
  <Link
    :href="route('reservations.create')"
    :class="ROW_LINK_CLASS"
    data-slot="reservation-draft-row"
  >
    <span class="min-w-0 flex-1">
      <span class="block text-pretty font-semibold text-foreground">
        {{ $t('reservations.cart.finish_named', { name: draft.name || $t('reservations.cart.untitled') }) }}
      </span>
      <span class="mt-0.5 block text-xs text-muted-foreground tabular-nums">
        {{ $tChoice('reservations.cart.items', draft.count, { count: String(draft.count) }) }} · {{ periodLabel }}
      </span>
      <span v-if="(draft.problemCount ?? 0) > 0" class="mt-0.5 flex items-center gap-1 text-xs text-status-danger">
        <AlertTriangle class="size-3.5 shrink-0" aria-hidden="true" />
        {{ $t('reservations.cart.conflicts_title') }}
      </span>
    </span>
    <span class="flex shrink-0 items-center gap-1 text-xs font-semibold text-brand">
      {{ $t('reservations.cart.continue_short') }}
      <ArrowRight class="size-3.5" aria-hidden="true" />
    </span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { AlertTriangle, ArrowRight } from '@lucide/vue';
import { computed } from 'vue';

import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';

import { formatReservationPeriod } from './reservationPeriod';

import type { ReservationDraftSummaryData } from './types';

const props = defineProps<{
  draft: ReservationDraftSummaryData;
}>();

const periodLabel = computed(() => (props.draft.start_time && props.draft.end_time
  ? formatReservationPeriod(props.draft.start_time, props.draft.end_time)
  : $t('reservations.cart.no_period')));
</script>
