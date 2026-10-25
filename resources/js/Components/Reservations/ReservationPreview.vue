<template>
  <PreviewPane
    entity-type="reservation"
    :eyebrow="$t('Rezervacija')"
    :title="reservation.name"
    :href="showHref"
  >
    <template #status>
      <ReservationStateSummary :states="getReservationStates(reservation, scope)" :unresolved="isReservationUnresolved(reservation, scope)" />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti rezervaciją') }}
          </Link>
        </Button>
      </slot>
    </template>

    <p v-if="reservation.description" class="text-sm text-muted-foreground">
      {{ reservation.description }}
    </p>

    <PreviewFacts>
      <PreviewFact :label="$t('Laikas')" :value="period" tabular />
    </PreviewFacts>

    <PreviewSection :title="$t('Ištekliai')" :count="reservation.resources.length">
      <ReservationResourceChips :resources="reservation.resources" :muted-foreign="scope.approvableOnly" :max="12" />
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import ReservationResourceChips from './ReservationResourceChips.vue';

import { PreviewFact, PreviewFacts, PreviewPane, PreviewSection } from '@/Components/Patterns';
import ReservationStateSummary from '@/Components/Tag/ReservationStateSummary.vue';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/Utils/dateTime';
import { getReservationStates, isReservationUnresolved, type DashboardReservation } from '@/Utils/ReservationStatus';

const props = defineProps<{
  reservation: DashboardReservation;
}>();

const showHref = computed(() => route('reservations.show', props.reservation.id));

// A resource administrator decides only on their own resources, so the state reads from those.
const scope = computed(() => ({ approvableOnly: props.reservation.resources.some(resource => resource.pivot.approvable) }));

const period = computed(() => `${formatDate(props.reservation.start_time)} – ${formatDate(props.reservation.end_time)}`);
</script>
