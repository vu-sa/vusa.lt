<template>
  <CollectionRowList :items="problems" :item-key="problem => problem.id" data-slot="problem-summary-list">
    <template #default="{ item: problem }">
      <ProblemRow
        :problem
        :title="problem.title"
        :href="route('problems.show', problem.id)"
        :actions="removable ? [{ key: 'unlink', label: $t('Atsieti problemą'), icon: Unlink }] : []"
        @select="key => key === 'unlink' && $emit('remove', problem)"
      />
    </template>
  </CollectionRowList>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Unlink } from '@lucide/vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';

import ProblemRow from './ProblemRow.vue';

/** `ProblemSummaryResource`: a problem as a line on another record. */
export interface ProblemSummary {
  id: string;
  title: string;
  status: string;
  occurred_at?: string | null;
  resolved_at?: string | null;
  tenant?: { id: number; shortname: string } | null;
  responsible_user?: { id: string; name: string } | null;
}

defineProps<{ problems: ProblemSummary[]; removable?: boolean }>();
defineEmits<{ remove: [problem: ProblemSummary] }>();
</script>
