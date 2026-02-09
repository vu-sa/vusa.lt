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
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';

import { formatStaticTime } from '@/Utils/IntlTime';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createTenantColumn,
} from '@/Utils/DataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  calendar: {
    data: App.Entities.Calendar[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  allCategories: App.Entities.Category[];
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
}>();

const modelName = 'calendar';
const entityName = 'calendar';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Calendar) => {
  return `calendar-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Calendar, any>[]>(() => [
  {
    accessorKey: 'title',
    header: () => 'Pavadinimas',
    cell: ({ row }) => {
      const title = row.getValue('title');
      const displayTitle = typeof title === 'object' && title !== null
        ? ((title as any).lt || (title as any).en || '-')
        : title;
      return (
        <div class="max-w-[200px] truncate" title={displayTitle as string}>
          {displayTitle}
        </div>
      );
    },
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'date',
    header: () => 'Data',
    cell: ({ row }) => {
      const { date } = row.original;
      if (!date) return null;
      return formatStaticTime(date, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
      });
    },
    size: 200,
  },
  {
    id: 'category',
    header: () => 'Kategorija',
    cell: ({ row }) => {
      const { category } = row.original;
      if (!category) return null;
      const name = typeof category.name === 'object' && category.name !== null
        ? ((category.name as any).lt || (category.name as any).en || '-')
        : category.name;
      return name;
    },
    size: 150,
  },
  {
    accessorKey: 'is_draft',
    header: () => 'Ar rodomas?',
    cell: ({ row }) => {
      return row.original.is_draft ? '❌ Ne' : '✅ Taip';
    },
    size: 100,
  },
  createTenantColumn<App.Entities.Calendar>(),
  createStandardActionsColumn<App.Entities.Calendar>('calendar', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canDuplicate: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Calendar>>(() => {
  return {
    modelName,
    entityName,
    data: props.calendar.data,
    columns: columns.value,
    getRowId,
    totalCount: props.calendar.meta.total,
    initialPage: props.calendar.meta.current_page,
    pageSize: props.calendar.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Renginiai',
    icon: Icons.CALENDAR,
    createRoute: route('calendar.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
