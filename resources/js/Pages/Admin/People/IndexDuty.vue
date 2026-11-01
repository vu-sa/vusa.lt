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
    :row-muted="isInactive"
    :columns
    table-fixed
    actions-width="w-44"
    table-min-width="min-w-[64rem]"
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

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow :actions="view === 'preview' ? [] : recordActions" :spotlight="item === source.items.value[0]" class="px-3 py-2.5 sm:px-4" data-slot="duty-collection-row" @select="key => selectRowAction(key, item)">
        <CollectionPrimaryCell :title="title(item)" :href="isDeleted ? undefined : route('duties.show', item.id)">
          <span class="inline-flex items-baseline gap-1.5">
            <template v-if="isInactive(item)">
              <component :is="operatingStatuses.inactive.icon" class="size-4 shrink-0 self-center" aria-hidden="true" data-slot="duty-inactive-mark" />
              <span class="sr-only">{{ $t(operatingStatuses.inactive.label) }}:</span>
            </template>
            <InflectedDutyName :name="title(item)" />
          </span>
        </CollectionPrimaryCell>
        <p class="mt-0.5 text-xs text-muted-foreground">{{ rowFacts(item).join(' · ') }}</p>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <CollectionPrimaryCell v-if="column.key === 'name'" :title="title(item)" :href="isDeleted ? undefined : route('duties.show', item.id)">
        <span class="inline-flex items-baseline gap-1.5">
          <template v-if="isInactive(item)">
            <component :is="operatingStatuses.inactive.icon" class="size-4 shrink-0 self-center" aria-hidden="true" data-slot="duty-inactive-mark" />
            <span class="sr-only">{{ $t(operatingStatuses.inactive.label) }}:</span>
          </template>
          <InflectedDutyName :name="title(item)" />
        </span>
      </CollectionPrimaryCell>
      <span v-else-if="column.key === 'institution'" class="text-muted-foreground">{{ institutionTitle(item) || '—' }} <InactiveInstitutionBadge :active="item.institution?.is_active" /></span>
      <span v-else-if="column.key === 'email'" class="text-muted-foreground">{{ item.email || '—' }}</span>
      <span v-else-if="column.key === 'occupancy'" :class="!isInactive(item) && item.dutiables_count === 0 ? 'text-status-attention' : 'text-muted-foreground'">{{ isInactive(item) && item.dutiables_count === 0 ? '—' : item.dutiables_count === 0 ? $t('Neužimta') : $tChoice('Narys|Nariai|Narių', item.dutiables_count, { count: item.dutiables_count }) }}</span>
      <span v-else-if="column.key === 'created_at' || column.key === 'updated_at'" class="tabular-nums">{{ formatRecordDate(item[column.key]) }}</span>
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
          <CollectionRowActions :actions="recordActions" emphasis full-labels @select="key => selectRowAction(key, item)" />
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
import { ArrowRight, Merge, Pencil, Plus, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import { operatingStatuses } from '@/Constants/statuses';
import { useCollectionRecordActions } from '@/Composables/useCollectionRecordActions';
import CollectionConfirmAction from '@/Components/Collection/CollectionConfirmAction.vue';
import CollectionActivityStatus from '@/Components/Collection/CollectionActivityStatus.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { formatRecordDate } from '@/Utils/dateTime';
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
      { key: 'open', label: $t('Atidaryti'), icon: ArrowRight, role: 'open' as const, primary: true, href: route('duties.show', item.id) },
      ...(item.can_update ? [{ key: 'edit', role: 'edit' as const, menuOnly: true, label: $t('Redaguoti'), icon: Pencil, href: route('duties.edit', item.id) }] : []),
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
    { value: 'created_at:asc', label: `${$t('Sukurta')} · ↑` },
    { value: 'created_at:desc', label: `${$t('Sukurta')} · ↓` },
    { value: 'updated_at:asc', label: `${$t('Atnaujinta')} · ↑` },
    { value: 'updated_at:desc', label: `${$t('Atnaujinta')} · ↓` },
  ],
  preserveUrlKeys: ['showDeleted'],
  facets: [{ field: 'is_active', label: $t('Būsena'), single: true, values: [
    { value: '1', label: $t(operatingStatuses.active.label) },
    { value: '0', label: $t(operatingStatuses.inactive.label) },
  ] }, { field: 'data_quality', label: $t('Duomenų kokybė'), single: true, values: [
    { value: 'vacant', label: $t('Neužimtos') }, { value: 'missing_en_name', label: $t('Trūksta EN pavadinimo') },
    { value: 'missing_lt_name', label: $t('Trūksta LT pavadinimo') }, { value: 'duplicate_holders', label: $t('Pasikartojantys nariai') },
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
  { key: 'institution', label: $t('Institucija'), class: 'w-52' },
  { key: 'email', label: $t('El. paštas'), class: 'w-52', sortField: 'email' },
  { key: 'occupancy', label: $t('Nariai'), class: 'w-32', sortField: 'dutiables_count' },
  { key: 'created_at', label: $t('Sukurta'), class: 'w-36', sortField: 'created_at', defaultHidden: true },
  { key: 'updated_at', label: $t('Atnaujinta'), class: 'w-36', sortField: 'updated_at', defaultHidden: true },
]);
const isInactive = (item: Duty) => item.is_active === false;
const rowFacts = (item: Duty) => [institutionTitle(item), item.email, item.types?.map(type => titleOf(type.title)).join(', ')].filter(Boolean);
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
