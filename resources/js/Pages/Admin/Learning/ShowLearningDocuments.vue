<script setup lang="ts">
import { computed } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import EmptyState from '@/Components/Patterns/EmptyState.vue';
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
import LearningStepRow from '@/Features/Admin/Learning/LearningStepRow.vue';
import { LearningLayer } from '@/Types/enums';
import type { LearningStepData } from '@/Types/learning';

const props = defineProps<{ documents: LearningStepData[]; canSeeReps: boolean }>();
const sections = computed(() => {
  const order = [LearningLayer.Foundation, LearningLayer.InstitutionType, LearningLayer.DutyType, LearningLayer.Tenant, LearningLayer.Platform];
  const groups = new Map<string, { key: string; label: string; steps: LearningStepData[] }>();
  for (const layer of order) {
    for (const step of props.documents.filter(item => item.layer === layer)) {
      const key = `${layer}:${step.layerLabel}`;
      const group: { key: string; label: string; steps: LearningStepData[] } = groups.get(key) ?? { key, label: step.layerLabel, steps: [] };
      group.steps.push(step);
      groups.set(key, group);
    }
  }
  return [...groups.values()];
});
</script>

<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <OverviewPage :title="$t('learning.pages.documents')">
      <section v-for="section in sections" :key="section.key" class="space-y-2" data-slot="document-section">
        <h2 class="border-b border-border pb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ section.label }}</h2>
        <LearningStepRow v-for="step in section.steps" :key="step.id" :step="step" show-glossary />
      </section>
      <EmptyState v-if="!sections.length" :title="$t('learning.documents.empty_title')" :description="$t('learning.documents.empty_description')" />
    </OverviewPage>
  </LearningPageFrame>
</template>
