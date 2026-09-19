<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
    @update:row-selection="handleRowSelectionChange"
  >
    <template #filters>
      <DataTableFilter
        v-model:value="dataQuality"
        :options="dataQualityOptions"
        filter-key="data_quality"
        @apply="handleDataQualityChange"
        @clear="handleDataQualityChange(null)"
      >
        {{ $t('forms.fields.data_quality_filter') }}
      </DataTableFilter>

      <div class="flex items-center gap-2 rounded-md border border-border bg-background px-2.5 py-1.5">
        <Switch id="show-external-duties" :model-value="showExternal" @update:model-value="handleShowExternalChange" />
        <Label for="show-external-duties" class="text-sm font-normal">{{ $t('forms.fields.show_external_duties') }}</Label>
      </div>
    </template>
    <template #headerActions>
      <Button v-if="canMerge" variant="outline" class="gap-1.5" @click="enterMergeMode">
        <MergeIcon class="h-4 w-4" />
        {{ $t('Sujungti įrašus') }}
      </Button>
    </template>
    <template #actions>
      <Button v-if="isMergeMode && selectedRows.length > 1" variant="secondary" @click="mergeRecords = selectedRows">
        {{ $t('Sujungti pasirinktus') }}
      </Button>
      <Button v-if="isMergeMode" variant="ghost" @click="leaveMergeMode">
        {{ $t('Cancel') }}
      </Button>
    </template>
  </IndexTablePage>
  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="duties"
    :records="mergeRecords"
    :submit-url="route('duties.mergeDuties')"
    target-field="target_duty_id"
    source-field="source_duty_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { Merge as MergeIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { TagList, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { capitalize } from '@/Utils/String';
import { resolveTranslatable } from '@/Composables/useDataTableColumns';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import { DutyIcon, InstitutionIcon, UserIcon } from '@/Components/icons';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';

const props = defineProps<{
  duties: {
    data: App.Entities.Duty[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const modelName = 'duties';
const entityName = 'duty';

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);
const selectedRows = ref<MergeRecord[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('duties.index', 'merge'));
const isMergeMode = computed(() => new URLSearchParams(usePage().url.split('?')[1] ?? '').get('merge') === '1');

const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.duty ?? false);

// "External" = duties owned by another tenant but assignable to the current
// user's tenant. Included by default; the toggle drives a `show_external` table filter.
const showExternal = ref<boolean>(props.filters?.show_external !== false);

const handleShowExternalChange = (value: boolean) => {
  showExternal.value = value;
  indexTablePageRef.value?.updateFilter('show_external', value ? undefined : false);
};

// Data-quality slice (vacant duties, missing localized names, duplicate holders).
// Unset is the neutral default — clearing it drops the filter so the full list returns.
const dataQuality = ref<string | null>(props.filters?.data_quality ?? null);

const dataQualityOptions = computed(() => [
  { label: $t('forms.fields.data_quality_vacant'), value: 'vacant' },
  { label: $t('forms.fields.data_quality_missing_en_name'), value: 'missing_en_name' },
  { label: $t('forms.fields.data_quality_missing_lt_name'), value: 'missing_lt_name' },
  { label: $t('forms.fields.data_quality_duplicate_holders'), value: 'duplicate_holders' },
]);

const handleDataQualityChange = (value: string | null) => {
  dataQuality.value = value;
  indexTablePageRef.value?.updateFilter('data_quality', value ?? undefined);
};

// Duty administration lives beside the list but is not what the page is for,
// so both entry points sit in the header's overflow menu.
const secondaryActions = computed(() => [
  {
    label: $t('forms.fields.duty_user_wizard'),
    icon: UserIcon,
    href: route('duties.updateUsersWizard'),
  },
]);

const getRowId = (row: App.Entities.Duty) => {
  return `duty-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.Duty, any>>>(() => [
  {
    accessorKey: 'name',
    header: () => $t('Pavadinimas'),
    // Not TruncatedText — a duty name's gendered ending is shown live (see
    // InflectedDutyName), which carries its own tooltip and wraps onto as many lines as
    // the name needs rather than cutting long names off at one line.
    cell: ({ row }) => h(InflectedDutyName, { name: resolveTranslatable(row.getValue('name')) }),
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'email',
    header: () => $t('El. paštas'),
    cell: ({ row }) => {
      const { email } = row.original;
      if (!email) return null;
      return h(TruncatedLink, {
        href: `mailto:${email}`,
        text: email,
        external: true,
        class: 'transition hover:text-vusa-red',
      });
    },
    size: 200,
  },
  {
    accessorKey: 'institution',
    header: () => $t('Institucija'),
    cell: ({ row }) => {
      const { institution } = row.original;
      if (!institution) return null;
      const displayName = resolveTranslatable(institution.short_name ?? institution.name);
      return h('a', {
        href: route('institutions.edit', { id: institution.id }),
        target: '_blank',
        class: 'transition hover:text-vusa-red',
      }, h(Button, { variant: 'ghost', size: 'xs', class: 'rounded-full' }, () => [
        h(InstitutionIcon),
        h(TruncatedText, { text: displayName }),
      ]));
    },
    size: 200,
  },
  {
    accessorKey: 'types',
    header: () => $t('Tipai'),
    cell: ({ row }) => {
      const { types } = row.original;
      if (!types?.length) return null;
      return h(TagList, {
        items: types,
        labelKey: 'title',
        maxVisible: 3,
      });
    },
    size: 200,
  },
  createStandardActionsColumn<App.Entities.Duty>('duties', {
    canView: true,
    canEdit: true,
    canDelete: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
    customActions: canMerge.value
      ? [{
          key: 'merge',
          label: $t('Sujungti su…'),
          icon: MergeIcon,
          onSelect: (row) => { mergeRecords.value = [{ id: row.id, label: resolveTranslatable(row.name), context: row.institution ? resolveTranslatable(row.institution.name) : null }]; },
        }]
      : [],
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Duty>>(() => ({
  modelName,
  entityName,
  data: props.duties.data,
  columns: columns.value,
  getRowId,
  totalCount: props.duties.meta.total,
  initialPage: props.duties.meta.current_page,
  pageSize: props.duties.meta.per_page,

  initialFilters: props.filters,
  initialSorting: props.sorting?.length ? props.sorting : [{ id: 'name', desc: false }],
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: isMergeMode.value,
  enableRowSelectionColumn: isMergeMode.value,
  allowToggleDeleted: true,
  showDeleted: props.showDeleted,
  deletedCount: props.deletedCount,

  headerTitle: capitalize($tChoice('entities.duty.model', 2)),
  icon: DutyIcon,
  createRoute: route('duties.create'),
  canCreate: true,
  secondaryActions: secondaryActions.value,
}));

const enterMergeMode = () => router.get(route('duties.index'), { merge: 1 }, { preserveState: true, preserveScroll: true });
const leaveMergeMode = () => router.get(route('duties.index'), {}, { preserveState: true, preserveScroll: true });
const merged = () => {
  mergeRecords.value = [];
  indexTablePageRef.value?.clearRowSelection();
  router.reload({ only: ['duties'] });
};
const handleRowSelectionChange = () => {
  selectedRows.value = (indexTablePageRef.value?.getSelectedRows() ?? []).map(row => ({
    id: row.id,
    label: resolveTranslatable(row.name),
    context: row.institution ? resolveTranslatable(row.institution.name) : null,
  }));
};

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
