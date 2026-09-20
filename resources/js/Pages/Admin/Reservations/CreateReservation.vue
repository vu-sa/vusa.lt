<template>
  <ReservationForm
    remember-key="CreateReservation"
    model-route="reservations.store"
    :reservation
    :all-resources="resources"
  />
</template>

<script setup lang="ts">
import { transChoice as $tChoice } from 'laravel-vue-i18n';
import { capitalize } from 'vue';

import ReservationForm from '@/Components/AdminForms/ReservationForm.vue';
import { ReservationIcon } from '@/Components/icons';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

export type ReservationCreationTemplate = Omit<
  App.Entities.Reservation,
  | 'created_at'
  | 'updated_at'
  | 'deleted_at'
  | 'id'
  | 'completed_at'
  | 'start_time'
  | 'end_time'
> & {
  id: undefined;
  start_time: number;
  end_time: number;
};

const props = defineProps<{
  resources: Array<App.Entities.Resource>;
  dateTimeRange: { start: number; end: number };
}>();

usePageBreadcrumbs([
  {
    label: capitalize($tChoice('entities.reservation.model', 2)),
    icon: ReservationIcon,
  },
]);

const reservation: ReservationCreationTemplate = {
  id: undefined,
  name: '',
  description: '',
  start_time: props.dateTimeRange.start,
  end_time: props.dateTimeRange.end,
  resources: [],
};
</script>
