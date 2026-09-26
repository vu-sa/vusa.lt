<template>
  <Link
    :href="route('reservations.create')"
    class="flex items-start gap-3 px-3 py-4 hover:bg-secondary/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring pointer-coarse:min-h-11"
    data-slot="reservation-draft-row"
  >
    <ShoppingBasket class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
    <span class="min-w-0 flex-1">
      <span class="block text-pretty font-bold text-foreground">
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
    <span class="flex shrink-0 items-center gap-1 text-xs font-bold text-brand">
      {{ $t('reservations.cart.continue_short') }}
      <ArrowRight class="size-3.5" aria-hidden="true" />
    </span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { AlertTriangle, ArrowRight, ShoppingBasket } from 'lucide-vue-next';
import { computed } from 'vue';

import { formatReservationPeriod } from './reservationPeriod';

/** `SerializeReservationCart::summary()`, or the full cart, which carries the same fields. */
export interface ReservationDraftSummaryData {
  name: string | null;
  count: number;
  start_time: number | null;
  end_time: number | null;
  problemCount?: number;
}

const props = defineProps<{
  draft: ReservationDraftSummaryData;
}>();

const periodLabel = computed(() => (props.draft.start_time && props.draft.end_time
  ? formatReservationPeriod(props.draft.start_time, props.draft.end_time)
  : $t('reservations.cart.no_period')));
</script>
