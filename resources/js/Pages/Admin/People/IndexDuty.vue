<template>
  <CollectionPage
    :row-actions="item => rowActions(item)"
    :source
    collection="duties"
    :eyebrow="$t('shell.workspaces.organizacija.title')"
    :title="$t('Pareigybės')"
    :lead="isDeleted ? $t('Peržiūrėk ištrintas pareigybes arba jas atkurk.') : $t('Tvarkyk pareigybes ir greitai pastebėk neužimtas vietas.')"
    default-view="rows"
    :item-key="dutyKey"
    :trash="{ count: deletedCount ?? 0, active: isDeleted }"
    :columns
    @row-action="(item, key) => selectRowAction(key, item)"
    :selectable="mergeMode"
    :quick-filters
    :search-placeholder="$t('Ieškoti pareigybių')"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="canMerge && !isDeleted" variant="outline" size="lg" :aria-pressed="mergeMode" @click="mergeMode = !mergeMode">
        <Merge aria-hidden="true" />{{ mergeMode ? $t('Atšaukti sujungimą') : $t('Sujungti pareigybes') }}
      </Button>
      <Button v-if="canCreate && !isDeleted" as-child variant="brand" size="lg">
        <Link :href="route('duties.create')">
          <Plus aria-hidden="true" />{{ $t('Nauja pareigybė') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item, actions: recordActions }">
      <article class="flex min-h-16 flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center" data-slot="duty-collection-row">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell :title="title(item)" :href="isDeleted ? undefined : route('duties.show', item.id)">
            <InflectedDutyName :name="title(item)" />
          </CollectionPrimaryCell>
          <p v-if="institutionTitle(item)" class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            {{ institutionTitle(item) }}
            <InactiveInstitutionBadge :active="item.institution?.is_active" />
          </p>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span v-if="item.email" class="truncate">{{ item.email }}</span>
            <span v-if="item.types?.length">{{ item.types.map(type => titleOf(type.title)).join(', ') }}</span>
          </div>
        </div>
        <div class="flex w-full flex-wrap items-center gap-3 sm:w-auto sm:justify-end">
          <CollectionActivityStatus
            entity="duties"
            :record-id="item.id"
            :active="item.is_active ?? true"
            :editable="!isDeleted && Boolean(item.can_update)"
            :spotlight="String(item.id) === String(source.items.value[0]?.id)"
            @updated="active => statusUpdated(item, active)"
          />
          <span v-if="item.is_active !== false && item.dutiables_count === 0" class="inline-flex shrink-0 items-center gap-1 text-xs text-status-attention">
            <CircleAlert class="size-3" aria-hidden="true" />{{ $t('Neužimta') }}
          </span>
          <CollectionRowActions :spotlight="item === source?.items.value[0]" :actions="recordActions" @select="key => selectRowAction(key, item)" />
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="title(item)" :href="isDeleted ? undefined : route('duties.show', item.id)">
        <InflectedDutyName :name="title(item)" />
      </CollectionPrimaryCell>
      <template v-else-if="column.key === 'status'">
        <CollectionActivityStatus
          entity="duties"
          :record-id="item.id"
          :active="item.is_active ?? true"
          :editable="!isDeleted && Boolean(item.can_update)"
          :spotlight="String(item.id) === String(source.items.value[0]?.id)"
          @updated="active => statusUpdated(item, active)"
        />
      </template>
      <span v-else-if="column.key === 'institution'" class="text-muted-foreground">{{ institutionTitle(item) || '—' }} <InactiveInstitutionBadge :active="item.institution?.is_active" /></span>
      <span v-else-if="column.key === 'email'" class="text-muted-foreground">{{ item.email || '—' }}</span>
      <span v-else-if="column.key === 'occupancy'" :class="item.dutiables_count === 0 ? 'text-status-attention' : 'text-muted-foreground'">{{ item.dutiables_count === 0 ? $t('Neužimta') : $tChoice('Narys|Nariai|Narių', item.dutiables_count, { count: item.dutiables_count }) }}</span>
    </template>

    <template #preview="{ item, actions: recordActions }">
      <DutyPreview :key="item.id" :duty="dutyPreviewFromRecord(item)">
        <template #status>
          <CollectionActivityStatus
            entity="duties"
            :record-id="item.id"
            :active="item.is_active ?? true"
            :editable="!isDeleted && Boolean(item.can_update)"
            :spotlight="String(item.id) === String(source.items.value[0]?.id)"
            @updated="active => statusUpdated(item, active)"
          />
        </template>
        <template #actions>
          <CollectionRowActions :actions="recordActions" full-labels class="flex-wrap" @select="key => selectRowAction(key, item)" />
        </template>
      </DutyPreview>
    </template>

    <template #bulk-actions="{ selected }">
      <Button variant="brand" size="sm" :disabled="selected.length < 2" @click="mergeRecords = toMergeRecords(selected)">
        <Merge aria-hidden="true" />{{ $t('Sujungti') }}
      </Button>
    </template>

    <template #empty>
      <EmptyState :mode="isFiltered ? 'no-results' : 'empty'" :icon="DutyIcon" :title="isDeleted ? $t('Ištrintų pareigybių nėra') : $t('Pareigybių dar nėra')" :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų pareigybių.') : $t('Sukurk pareigybę ir galėsi priskirti jos narius.')" :action-label="canCreate && !isDeleted ? $t('Nauja pareigybė') : undefined" @action="router.visit(route('duties.create'))" />
    </template>
  </CollectionPage>

  <MergeRecordsDialog :open="mergeRecords.length > 0" type="duties" :records="mergeRecords" :initial-target-id="mergeTargetId" :submit-url="route('duties.mergeDuties')" target-field="target_duty_id" source-field="source_duty_ids" @close="closeMerge" @merged="merged" />
  <ConfirmDialog :open="forceDeleteTarget !== null" :title="$t('Ištrinti pareigybę visam laikui?')" :description="$t('Šis veiksmas negrįžtamas. Pareigybė bus visiškai pašalinta.')" :confirm-label="$t('Ištrinti visam laikui')" destructive @update:open="!$event && (forceDeleteTarget = null)" @confirm="forceDelete" />
  <CollectionConfirmAction :dialog="deleteActions.dialog.value" @confirm="deleteActions.confirm" @cancel="deleteActions.pending.value = null" />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ArrowRight, CircleAlert, Merge, Pencil, Plus, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionActivityStatus from '@/Components/Collection/CollectionActivityStatus.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { DutyIcon } from '@/Components/icons';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import DutyPreview from '@/Components/Duties/DutyPreview.vue';
import { dutyPreviewFromRecord } from '@/Components/Duties/dutyPreview';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';

interface Translation { lt?: string; en?: string }
type Duty = App.Entities.Duty & { name: Translation; email?: string | null; dutiables_count: number; can_update?: boolean; can_delete?: boolean; is_active?: boolean; institution?: { id: string | number; name?: Translation; is_active?: boolean; short_name?: Translation | null } | null; types?: { id: string | number; title: Translation }[] };

const props = defineProps<{
  duties: { data: Duty[]; meta: { total: number; per_page: number; current_page: number; last_page: number } };
  deletedCount?: number;
  showDeleted?: boolean;
  dutyTypes: { id: number; title: Translation }[];
  mergePreset?: { target_id: string; duties: Duty[] } | null;
}>();
const isDeleted = computed(() => Boolean(props.showDeleted));
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.duty));
const canForceDelete = computed(() => Boolean(usePage().props.auth?.can?.forceDelete?.duty));
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('duties.index', 'merge'));
const mergeMode = ref(false);
const mergeRecords = ref<MergeRecord[]>([]);
const mergeTargetId = ref<string | null>(null);
const forceDeleteTarget = ref<Duty | null>(null);

