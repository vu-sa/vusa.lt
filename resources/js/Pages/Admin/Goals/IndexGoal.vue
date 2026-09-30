<template>
  <CollectionPage
    :source
    collection="goals"
    entity-type="goal"
    :eyebrow="`${$t('shell.workspaces.atstovavimas.title')} · ${$t('shell.sections.tikslai')}`"
    :title="$t('shell.sections.tikslai')"
    :lead="$t('goals.lead')"
    default-view="rows"
    :available-views="['rows', 'table']"
    :item-key="goal => goal.id"
    :columns
    :search-placeholder="$t('goals.search')"
  >
    <template #actions>
      <Badge variant="outline" class="text-xs" :title="$t('goals.experimental_note')">
        {{ $t('goals.experimental') }}
      </Badge>
      <Button v-if="canCreate" as-child variant="brand" size="lg">
        <Link :href="route('goals.create')">
          <Plus aria-hidden="true" />
          {{ $t('goals.new') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-center gap-4 px-3 py-3 sm:px-4">
        <div class="min-w-0 flex-1">
          <Link :href="route('goals.show', item.id)" data-collection-open class="block truncate font-medium hover:text-brand">
            {{ title(item) }}
          </Link>
          <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
            <span class="font-medium">{{ item.tenant.shortname }}</span>
            <template v-if="item.cadence">
              <span aria-hidden="true">·</span>
              <span>{{ item.cadence.label }}</span>
            </template>
            <span aria-hidden="true">·</span>
            <span>{{ $tChoice('entities.step.model', item.steps_count) }}: {{ item.steps_count }}</span>
            <template v-if="item.is_public">
              <span aria-hidden="true">·</span>
              <span>{{ $t('goals.filters.public') }}</span>
            </template>
          </div>
        </div>
        <StatusBadge :status="goalStatuses[item.status]" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <Link v-if="column.key === 'title'" :href="route('goals.show', item.id)" data-collection-open class="block truncate font-medium hover:text-brand">
        {{ title(item) }}
      </Link>
      <StatusBadge v-else-if="column.key === 'status'" :status="goalStatuses[item.status]" />
      <span v-else-if="column.key === 'tenant'" class="text-xs">{{ item.tenant.shortname }}</span>
      <span v-else-if="column.key === 'cadence'" class="text-xs tabular-nums">{{ item.cadence?.label ?? '—' }}</span>
      <span v-else-if="column.key === 'steps_count'" class="tabular-nums">{{ item.steps_count }}</span>
      <span v-else-if="column.key === 'problems_count'" class="tabular-nums">{{ item.problems_count }}</span>
    </template>

    <template #empty>
      <EmptyState
        :mode="source.query.value.trim() || source.activeFilterCount.value ? 'no-results' : 'empty'"
        :icon="GoalIcon"
        :title="$t('goals.empty_title')"
        :description="$t('goals.empty_description')"
        :action-label="canCreate ? $t('goals.new') : undefined"
        @action="router.visit(route('goals.create'))"
      />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed, toRef } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import { GoalIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { goalStatuses } from '@/Constants/statuses';
import { translatedText, type Translated } from '@/Features/Admin/Goals/types';
import { GoalStatus } from '@/Types/enums';

interface GoalRow {
  id: string;
  title: Translated;
  status: GoalStatus;
  is_public: boolean;
  tenant: { id: number; shortname: string };
  cadence: { id: string; label: string } | null;
  responsible_duty: string | null;
  steps_count: number;
  problems_count: number;
  updated_at: string | null;
  can_update: boolean;
}

const props = defineProps<{
  goals: GoalRow[];
  canCreate: boolean;
}>();

const title = (goal: GoalRow) => translatedText(goal.title, getActiveLanguage()) || '—';

const source = useLocalCollectionSource<GoalRow>({
  items: toRef(props, 'goals'),
  searchText: goal => [goal.title.lt, goal.title.en, goal.tenant.shortname, goal.responsible_duty],
  defaultSort: 'updated_at:desc',
  sortOptions: () => [
    { value: 'updated_at:desc', label: $t('Naujausios atnaujintos'), by: goal => goal.updated_at ?? '' },
    { value: 'title:asc', label: $t('Pagal pavadinimą (A–Z)'), by: goal => title(goal) },
  ],
  facets: [
    { field: 'status', label: $t('goals.filters.status'), get: goal => goal.status, valueLabel: value => $t(goalStatuses[value as GoalStatus]?.label ?? value) },
    { field: 'tenant', label: $tChoice('entities.tenant.model', 1), get: goal => goal.tenant.shortname },
    { field: 'cadence', label: $t('goals.filters.cadence'), get: goal => goal.cadence?.label },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'title', label: $t('entities.goal.title') },
  { key: 'status', label: $t('goals.filters.status'), class: 'w-36' },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), class: 'w-28' },
  { key: 'cadence', label: $t('goals.filters.cadence'), class: 'w-28' },
  { key: 'steps_count', label: $tChoice('entities.step.model', 2), class: 'w-24' },
  { key: 'problems_count', label: $tChoice('entities.problem.model', 2), class: 'w-24' },
]);
</script>
