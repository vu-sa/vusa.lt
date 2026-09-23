<template>
  <CollectionPage
    :source
    collection="study-programs"
    entity-type="studyProgram"
    :eyebrow="`${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.studiju_programos')}`"
    :title="$t('Studijų programos')"
    :lead="isTrash
      ? $t('Ištrintos studijų programos. Atkurk tai, ko dar reikia.')
      : $t('Studijų programos, kurias gali nurodyti nariai ir studentų atstovai.')"
    default-view="table"
    :item-key="program => String(program.id)"
    :columns
    :selectable="mergeMode"
    :trash="{ count: deletedCount, active: isTrash }"
    :search-placeholder="$t('Ieškoti studijų programų')"
  >
    <template #actions>
      <Button v-if="canMerge && !isTrash" variant="outline" size="lg" :aria-pressed="mergeMode" @click="mergeMode = !mergeMode">
        <Merge aria-hidden="true" />
        {{ mergeMode ? $t('Atšaukti sujungimą') : $t('Sujungti programas') }}
      </Button>
      <Button v-if="canCreate && !isTrash" as-child variant="brand" size="lg">
        <Link :href="route('studyPrograms.create')">
          <Plus aria-hidden="true" />
          {{ $t('Nauja studijų programa') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="item.name" :href="isTrash ? undefined : route('studyPrograms.edit', item.id)" :sub="item.tenant?.shortname" />
          <p class="mt-2 text-xs text-muted-foreground">{{ degreeLabel(item.degree) }}</p>
        </div>
        <CollectionRowActions :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="item.name" :href="isTrash ? undefined : route('studyPrograms.edit', item.id)" />
      <span v-else-if="column.key === 'degree'" class="text-muted-foreground">{{ degreeLabel(item.degree) }}</span>
      <span v-else-if="column.key === 'tenant'" class="text-muted-foreground">{{ item.tenant?.shortname ?? '—' }}</span>
      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="actionsFor(item)" @select="key => actions.select(key, item.force_delete_blocked_reason)" />
    </template>

    <template #bulk-actions="{ selected }">
      <Button variant="brand" size="sm" :disabled="selected.length < 2" @click="mergeRecords = toMergeRecords(selected)">
        <Merge aria-hidden="true" />
        {{ $t('Sujungti') }}
      </Button>
    </template>
  </CollectionPage>

  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="study-programs"
    :records="mergeRecords"
    :submit-url="route('studyPrograms.mergeStudyPrograms')"
    target-field="target_study_program_id"
    source-field="source_study_program_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
  <CollectionConfirmAction :dialog="actions.dialog.value" @confirm="actions.confirm" @cancel="actions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Merge, Plus } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';

import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { Button } from '@/Components/ui/button';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import { isTrashView, useLocalCollectionSource } from '@/Composables/useCollectionSource';

type StudyProgramRow = App.Entities.StudyProgram & {
  tenant?: { id: number; shortname: string } | null;
  force_delete_blocked_reason?: string | null;
};

const props = defineProps<{
  studyPrograms: StudyProgramRow[];
  deletedCount: number;
  degreeOptions: { label: string; value: string }[];
}>();

const page = usePage();
const isTrash = isTrashView();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.studyProgram));
const canForceDelete = computed(() => Boolean(page.props.auth?.can?.forceDelete?.studyProgram));
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('studyPrograms.index', 'merge'));

// `?merge=1` is the palette's "Sujungti studijų programas" entry point.
const mergeMode = ref(!isTrash && new URLSearchParams(window.location.search).get('merge') === '1');
const mergeRecords = ref<MergeRecord[]>([]);

const degreeLabel = (value: string) => props.degreeOptions.find(option => option.value === value)?.label ?? value;

const source = useLocalCollectionSource<StudyProgramRow>({
  items: toRef(props, 'studyPrograms'),
  searchText: program => [program.name, program.degree, program.tenant?.shortname],
  defaultSort: 'name:asc',
  sortOptions: [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)'), by: program => program.name },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)'), by: program => program.name },
  ],
  facets: [
    { field: 'degree', label: $t('Laipsnis'), get: program => program.degree, valueLabel: degreeLabel },
    { field: 'tenant', label: $t('Padalinys'), get: program => program.tenant?.shortname },
  ],
});

const actions = useCollectionRecordActions({
  routePrefix: 'studyPrograms',
  canDelete: () => canCreate.value,
  canRestore: () => canCreate.value,
  canForceDelete: () => canForceDelete.value,
});
const actionsFor = (program: StudyProgramRow) => actions.rowActions(program, program.name, isTrash);

const toMergeRecords = (programs: StudyProgramRow[]): MergeRecord[] =>
  programs.map(program => ({ id: program.id, label: program.name, context: program.tenant?.shortname }));

function merged(): void {
  mergeRecords.value = [];
  mergeMode.value = false;
  router.reload({ only: ['studyPrograms'] });
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Studijų programa'), sortField: 'name' },
  { key: 'degree', label: $t('Laipsnis'), class: 'w-40' },
  { key: 'tenant', label: $t('Padalinys'), class: 'w-32' },
  { key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true },
]);
</script>