const rowActions = (item: Duty): CollectionRowAction[] => isDeleted.value
  ? [
      { key: 'restore', label: $t('Atkurti'), icon: RotateCcw, primary: !canForceDelete.value },
      ...(canForceDelete.value ? [{ key: 'forceDelete', label: $t('Ištrinti visam laikui'), icon: Trash2, destructive: true }] : []),
    ]
  : [
      { key: 'open', label: $t('Atidaryti'), icon: ArrowRight, role: 'open' as const, href: route('duties.show', item.id) },
      ...(item.can_update ? [{ key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil, href: route('duties.edit', item.id) }] : []),
      ...(item.can_delete ? [{ key: `delete:${item.id}:${titleOf(item.name)}`, label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
    ];

function selectRowAction(key: string, item: Duty): void {
  if (key.startsWith('delete:')) deleteActions.select(key);
  if (key === 'restore') restore(item);
  if (key === 'forceDelete') forceDeleteTarget.value = item;
}

const source = useDatabaseCollectionSource<Duty>({
  endpoint: route('api.v1.admin.duties.index'),
  initial: { items: props.duties.data, total: props.duties.meta.total, perPage: props.duties.meta.per_page, currentPage: props.duties.meta.current_page, lastPage: props.duties.meta.last_page },
  defaultSort: 'name:asc',
  sortOptions: () => [
    { value: 'name:asc', label: $t('Pagal pavadinimą (A–Z)') },
    { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)') },
    { value: 'email:asc', label: $t('Pagal el. paštą (A–Z)') },
    { value: 'email:desc', label: $t('Pagal el. paštą (Z–A)') },
    { value: 'dutiables_count:desc', label: $t('Daugiausia narių') },
    { value: 'dutiables_count:asc', label: $t('Mažiausiai narių') },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets: [{ field: 'data_quality', label: $t('Duomenų kokybė'), single: true, values: [
    { value: 'vacant', label: $t('Neužimtos') }, { value: 'missing_en_name', label: $t('Trūksta EN pavadinimo') },
    { value: 'missing_lt_name', label: $t('Trūksta LT pavadinimo') }, { value: 'duplicate_holders', label: $t('Pasikartojantys nariai') },
    { value: 'inactive', label: $t('Nebeveikiančios') },
    // Only live duties are compared, so the trash has none to show.
    ...(isDeleted.value ? [] : [{ value: 'possible_duplicates', label: $t('forms.duty_duplicate.filter') }]),
  ] }, {
    field: 'history',
    get label() { return $t('Istorija'); },
    single: true,
    values: [
      { value: 'any', get label() { return $t('Su istorija'); } },
      { value: 'renamed', get label() { return $t('Pervadintos'); } },
      { value: 'linked', get label() { return $t('Susietos su kitomis pareigybėmis'); } },
    ],
  }, {
    field: 'duty_type_id',
    get label() { return $t('forms.duty_type_filter.label'); },
    single: true,
    values: [
      { value: 'none', get label() { return $t('Be tipo'); } },
      ...props.dutyTypes.map(type => ({ value: String(type.id), get label() { return getTranslatedValue(type.title); } })),
    ],
  }],
});
function statusUpdated(item: Duty, active: boolean): void {
  source.patchItems([String(item.id)], { is_active: active });
  source.refresh();
}

const columns = computed<CollectionColumn[]>(() => [
  { key: 'name', label: $t('Pareigybė'), sortField: 'name' },
  { key: 'status', label: $t('Būsena'), class: 'w-40' },
  { key: 'institution', label: $t('Institucija') },
  { key: 'email', label: $t('El. paštas'), sortField: 'email' },
  { key: 'occupancy', label: $t('Nariai'), class: 'w-32', sortField: 'dutiables_count' },
]);
const dutyKey = (duty: Duty) => String(duty.id);
const titleOf = (value: Translation | string | null | undefined) => getTranslatedValue(value, undefined, '—');
const title = (duty: Duty) => titleOf(duty.name);
const institutionTitle = (duty: Duty) => titleOf(duty.institution?.short_name || duty.institution?.name);
const toMergeRecords = (duties: Duty[]): MergeRecord[] =>
  duties.map(duty => ({ id: duty.id, label: title(duty), context: institutionTitle(duty) }));
const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);
const quickFilters = computed<CollectionQuickFilter[]>(() => [
  { id: 'vacant', label: $t('Neužimtos'), active: source.filters.value.data_quality === 'vacant' },
  {
    id: 'untyped', label: $t('Be tipo'), active: source.filters.value.duty_type_id === 'none',
    spotlight: {
      id: 'duty-type-filter-v1',
      title: $t('forms.duty_type_filter.label'),
      description: $t('forms.duty_type_filter.spotlight'),
    },
  },
  {
    id: 'history', label: $t('Su istorija'), active: source.filters.value.history === 'any',
    spotlight: {
      id: 'duty-history-filter-v1',
      title: $t('Su istorija'),
      description: $t('Atrink pareigybes, kurios buvo pervadintos arba susietos su ankstesnėmis ar vėlesnėmis pareigybėmis.'),
    },
  },
  ...(isDeleted.value
    ? []
    : [{
        id: 'possible_duplicates',
        label: $t('forms.duty_duplicate.filter'),
        active: source.filters.value.data_quality === 'possible_duplicates',
        spotlight: {
          id: 'duty-duplicates-v1',
          title: $t('forms.duty_duplicate.filter'),
          description: $t('forms.duty_duplicate.filter_spotlight'),
        },
      }]),
]);
function toggleQuickFilter(id: string): void {
  if (id === 'history') {
    source.setFilter('history', source.filters.value.history === 'any' ? undefined : 'any');
    return;
  }
  if (id === 'untyped') {
    source.setFilter('duty_type_id', source.filters.value.duty_type_id === 'none' ? undefined : 'none');
    return;
  }
  source.setFilter('data_quality', source.filters.value.data_quality === id ? undefined : id);
}
if (isDeleted.value && source.filters.value.data_quality === 'possible_duplicates') {
  source.setFilter('data_quality', undefined);
}
// Opened from the duplicate warning's "merge into this one" link.
if (props.mergePreset) {
  mergeRecords.value = toMergeRecords(props.mergePreset.duties);
  mergeTargetId.value = props.mergePreset.target_id;
}

function closeMerge(): void {
  mergeRecords.value = [];
  if (mergeTargetId.value === null) {
    return;
  }
  mergeTargetId.value = null;
  // Drop the link's parameters so a reload does not reopen the dialog.
  const url = new URL(window.location.href);
  ['merge', 'source', 'target'].forEach(key => url.searchParams.delete(key));
  window.history.replaceState(window.history.state, '', url.toString());
}

function merged(): void {
  mergeRecords.value = [];
  mergeMode.value = false;
  source.refresh();
}
function restore(duty: Duty): void {
  router.patch(route('duties.restore', duty.id), {}, { preserveScroll: true, onSuccess: () => source.refresh() });
}
function forceDelete(): void {
  if (!forceDeleteTarget.value) {
    return;
  }
  const duty = forceDeleteTarget.value;
  forceDeleteTarget.value = null;
  router.delete(route('duties.forceDelete', duty.id), { preserveScroll: true, onSuccess: () => source.refresh() });
}

const deleteActions = useCollectionRecordActions({ source, routePrefix: 'duties' });
</script>
