<template>
  <ReservationForm :cart="reservationCart" :default-period="defaultDateTimeRange" />
</template>

<script setup lang="ts">
import { transChoice as $tChoice } from 'laravel-vue-i18n';
import { capitalize } from 'vue';

import ReservationForm from '@/Components/AdminForms/ReservationForm.vue';
import { ReservationIcon } from '@/Components/icons';
import type { ReservationCart } from '@/Components/Reservations/types';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

defineProps<{
  reservationCart: ReservationCart | null;
  defaultDateTimeRange: { start: number; end: number };
}>();

usePageBreadcrumbs([
  {
    label: capitalize($tChoice('entities.reservation.model', 2)),
    icon: ReservationIcon,
  },
]);
</script>
