<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  />
</template>

<script setup lang="tsx">
import { transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { capitalize } from '@/Utils/String';
import { resolveTranslatable } from '@/Utils/DataTableColumns';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';

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

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Duty) => {
  return `duty-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Duty, any>[]>(() => [
  {
    accessorKey: 'name',
    header: () => 'Pavadinimas',
    cell: ({ row }) => resolveTranslatable(row.getValue('name')),
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'email',
    header: () => 'El. paštas',
    cell: ({ row }) => {
      const { email } = row.original;
      if (!email) return null;
      return (
        <a href={`mailto:${email}`} class="transition hover:text-vusa-red">
          <div class="max-w-[200px] truncate" title={email}>
            {email}
          </div>
        </a>
      );
    },
    size: 200,
  },
  {
    accessorKey: 'institution',
    header: () => 'Institucija',
    cell: ({ row }) => {
      const { institution } = row.original;
      if (!institution) return null;
      const displayName = resolveTranslatable(institution.short_name ?? institution.name);
      return (
        <a
          href={route('institutions.edit', { id: institution.id })}
          target="_blank"
          class="transition hover:text-vusa-red"
        >
          <Button variant="ghost" size="xs" class="rounded-full">
            <Icons.INSTITUTION />
            <span class="max-w-[150px] truncate" title={displayName}>
              {displayName}
            </span>
          </Button>
        </a>
      );
    },
    size: 200,
  },
  {
    accessorKey: 'types',
    header: () => 'Tipai',
    cell: ({ row }) => {
      const { types } = row.original;
      if (!types?.length) return null;
      return (
        <div class="flex flex-wrap gap-1">
          {types.map(type => (
            <Badge key={type.id} variant="secondary" class="text-xs">
              {resolveTranslatable(type.title)}
            </Badge>
          ))}
        </div>
      );
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
  initialSorting: props.sorting,
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: false,
  allowToggleDeleted: true,

  headerTitle: capitalize($tChoice('entities.duty.model', 2)),
  icon: Icons.DUTY,
  createRoute: route('duties.create'),
  canCreate: true,
}));

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
