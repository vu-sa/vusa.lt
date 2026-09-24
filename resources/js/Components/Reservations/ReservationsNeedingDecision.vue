<template>
  <OverviewSection
    :title="$t('reservations.overview.attention')"
    :icon="Inbox"
    variant="home"
    content-ruled
    :empty="reservations.length === 0"
    :empty-text="$t('reservations.overview.attention_empty')"
    :href
    :href-label="$t('Visos')"
  >
    <ul class="divide-y divide-border border-y border-border" data-slot="reservations-needing-decision">
      <li v-for="reservation in reservations" :key="reservation.id" class="flex flex-col gap-2 px-1 py-3 sm:flex-row sm:items-center sm:gap-4">
        <span class="min-w-0 flex-1">
          <Link :href="route('reservations.show', reservation.id)" prefetch class="block truncate font-medium hover:text-brand">
            {{ reservation.name }}
          </Link>
          <span class="block truncate text-sm text-muted-foreground">
            {{ resourceNames(reservation) }} · {{ formatDate(new Date(reservation.start_time)) }} – {{ formatDate(new Date(reservation.end_time)) }}
          </span>
        </span>
        <ReservationRowActions :reservation @decide="(decision, target) => emit('decide', decision, target)" />
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import ReservationRowActions from './ReservationRowActions.vue';
import type { ReservationDecision } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { formatDate } from '@/Utils/dateTime';
import type { DashboardReservation } from '@/Utils/ReservationStatus';

defineProps<{
  reservations: DashboardReservation[];
  /** "Visos" → the filtered collection this list is the first few rows of. */
  href?: string;
}>();

const emit = defineEmits<{
  decide: [decision: ReservationDecision, reservation: DashboardReservation];
}>();

const resourceNames = (reservation: DashboardReservation) => reservation.resources.map(resource => resource.name).join(', ');
</script>
