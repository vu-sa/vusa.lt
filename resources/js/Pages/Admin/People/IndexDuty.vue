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
    </template>
    <template #filters>
      <div class="flex items-center gap-2">
        <Switch id="show-external-duties" :model-value="showExternal" @update:model-value="handleShowExternalChange" />
        <Label for="show-external-duties" class="text-sm font-normal">{{ $t('forms.fields.show_external_duties') }}</Label>
      </div>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { TagList, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { capitalize } from '@/Utils/String';
import { resolveTranslatable } from '@/Composables/useDataTableColumns';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import { DutyIcon, InstitutionIcon, UserIcon } from '@/Components/icons';

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
}>();

const modelName = 'duties';
const entityName = 'duty';

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// "External" = duties owned by another tenant but assignable to the current
// user's tenant. Included by default; the toggle drives a `show_external` table filter.
const showExternal = ref<boolean>(props.filters?.show_external !== false);

const handleShowExternalChange = (value: boolean) => {
  showExternal.value = value;
  indexTablePageRef.value?.updateFilter('show_external', value ? undefined : false);
};

const getRowId = (row: App.Entities.Duty) => {
  return `duty-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.Duty, any>>>(() => [
  {
    accessorKey: 'name',
    header: () => $t('Pavadinimas'),
    cell: ({ row }) => h(TruncatedText, { text: resolveTranslatable(row.getValue('name')) }),
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
