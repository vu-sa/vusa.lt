<template>
  <CollectionPage
    collection="learningSteps"
    :eyebrow="$t('shell.workspaces.pradzia.title')"
    :title="$t('learning.pages.library')"
    :lead="$t('learning_library.library.lead')"
    :source
    :columns
    :item-key="stepKey"
    :row-actions="rowActions"
    default-view="table"
    table-fixed
    actions-width="w-32"
    table-min-width="min-w-[56rem]"
    :search-placeholder="$t('learning_library.library.search_placeholder')"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #header>
      <LearningTabs :can-see-reps="canSeeReps" />
    </template>

    <template #actions>
      <Button v-if="canCreateStep" as-child variant="brand" data-testid="create-learning-step">
        <Link :href="route('learningSteps.create')">
          <Plus aria-hidden="true" />
          {{ $t('learning_library.library.new_step') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow
        :spotlight="item === source.items.value[0]"
        :actions="view === 'preview' ? [] : recordActions"
        class="px-3 py-3 sm:px-4"
        @select="key => selectRowAction(key, item)"
      >
        <div class="min-w-0 flex-1 space-y-1.5">
          <div class="flex flex-wrap items-center gap-2">
            <Link :href="route('learningSteps.show', item.id)" class="font-medium text-foreground hover:text-brand">
              {{ item.title }}
            </Link>
            <StatusBadge v-if="!item.isActive" :status="bannerStatuses.inactive" voice="sentence" data-testid="step-inactive-badge" />
          </div>
          <div class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
            <StatusBadge :status="learningStepKinds[item.kind]" voice="sentence" />
            <span>· {{ item.layerLabel }}</span>
            <StatusBadge v-if="item.stage" :status="learningStepStages[item.stage]" voice="sentence" />
            <StatusBadge :status="learningStepImportances[item.importance]" voice="sentence" />
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
          <Link :href="route('learningSteps.show', item.id)" class="font-medium text-foreground hover:text-brand">
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
      <StatusBadge v-else-if="column.key === 'kind'" :status="learningStepKinds[item.kind]" voice="sentence" />
      <span v-else-if="column.key === 'layer'" class="text-sm text-muted-foreground">{{ item.layerLabel || '—' }}</span>
      <template v-else-if="column.key === 'stage'">
        <StatusBadge v-if="item.stage" :status="learningStepStages[item.stage]" voice="sentence" />
        <span v-else class="text-muted-foreground">—</span>
      </template>
      <StatusBadge v-else-if="column.key === 'importance'" :status="learningStepImportances[item.importance]" voice="sentence" />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="BookOpen"
        :title="$t('learning_library.library.empty_title')"
        :description="$t('learning_library.library.empty_description')"
        :action-label="canCreateStep ? $t('learning_library.library.new_step') : undefined"
        @action="router.visit(route('learningSteps.create'))"
      />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { BookOpen, Eye, Pencil, Plus } from '@lucide/vue';
import { computed, toRef } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
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
  learningStepStages,
} from '@/Constants/statusIcons';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import { LearningImportance, LearningLayer, LearningStepKind } from '@/Types/enums';
import type { LearningLibraryStep } from '@/Types/learning';

const props = defineProps<{
  steps: LearningLibraryStep[];
  canCreate?: boolean;
  canSeeReps: boolean;
}>();

const page = usePage();
const canCreateStep = computed(() => props.canCreate ?? Boolean(page.props.auth?.can?.create?.learningStep));
const stepKey = (step: LearningLibraryStep) => step.id;

const source = useLocalCollectionSource<LearningLibraryStep>({
  items: toRef(props, 'steps'),
  searchText: step => [step.title, step.layerLabel],
  defaultSort: 'order:asc',
  sortOptions: () => [
    { value: 'order:asc', label: $t('learning_library.library.sort.order_asc'), by: (_step) => 0 },
    { value: 'title:asc', label: $t('learning_library.library.sort.title_asc'), by: step => step.title },
    { value: 'title:desc', label: $t('learning_library.library.sort.title_desc'), by: step => step.title },
  ],
  quickFilters: () => [
    {
      key: 'layer',
      label: $t('learning_library.library.filters.layer'),
      defaultValue: 'all',
      options: [
        { value: 'all', label: $t('learning_library.library.filters.all') },
        { value: LearningLayer.Platform, label: $t('learning.layers.platform') },
        { value: LearningLayer.Foundation, label: $t('learning.layers.foundation') },
        { value: LearningLayer.InstitutionType, label: $t('learning.layers.institution_type') },
        { value: LearningLayer.DutyType, label: $t('learning.layers.duty_type') },
        { value: LearningLayer.Tenant, label: $t('learning.layers.tenant') },
      ],
      predicate: (step, value) => step.layer === value,
    },
    {
      key: 'kind',
      label: $t('learning_library.library.filters.kind'),
      defaultValue: 'all',
      options: [
        { value: 'all', label: $t('learning_library.library.filters.all') },
        { value: LearningStepKind.Knowledge, label: $t('learning.kinds.knowledge') },
        { value: LearningStepKind.Action, label: $t('learning.kinds.action') },
        { value: LearningStepKind.MeetingPrep, label: $t('learning.kinds.meeting_prep') },
      ],
      predicate: (step, value) => step.kind === value,
    },
    {
      key: 'importance',
      label: $t('learning_library.library.filters.importance'),
      defaultValue: 'all',
      options: [
        { value: 'all', label: $t('learning_library.library.filters.all') },
        { value: LearningImportance.Essential, label: $t('learning.importance.essential') },
        { value: LearningImportance.Recommended, label: $t('learning.importance.recommended') },
        { value: LearningImportance.Optional, label: $t('learning.importance.optional') },
      ],
      predicate: (step, value) => step.importance === value,
    },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('learning_library.library.columns.title'), sortField: 'title' },
  { key: 'kind', label: $t('learning_library.library.columns.kind'), class: 'w-44' },
  { key: 'layer', label: $t('learning_library.library.columns.layer'), class: 'w-44' },
  { key: 'stage', label: $t('learning_library.library.columns.stage'), class: 'w-44' },
  { key: 'importance', label: $t('learning_library.library.columns.importance'), class: 'w-40' },
]);

const rowActions = (step: LearningLibraryStep): CollectionRowAction[] => [
  { key: 'open', role: 'open' as const, label: $t('Atidaryti'), icon: Eye, href: route('learningSteps.show', step.id) },
  ...(step.canUpdate
    ? [{ key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil, href: route('learningSteps.edit', step.id) }]
    : []),
];

function selectRowAction(key: string, step: LearningLibraryStep): void {
  if (key === 'open') {
    router.visit(route('learningSteps.show', step.id));
  }
  else if (key === 'edit' && step.canUpdate) {
    router.visit(route('learningSteps.edit', step.id));
  }
}
</script>
