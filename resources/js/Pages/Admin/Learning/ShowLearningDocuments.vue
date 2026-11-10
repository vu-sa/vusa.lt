<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { EmptyState, FormSegmentedControl } from '@/Components/Patterns';
import LearningPageFrame from '@/Features/Admin/Learning/LearningPageFrame.vue';
import LearningStepRow from '@/Features/Admin/Learning/LearningStepRow.vue';
import { LearningImportance } from '@/Types/enums';
import type { LearningDocumentsData, LearningStepData } from '@/Types/learning';

const props = defineProps<{
  documents: LearningDocumentsData;
  canSeeReps: boolean;
}>();
const page = usePage();
const viewFromUrl = () => new URLSearchParams(page.url.split('?')[1]?.split('#')[0]).get('view') === 'sections' ? 'sections' : 'importance';
const view = ref<string>(viewFromUrl());
watch(() => page.url, () => { view.value = viewFromUrl(); });
const viewOptions = computed(() => [
  { value: 'importance', label: $t('learning.documents.by_importance') },
  { value: 'sections', label: $t('learning.documents.by_sections') },
]);
const selectedView = computed({ get: () => view.value, set: changeView });
function changeView(value: string) {
  view.value = value;
  const url = new URL(page.url, window.location.origin);
  if (value === 'sections') url.searchParams.set('view', value);
  else url.searchParams.delete('view');
  router.replace({ url: `${url.pathname}${url.search}${url.hash}`, preserveState: true, preserveScroll: true });
}
const resolved = (step: LearningStepData) => step.state === 'done' || step.state === 'skipped';
const groups = computed(() => {
  if (view.value === 'sections') {
    return [
      ...[...props.documents.sections].sort((a, b) => a.order - b.order).map(section => ({
        key: section.id, title: section.title, description: section.description,
        steps: props.documents.documents.filter(step => step.section?.id === section.id),
      })),
      { key: 'other', title: $t('learning.documents.other'), description: '', steps: props.documents.documents.filter(step => !step.section) },
    ].filter(group => group.steps.length);
  }
  return Object.values(LearningImportance).map(importance => ({
    key: importance, title: $t(`learning.documents.${importance}`), description: '',
    steps: props.documents.documents.filter(step => step.importance === importance).sort((a, b) => Number(resolved(a)) - Number(resolved(b))),
  })).filter(group => group.steps.length);
});
</script>

<template>
  <LearningPageFrame :can-see-reps="canSeeReps">
    <OverviewPage :title="$t('learning.pages.documents')">
      <FormSegmentedControl
        v-model="selectedView"
        :options="viewOptions"
        :ariaLabel="$t('Rodyti')"
        test-id-prefix="documents-view"
        class="max-w-md"
      />
      <section v-for="group in groups" :key="group.key" data-slot="document-section" :aria-labelledby="`documents-${group.key}`">
        <h2 :id="`documents-${group.key}`" class="flex items-baseline gap-2 text-xl font-bold leading-tight text-foreground sm:text-2xl">
          {{ group.title }}
          <span class="text-sm font-normal tabular-nums text-muted-foreground">{{ group.steps.filter(resolved).length }}/{{ group.steps.length }}</span>
        </h2>
        <p v-if="group.description" class="mt-1 max-w-prose text-sm text-muted-foreground">{{ group.description }}</p>
        <ul class="mt-3 divide-y divide-border border border-border">
          <LearningStepRow v-for="step in group.steps" :key="step.id" :step="step" show-glossary />
        </ul>
      </section>
      <EmptyState v-if="!groups.length" :title="$t('learning.documents.empty_title')" :description="$t('learning.documents.empty_description')" />
    </OverviewPage>
  </LearningPageFrame>
</template>
