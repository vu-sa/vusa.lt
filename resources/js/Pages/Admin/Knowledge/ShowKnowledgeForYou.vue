<template>
  <OverviewPage
    :title="$t('learning.wiki.mine_title')"
    :eyebrow="$t('learning.pages.knowledge')"
  >
    <template #actions>
      <WikiAssignedProgress :progress="assignedProgress" class="sm:w-80" />
    </template>
    <FormSegmentedControl v-model="mineView" :options="mineOptions" :label="$t('Rodyti')" test-id-prefix="documents-view" class="max-w-md" />
    <section v-for="group in mineGroups" :id="`section-${group.key}`" :key="group.key" class="scroll-mt-24" data-slot="document-section" :aria-labelledby="`documents-${group.key}`">
      <h2 :id="`documents-${group.key}`" class="flex items-baseline gap-2 text-xl font-bold leading-tight text-foreground sm:text-2xl">
        {{ group.title }}
        <span class="text-sm font-normal tabular-nums text-muted-foreground">{{ group.items.filter(resolved).length }}/{{ group.items.length }}</span>
      </h2>
      <p v-if="group.key === 'self'" class="mt-1 text-sm text-muted-foreground">
        {{ $t('learning.wiki.self_learned_hint') }}
      </p>
      <ul class="mt-3 divide-y divide-border border border-border">
        <LearningStepRow v-for="item in group.items" :key="item.id" :step="item" />
      </ul>
    </section>
    <EmptyState v-if="!mineGroups.length" :title="$t('learning.documents.empty_title')" :description="$t('learning.documents.empty_description')" />
  </OverviewPage>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import WikiAssignedProgress from '@/Features/Admin/Knowledge/WikiAssignedProgress.vue';
import { EmptyState, FormSegmentedControl } from '@/Components/Patterns';
import LearningStepRow from '@/Features/Admin/Learning/LearningStepRow.vue';
import { LearningImportance } from '@/Types/enums';
import type { KnowledgeItemData, KnowledgePriorityProgress, LearningDocumentsData, SelfLearnedItemData } from '@/Types/learning';

const props = defineProps<{ knowledge: LearningDocumentsData; assignedProgress: KnowledgePriorityProgress[] }>();
const selfLearned = computed(() => props.knowledge.selfLearned ?? []);
const page = usePage();
const selectedView = () => new URLSearchParams(page.url.split('?')[1]?.split('#')[0]).get('view') === 'sections' ? 'sections' : 'importance';
const view = ref(selectedView());
watch(() => page.url, () => { view.value = selectedView(); });
function setView(value: string): void {
  view.value = value === 'sections' ? 'sections' : 'importance';
  router.replace({ url: route('knowledgeBase.mine', { view: view.value }), preserveState: true, preserveScroll: true });
}

const resolved = (item: KnowledgeItemData | SelfLearnedItemData) => item.state === 'done' || item.state === 'skipped';

const mineView = computed<string>({ get: () => view.value, set: setView });
const mineOptions = computed(() => [
  { value: 'importance', label: $t('learning.documents.by_importance') },
  { value: 'sections', label: $t('learning.documents.by_sections') },
]);
const mineGroups = computed(() => {
  if (mineView.value === 'sections') {
    return [
      ...props.knowledge.sections.map(section => ({
        key: section.id, title: section.title, items: props.knowledge.items.filter(item => item.section?.id === section.id),
      })),
      { key: 'other', title: $t('learning.documents.other'), items: props.knowledge.items.filter(item => !item.section) },
    ].filter(group => group.items.length);
  }
  return [
    ...Object.values(LearningImportance).map(importance => ({
      key: importance, title: $t(`learning.documents.${importance}`),
      items: props.knowledge.items.filter(item => item.importance === importance).sort((a, b) => Number(resolved(a)) - Number(resolved(b))),
    })),
    { key: 'self', title: $t('learning.wiki.self_learned'), items: selfLearned.value },
  ].filter(group => group.items.length);
});
</script>
