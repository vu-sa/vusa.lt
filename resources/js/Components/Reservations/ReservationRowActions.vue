<template>
  <CollectionRowActions :actions="reservationRowActions(reservation, disabled)" data-slot="reservation-row-actions" @select="key => emit('decide', key as ReservationDecision, reservation)" />
</template>

<script setup lang="ts">
import { reservationRowActions } from './reservationRowActions';
import type { ReservationDecision } from './types';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { DashboardReservation } from '@/Utils/ReservationStatus';

defineProps<{ reservation: DashboardReservation; disabled?: boolean }>();
const emit = defineEmits<{ decide: [decision: ReservationDecision, reservation: DashboardReservation] }>();
</script>
