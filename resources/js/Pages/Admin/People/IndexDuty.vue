<template>
  <CollectionPage
    :source
    collection="duties"
    entity-type="duty"
    :eyebrow="isDeleted ? $t('Ištrintos pareigybės') : `${$t('shell.workspaces.organizacija.title')} · ${$t('shell.sections.pareigybes')}`"
    :title="isDeleted ? $t('Ištrintos pareigybės') : $t('Pareigybės')"
    :lead="isDeleted ? $t('Peržiūrėk ištrintas pareigybes arba jas atkurk.') : $t('Tvarkyk pareigybes ir greitai pastebėk neužimtas vietas.')"
    default-view="rows"
    :item-key="dutyKey"
    :columns
    :quick-filters
    :search-placeholder="$t('Ieškoti pareigybių')"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <Button v-if="isDeleted" as-child variant="ghost">
        <Link :href="route('duties.index')">‹ {{ $t('Visos pareigybės') }}</Link>
      </Button>
      <Button v-else-if="deletedCount > 0" as-child variant="ghost">
        <Link :href="route('duties.index', { showDeleted: 'true' })"><Trash2 aria-hidden="true" />{{ $t('Ištrinti') }} ({{ deletedCount }})</Link>
      </Button>
      <Button v-if="canMerge && !isDeleted" variant="ghost" @click="mergeMode = true"><Merge aria-hidden="true" />{{ $t('Sujungti') }}</Button>
      <Button v-if="canCreate && !isDeleted" as-child variant="brand">
        <Link :href="route('duties.create')"><Plus aria-hidden="true" />{{ $t('Nauja pareigybė') }}</Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-16 items-start gap-3 px-3 py-3 sm:px-4">
        <Checkbox v-if="mergeMode" class="mt-1" :model-value="selectedIds.includes(dutyKey(item))" :aria-label="$t('Pasirinkti :name', { name: title(item) })" @update:model-value="toggle(item)" />
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-3">
            <Link :href="route('duties.show', item.id)" class="font-medium hover:text-brand">{{ title(item) }}</Link>
            <span v-if="item.dutiables_count === 0" class="inline-flex shrink-0 items-center gap-1 text-xs text-status-attention"><CircleAlert class="size-3" aria-hidden="true" />{{ $t('Neužimta') }}</span>
          </div>
          <p v-if="institutionTitle(item)" class="mt-1 text-sm text-muted-foreground">{{ institutionTitle(item) }}</p>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span v-if="item.email" class="truncate">{{ item.email }}</span>
            <span v-if="item.types?.length">{{ item.types.map(type => titleOf(type.title)).join(', ') }}</span>
          </div>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <Checkbox v-if="column.key === 'select'" :model-value="selectedIds.includes(dutyKey(item))" :aria-label="$t('Pasirinkti :name', { name: title(item) })" @update:model-value="toggle(item)" />
      <Link v-else-if="column.key === 'name'" :href="route('duties.show', item.id)" class="font-medium hover:text-brand">{{ title(item) }}</Link>
      <span v-else-if="column.key === 'institution'" class="text-muted-foreground">{{ institutionTitle(item) || '—' }}</span>
      <span v-else-if="column.key === 'email'" class="text-muted-foreground">{{ item.email || '—' }}</span>
      <span v-else-if="column.key === 'occupancy'" :class="item.dutiables_count === 0 ? 'text-status-attention' : 'text-muted-foreground'">{{ item.dutiables_count === 0 ? $t('Neužimta') : $tChoice('Narys|Nariai|Narių', item.dutiables_count, { count: item.dutiables_count }) }}</span>
    </template>

    <template #preview="{ item }">
      <section class="flex flex-col gap-4 p-5">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ $t('Pareigybė') }}</p>
          <Link :href="route('duties.show', item.id)" class="mt-1 block text-lg font-semibold hover:text-brand">{{ title(item) }}</Link>
          <p v-if="institutionTitle(item)" class="mt-1 text-sm text-muted-foreground">{{ institutionTitle(item) }}</p>
        </div>
        <dl class="grid gap-3 border-y border-border py-4 text-sm">
          <div><dt class="text-xs text-muted-foreground">{{ $t('El. paštas') }}</dt><dd class="mt-1">{{ item.email || '—' }}</dd></div>
          <div><dt class="text-xs text-muted-foreground">{{ $t('Nariai') }}</dt><dd class="mt-1" :class="item.dutiables_count === 0 ? 'text-status-attention' : ''">{{ item.dutiables_count || $t('Neužimta') }}</dd></div>
        </dl>
        <template v-if="isDeleted">
          <Button variant="outline" @click="restore(item)"><RotateCcw aria-hidden="true" />{{ $t('Atkurti') }}</Button>
          <Button variant="ghost" class="text-destructive hover:text-destructive" @click="forceDeleteTarget = item"><Trash2 aria-hidden="true" />{{ $t('Ištrinti visam laikui') }}</Button>
        </template>
        <template v-else>
          <Button as-child variant="brand"><Link :href="route('duties.show', item.id)">{{ $t('Atidaryti') }}</Link></Button>
          <Button v-if="canUpdate" as-child variant="outline"><Link :href="route('duties.edit', item.id)">{{ $t('Redaguoti') }}</Link></Button>
        </template>
      </section>
    </template>

    <template #empty>
      <EmptyState :mode="isFiltered ? 'no-results' : 'empty'" :icon="DutyIcon" :title="isDeleted ? $t('Ištrintų pareigybių nėra') : $t('Pareigybių dar nėra')" :description="isDeleted ? $t('Šiukšliadėžėje nėra pašalintų pareigybių.') : $t('Sukurk pareigybę ir galėsi priskirti jos narius.')" :action-label="canCreate && !isDeleted ? $t('Nauja pareigybė') : undefined" @action="router.visit(route('duties.create'))" />
    </template>
  </CollectionPage>

  <CollectionSelectionBar v-if="mergeMode" :count="selectedIds.length" :count-label="$t('Pasirinkta')" @clear="leaveMerge">
    <Button variant="brand" size="sm" :disabled="selectedIds.length < 2" @click="mergeRecords = selectedDuties"><Merge aria-hidden="true" />{{ $t('Sujungti') }}</Button>
  </CollectionSelectionBar>
  <MergeRecordsDialog :open="mergeRecords.length > 0" type="duties" :records="mergeRecords" :submit-url="route('duties.mergeDuties')" target-field="target_duty_id" source-field="source_duty_ids" @close="mergeRecords = []" @merged="merged" />
  <ConfirmDialog :open="forceDeleteTarget !== null" :title="$t('Ištrinti pareigybę visam laikui?')" :description="$t('Šis veiksmas negrįžtamas. Pareigybė bus visiškai pašalinta.')" :confirm-label="$t('Ištrinti visam laikui')" destructive @update:open="!$event && (forceDeleteTarget = null)" @confirm="forceDelete" />
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { CircleAlert, Merge, Plus, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { DutyIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';

type Translation = { lt?: string; en?: string };
type Duty = App.Entities.Duty & { name: Translation; email?: string | null; dutiables_count: number; institution?: { id: string | number; name?: Translation; short_name?: Translation | null } | null; types?: { id: string | number; title: Translation }[] };

const props = defineProps<{ duties: { data: Duty[]; meta: { total: number; per_page: number; current_page: number; last_page: number } }; deletedCount?: number; showDeleted?: boolean }>();
const isDeleted = computed(() => Boolean(props.showDeleted));
const canCreate = computed(() => Boolean(usePage().props.auth?.can?.create?.duty));
const canUpdate = computed(() => Boolean(usePage().props.auth?.can?.update?.duty));
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('duties.index', 'merge'));
const mergeMode = ref(false);
const selectedIds = ref<string[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);
const forceDeleteTarget = ref<Duty | null>(null);

const source = useDatabaseCollectionSource<Duty>({
  endpoint: route('api.v1.admin.duties.index'),
  initial: { items: props.duties.data, total: props.duties.meta.total, perPage: props.duties.meta.per_page, currentPage: props.duties.meta.current_page, lastPage: props.duties.meta.last_page },
  defaultSort: 'name:asc',
  sortOptions: [{ value: 'name:asc', label: $t('Pagal pavadinimą') }, { value: 'name:desc', label: $t('Pagal pavadinimą (Z–A)') }],
  preserveUrlKeys: ['showDeleted'],
  facets: [{ field: 'data_quality', label: $t('Duomenų kokybė'), single: true, values: [
    { value: 'vacant', label: $t('Neužimtos') }, { value: 'missing_en_name', label: $t('Trūksta EN pavadinimo') },
    { value: 'missing_lt_name', label: $t('Trūksta LT pavadinimo') }, { value: 'duplicate_holders', label: $t('Pasikartojantys nariai') },
  ] }],
});
const columns = computed<CollectionColumn[]>(() => [
  ...(mergeMode.value ? [{ key: 'select', label: $t('Pasirinkti'), class: 'w-12' }] : []),
  { key: 'name', label: $t('Pareigybė') }, { key: 'institution', label: $t('Institucija') }, { key: 'email', label: $t('El. paštas') }, { key: 'occupancy', label: $t('Nariai'), class: 'w-32' },
]);
const dutyKey = (duty: Duty) => String(duty.id);
const titleOf = (value: Translation | string | null | undefined) => typeof value === 'string' ? value : value?.lt || value?.en || '—';
const title = (duty: Duty) => titleOf(duty.name);
const institutionTitle = (duty: Duty) => titleOf(duty.institution?.short_name || duty.institution?.name);
const selectedDuties = computed<MergeRecord[]>(() => source.items.value
  .filter(duty => selectedIds.value.includes(dutyKey(duty)))
  .map(duty => ({ id: duty.id, label: title(duty), context: institutionTitle(duty) })));
const isFiltered = computed(() => source.query.value.trim() !== '' || source.activeFilterCount.value > 0);
const quickFilters = computed<CollectionQuickFilter[]>(() => [{ id: 'vacant', label: $t('Neužimtos'), active: source.filters.value.data_quality === 'vacant' }]);
function toggleQuickFilter(id: string): void {
  if (id === 'vacant') {
    source.setFilter('data_quality', source.filters.value.data_quality === 'vacant' ? undefined : 'vacant');
  }
}
function toggle(duty: Duty): void {
  const id = dutyKey(duty);
  selectedIds.value = selectedIds.value.includes(id)
    ? selectedIds.value.filter(selected => selected !== id)
    : [...selectedIds.value, id];
}
function leaveMerge(): void {
  mergeMode.value = false;
  selectedIds.value = [];
}
function merged(): void {
  mergeRecords.value = [];
  leaveMerge();
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
</script>
