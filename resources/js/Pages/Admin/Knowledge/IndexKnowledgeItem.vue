<template>
  <CollectionPage
    collection="knowledgeItems"
    :eyebrow="$t('shell.workspaces.pradzia.title')"
    :title="$t('learning.pages.library')"
    :lead="$t('learning_library.library.lead')"
    :source
    :columns
    :item-key="stepKey"
    :row-actions="rowActions"
    :quick-filters="quickFilters"
    default-view="table"
    table-fixed
    actions-width="w-32"
    table-min-width="min-w-[56rem]"
    :search-placeholder="$t('learning_library.library.search_placeholder')"
    @quick-filter="toggleQuickFilter"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #actions>
      <Button variant="outline" voice="sentence" class="min-h-11" data-testid="manage-sections" @click="sectionsOpen = true">{{ $t('learning_library.section_manager.title') }}</Button>
      <Button v-if="canCreateStep" as-child variant="brand" data-testid="create-learning-step">
        <Link :href="route('knowledgeItems.create')">
          <Plus aria-hidden="true" />
          {{ $t('learning_library.library.new_step') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow
        :actions="view === 'preview' ? [] : recordActions"
        class="px-3 py-3 sm:px-4"
        @select="key => selectRowAction(key, item)"
      >
        <div class="min-w-0 flex-1 space-y-1.5">
          <div class="flex flex-wrap items-center gap-2">
            <Link :href="route('knowledgeItems.show', item.id)" class="font-medium text-foreground hover:text-brand">
              {{ item.title }}
            </Link>
            <StatusBadge v-if="!item.isActive" :status="bannerStatuses.inactive" voice="sentence" data-testid="step-inactive-badge" />
          </div>
          <div class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
            <span>· {{ audienceSummary(item) }}</span>
            <span v-if="item.section">· {{ item.section.title }}</span>
            <StatusBadge :status="learningStepImportances[highestImportance(item)]" voice="sentence" />
            <template v-if="item.canUpdate && item.flags.length">
              <StatusBadge
                v-for="flag in item.flags"
                :key="flag"
                :status="learningCuratorFlags[flag]"
                voice="sentence"
                :data-testid="`curator-flag-${flag}`"
              />
            </template>
          </div>
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <div v-if="column.key === 'title'" class="space-y-1">
        <div class="flex flex-wrap items-center gap-2">
          <Link :href="route('knowledgeItems.show', item.id)" class="font-medium text-foreground hover:text-brand">
            {{ item.title }}
          </Link>
          <StatusBadge v-if="!item.isActive" :status="bannerStatuses.inactive" voice="sentence" data-testid="step-inactive-badge" />
        </div>
        <div v-if="item.canUpdate && item.flags.length" class="flex flex-wrap items-center gap-1.5">
          <StatusBadge
            v-for="flag in item.flags"
            :key="flag"
            :status="learningCuratorFlags[flag]"
            voice="sentence"
            :data-testid="`curator-flag-${flag}`"
          />
        </div>
      </div>
      <span v-else-if="column.key === 'audience'" class="text-sm text-muted-foreground">{{ audienceSummary(item) || '—' }}</span>
      <span v-else-if="column.key === 'section'" class="text-sm text-muted-foreground">{{ item.section?.title || '—' }}</span>
      <StatusBadge v-else-if="column.key === 'importance'" :status="learningStepImportances[highestImportance(item)]" voice="sentence" />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="BookOpen"
        :title="$t('learning_library.library.empty_title')"
        :description="$t('learning_library.library.empty_description')"
        :action-label="canCreateStep ? $t('learning_library.library.new_step') : undefined"
        @action="router.visit(route('knowledgeItems.create'))"
      />
    </template>
  </CollectionPage>
  <LearningSectionsSheet
    v-model:open="sectionsOpen" :sections="sections" :scopes="sectionScopes" :locale="locale" :can-create="canCreateStep"
  />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BookOpen, Eye, Pencil, Plus } from '@lucide/vue';
import { computed, ref, toRef } from 'vue';

import { quickFilterLabel } from '@/Components/Collection/quickFilterLabel';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import {
  bannerStatuses,
  learningCuratorFlags,
  learningStepImportances,
  learningStepKinds,
} from '@/Constants/statusIcons';
import type { KnowledgeLibraryItem } from '@/Types/learning';
import { LearningImportance, LearningAudienceType } from '@/Types/enums';
import LearningSectionsSheet from '@/Features/Admin/Learning/LearningSectionsSheet.vue';
import type { LearningSectionOption, LearningSectionScopes } from '@/Features/Admin/Learning/types';

const props = withDefaults(defineProps<{
  items: KnowledgeLibraryItem[];
  sections?: LearningSectionOption[];
  audienceTypes?: LearningAudienceType[];
  sectionScopes?: LearningSectionScopes;
  canCreate?: boolean;
}>(), { sections: () => [], audienceTypes: () => Object.values(LearningAudienceType), sectionScopes: () => ({ create: { canTargetEverywhere: false, tenants: [] }, update: { canTargetEverywhere: false, tenants: [] } }) });
const page = usePage();
const locale = computed<'lt' | 'en'>(() => page.props.app.locale === 'en' ? 'en' : 'lt');
const sectionsOpen = ref(false);
function audienceSummary(step: KnowledgeLibraryItem) {
  return step.audiences.slice(0, 2).map(audience => audience.label).join(', ') + (step.audiences.length > 2 ? ` +${step.audiences.length - 2}` : '');
}
function highestImportance(step: KnowledgeLibraryItem): LearningImportance {
  return Object.values(LearningImportance).find(importance => step.audiences.some(audience => audience.importance === importance)) ?? LearningImportance.Optional;
}

const canCreateStep = computed(() => Boolean(props.canCreate));
const stepKey = (step: KnowledgeLibraryItem) => step.id;

const source = useLocalCollectionSource<KnowledgeLibraryItem>({
  items: toRef(props, 'items'),
  searchText: step => [step.title, ...step.audiences.map(audience => audience.label), step.section?.title],
  defaultSort: 'order:asc',
  sortOptions: () => [
    { value: 'order:asc', label: $t('learning_library.library.sort.order_asc'), by: step => step.order },
    { value: 'title:asc', label: $t('learning_library.library.sort.title_asc'), by: step => step.title },
    { value: 'title:desc', label: $t('learning_library.library.sort.title_desc'), by: step => step.title },
  ],
  facets: [
    { field: 'audience', label: $t('learning_library.library.filters.audience'), get: step => step.audienceTypes.filter(type => props.audienceTypes.includes(type)), valueLabel: value => $t(`learning.audience_types.${value}`) },
    { field: 'section', label: $t('learning_library.library.filters.section'), get: step => step.section?.id, valueLabel: value => props.sections.find(section => section.id === value)?.title[locale.value] || props.items.find(step => step.section?.id === value)?.section?.title || value },
    { field: 'importance', label: $t('learning_library.library.filters.importance'), get: step => highestImportance(step), valueLabel: value => $t(`learning.importance.${value}`) },
  ],
});

const quickFilters = computed<CollectionQuickFilter[]>(() =>
  source.facets.value.flatMap(facet => facet.values.map(option => ({
    id: `${facet.field}:${option.value}`,
    label: quickFilterLabel(option.label, option.count ?? 0),
    active: option.isSelected,
  }))),
);

function toggleQuickFilter(id: string): void {
  const [field, value] = id.split(':');
  if (field && value) {
    source.toggleFilter(field, value);
  }
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('learning_library.library.columns.title'), sortField: 'title' },
  { key: 'audience', label: $t('learning_library.library.columns.audience'), class: 'w-44' },
  { key: 'section', label: $t('learning_library.library.columns.section'), class: 'w-44' },
  { key: 'importance', label: $t('learning_library.library.columns.importance'), class: 'w-40' },
]);

const rowActions = (step: KnowledgeLibraryItem): CollectionRowAction[] => [
  { key: 'open', role: 'open' as const, label: $t('Atidaryti'), icon: Eye, href: route('knowledgeItems.show', step.id) },
  ...(step.canUpdate
    ? [{ key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil, href: route('knowledgeItems.edit', step.id) }]
    : []),
];

function selectRowAction(key: string, step: KnowledgeLibraryItem): void {
  if (key === 'open') {
    router.visit(route('knowledgeItems.show', step.id));
  }
  else if (key === 'edit' && step.canUpdate) {
    router.visit(route('knowledgeItems.edit', step.id));
  }
}
</script>
