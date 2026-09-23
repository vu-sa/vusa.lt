<template>
  <CollectionPage
    :source
    collection="study-sets"
    entity-type="studySet"
    :eyebrow="`${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.studiju_rinkiniai')}`"
    :title="$t('Studijų komplektai')"
    :lead="isTrash
      ? $t('Ištrinti komplektai. Atkurk tai, ko dar reikia.')
      : $t('Individualių studijų dalykų komplektai, rodomi viešoje svetainėje eilės tvarka.')"
    default-view="table"
    :item-key="set => String(set.id)"
    :columns
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti komplektų')"
  >
    <template #actions>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('studySets.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas komplektas') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <CollectionPrimaryCell :title="nameOf(item)" :href="isTrash ? undefined : route('studySets.edit', item.id)" :sub="item.tenant?.shortname" />
            <StatusBadge v-if="!item.is_visible" :status="contentStatuses.draft" class="shrink-0" />
          </div>
          <p class="mt-2 text-xs tabular-nums text-muted-foreground">
            {{ $tChoice(':count dalykas|:count dalykai|:count dalykų', item.courses_count ?? 0, { count: item.courses_count ?? 0 }) }} · #{{ item.order }}
          </p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="nameOf(item)" :href="isTrash ? undefined : route('studySets.edit', item.id)" />
      <span v-else-if="column.key === 'tenant'" class="text-muted-foreground">{{ item.tenant?.shortname ?? '—' }}</span>
      <span v-else-if="column.key === 'courses'" class="tabular-nums text-muted-foreground">{{ item.courses_count ?? 0 }}</span>
      <span v-else-if="column.key === 'order'" class="tabular-nums text-muted-foreground">{{ item.order }}</span>
      <StatusBadge v-else-if="column.key === 'status' && !item.is_visible" :status="contentStatuses.draft" />
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actionsFor(item)" @select="key => actions.select(key)" />
    </template>
  </CollectionPage>

  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed, toRef } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { contentStatuses } from '@/Constants/statuses';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';

type StudySetRow = App.Entities.StudySet & { courses_count?: number; tenant?: { id: number; shortname: string } | null };

const props = defineProps<{
  studySets: StudySetRow[];
  deletedCount: number;
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.studySet));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.studySet));

const nameOf = (set: StudySetRow) => getTranslatedValue(set.name) || $t('Be pavadinimo');

const source = useLocalCollectionSource<StudySetRow>({
  items: toRef(props, 'studySets'),
  searchText: set => [getTranslatedValue(set.name, 'lt'), getTranslatedValue(set.name, 'en')],
  defaultSort: 'order:asc',
  sortOptions: [
    { value: 'order:asc', label: $t('Eilės tvarka'), by: set => set.order },
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)'), by: nameOf },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)'), by: nameOf },
  ],
  facets: [{ field: 'tenant', label: $t('Padalinys'), get: set => set.tenant?.shortname }],
});

const actions = useCollectionRecordActions({
  routePrefix: 'studySets',
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (set: StudySetRow) => actions.rowActions(set, nameOf(set), isTrash);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Komplektas'), sortField: 'name' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'courses', label: $t('Dalykai'), class: 'w-28' },
  { key: 'order', label: $t('Eilė'), class: 'w-20' },
  { key: 'status', label: $t('Būsena'), class: 'w-36' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
