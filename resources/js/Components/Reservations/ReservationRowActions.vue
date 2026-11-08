<template>
  <CollectionRowActions :actions="reservationRowActions(reservation, disabled)" data-slot="reservation-row-actions" @select="key => emit('decide', key as ReservationDecision, reservation)" />
</template>

<script setup lang="ts">
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { DashboardReservation } from '@/Utils/ReservationStatus';

import { reservationRowActions } from './reservationRowActions';
import type { ReservationDecision } from './types';

defineProps<{ reservation: DashboardReservation; disabled?: boolean }>();
const emit = defineEmits<{ decide: [decision: ReservationDecision, reservation: DashboardReservation] }>();
</script>
