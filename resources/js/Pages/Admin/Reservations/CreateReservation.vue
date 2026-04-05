<template>
  <PageContent
    :title="$tChoice('forms.new_model', 0, {
      model: $tChoice('entities.reservation.model', 1),
    })"
    :heading-icon="Icons.RESERVATION">
    <UpsertModelLayout>
      <ReservationForm remember-key="CreateReservation" model-route="reservations.store" :reservation :all-resources="resources" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { capitalize } from 'vue';

import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import ReservationForm from '@/Components/AdminForms/ReservationForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

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

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs([
  {
    label: capitalize($tChoice('entities.reservation.model', 2)),
    icon: Icons.RESERVATION,
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
