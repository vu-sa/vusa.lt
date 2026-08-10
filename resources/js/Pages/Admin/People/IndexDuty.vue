<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <template #headerActions>
      <Link :href="route('duties.updateUsersWizard')">
        <Button variant="outline" size="sm">
          <UserIcon class="size-4" />
          {{ $t('forms.fields.duty_user_wizard') }}
        </Button>
      </Link>
      <Button variant="outline" size="sm" as-child class="gap-1.5">
        <Link :href="route('duties.merge')">
          <MergeIcon class="size-4" />
          {{ $t('Sulieti pareigybes') }}
        </Link>
      </Button>
    </template>
    <template #filters>
      <div class="flex items-center gap-2">
        <Switch id="show-external-duties" :model-value="showExternal" @update:model-value="handleShowExternalChange" />
        <Label for="show-external-duties" class="text-sm font-normal">{{ $t('forms.fields.show_external_duties') }}</Label>
      </div>
      <div class="flex items-center gap-2">
        <Label for="data-quality-filter" class="text-sm font-normal whitespace-nowrap">{{ $t('forms.fields.data_quality_filter') }}</Label>
        <Select v-model="dataQualityModel">
          <SelectTrigger id="data-quality-filter" class="h-8 w-52">
            <SelectValue :placeholder="$t('forms.fields.data_quality_all')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{{ $t('forms.fields.data_quality_all') }}</SelectItem>
            <SelectItem value="vacant">{{ $t('forms.fields.data_quality_vacant') }}</SelectItem>
            <SelectItem value="missing_en_name">{{ $t('forms.fields.data_quality_missing_en_name') }}</SelectItem>
            <SelectItem value="missing_lt_name">{{ $t('forms.fields.data_quality_missing_lt_name') }}</SelectItem>
            <SelectItem value="duplicate_holders">{{ $t('forms.fields.data_quality_duplicate_holders') }}</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { TagList, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { capitalize } from '@/Utils/String';
import { resolveTranslatable } from '@/Composables/useDataTableColumns';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import { Merge as MergeIcon } from 'lucide-vue-next';
import { DutyIcon, InstitutionIcon, UserIcon } from '@/Components/icons';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';

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

const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.duty ?? false);

// "External" = duties owned by another tenant but assignable to the current
// user's tenant. Included by default; the toggle drives a `show_external` table filter.
const showExternal = ref<boolean>(props.filters?.show_external !== false);

const handleShowExternalChange = (value: boolean) => {
  showExternal.value = value;
  indexTablePageRef.value?.updateFilter('show_external', value ? undefined : false);
};

// Data-quality slice (vacant duties, missing localized names, duplicate holders).
// "all" is the neutral default — selecting it drops the filter so the full list returns.
const dataQuality = ref<string | undefined>(props.filters?.data_quality);

const dataQualityModel = computed<string>({
  get: () => dataQuality.value ?? 'all',
  set: (value: string) => {
    const next = value === 'all' ? undefined : value;
    dataQuality.value = next;
    indexTablePageRef.value?.updateFilter('data_quality', next);
  },
});

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
  enableRowSelection: false,
  allowToggleDeleted: true,
  showDeleted: props.showDeleted,
  deletedCount: props.deletedCount,

  headerTitle: capitalize($tChoice('entities.duty.model', 2)),
  icon: DutyIcon,
  createRoute: route('duties.create'),
  canCreate: true,
}));

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
