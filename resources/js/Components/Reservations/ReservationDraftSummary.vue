<template>
  <OverviewSection
    :title="$t('reservations.cart.unfinished')"
    :icon="ShoppingBasket"
    :variant
    data-slot="reservation-draft-summary"
  >
    <CollectionRowList :items="[draft]" frame="attached">
      <template #default>
        <ReservationDraftRow :draft />
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ShoppingBasket } from '@lucide/vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';

import ReservationDraftRow from './ReservationDraftRow.vue';
import type { ReservationDraftSummaryData } from './types';

withDefaults(defineProps<{
  draft: ReservationDraftSummaryData;
  variant?: 'default' | 'home';
}>(), {
  variant: 'default',
});
</script>
