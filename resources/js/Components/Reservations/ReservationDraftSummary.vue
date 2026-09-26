<template>
  <OverviewSection
    :title="$t('reservations.cart.unfinished')"
    :icon="ShoppingBasket"
    :variant
    content-ruled
    data-slot="reservation-draft-summary"
  >
    <div class="border-y border-border">
      <ReservationDraftRow :draft />
    </div>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ShoppingBasket } from 'lucide-vue-next';

import ReservationDraftRow, { type ReservationDraftSummaryData } from './ReservationDraftRow.vue';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

withDefaults(defineProps<{
  draft: ReservationDraftSummaryData;
  variant?: 'default' | 'home';
}>(), {
  variant: 'default',
});
</script>
