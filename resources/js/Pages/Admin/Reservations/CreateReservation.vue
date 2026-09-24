<template>
  <ReservationForm :cart="reservationCart" :default-period="defaultDateTimeRange" />
</template>

<script setup lang="ts">
import { transChoice as $tChoice } from 'laravel-vue-i18n';
import { capitalize } from 'vue';

import ReservationForm from '@/Components/AdminForms/ReservationForm.vue';
import type { ReservationCart } from '@/Components/Reservations/types';

defineProps<{
  reservationCart: ReservationCart | null;
  defaultDateTimeRange: { start: number; end: number };
}>();

</script>
