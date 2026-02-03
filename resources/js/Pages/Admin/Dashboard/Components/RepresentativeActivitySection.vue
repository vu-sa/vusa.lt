<template>
  <section class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
        {{ $t('Atstovų aktyvumas') }}
      </h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Activity Stats Card -->
      <RepresentativeStatsCard
        :stats
        :loading
      />

      <!-- Users List Card -->
      <RepresentativeUsersCard
        :users
        :loading
        @show-all="handleShowAll"
      />
    </div>

    <!-- Representatives DataTable Modal -->
    <RepresentativeDataTable
      :users
      :is-open="showDataTable"
      :initial-tab="dataTableInitialTab"
      @update:is-open="showDataTable = $event"
    />
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { RepresentativeActivityStats, RepresentativeUser } from '../types';

import RepresentativeStatsCard from './RepresentativeStatsCard.vue';
import RepresentativeUsersCard from './RepresentativeUsersCard.vue';
import RepresentativeDataTable from './RepresentativeDataTable.vue';

interface Props {
  stats: RepresentativeActivityStats;
  users: RepresentativeUser[];
  loading?: boolean;
}

defineProps<Props>();

const showDataTable = ref(false);
const dataTableInitialTab = ref<'active' | 'inactive'>('inactive');

function handleShowAll(tab?: 'active' | 'inactive') {
  if (tab) {
    dataTableInitialTab.value = tab;
  }
  showDataTable.value = true;
}
</script>
