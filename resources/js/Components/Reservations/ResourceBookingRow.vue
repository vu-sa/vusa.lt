<template>
  <li class="flex flex-wrap items-center justify-between gap-3 py-3" data-slot="resource-booking-row" :data-overdue="booking.overdue || undefined">
    <div class="min-w-0">
      <Link v-if="booking.href" :href="booking.href" class="block truncate text-sm font-medium hover:text-brand">
        {{ booking.name }}
      </Link>
      <span v-else class="block text-sm text-muted-foreground">{{ $t('reservations.resource.other_reservation') }}</span>
      <span class="mt-0.5 block text-xs text-muted-foreground tabular-nums">
        {{ booking.start_time && booking.end_time ? formatReservationPeriod(booking.start_time, booking.end_time) : '—' }}
      </span>
    </div>
    <div class="flex shrink-0 items-center gap-3">
      <span class="text-sm tabular-nums">{{ $t('reservations.resource.units', { count: String(booking.quantity) }) }}</span>
      <StatusBadge v-if="statusOf(booking.state)" :status="statusOf(booking.state)!" />
    </div>
  </li>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { formatReservationPeriod } from './reservationPeriod';

import { StatusBadge } from '@/Components/Patterns';
import { reservationResourceStatuses, type ReservationResourceStatus } from '@/Constants/statuses';

/** One reservation line on a resource, as `ResourceController::reservationRow()` shapes it. */
export interface ResourceBooking {
  id: number;
  reservation_id: string | null;
  name: string | null;
  href: string | null;
  quantity: number;
  state: string;
  start_time: number | null;
  end_time: number | null;
  overdue: boolean;
}

defineProps<{
  booking: ResourceBooking;
}>();

const statusOf = (state: string) => reservationResourceStatuses[state as ReservationResourceStatus];
</script>
