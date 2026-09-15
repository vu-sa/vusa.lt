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

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { usePage } from '@inertiajs/vue3';
import { h, ref, computed } from 'vue';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { TruncatedBadge, TruncatedText } from '@/Components/ui/data-table/cells';
import { CalendarIcon } from '@/Components/icons';
import {
  createTitleColumn,
  createTextColumn,
} from '@/Composables/useDataTableColumns';

const props = defineProps<{
  eventTypes: {
    data: App.Entities.EventType[];
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

const modelName = 'eventTypes';
const entityName = 'eventType';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const canCreate = computed(() => usePage().props.auth?.can?.create?.eventType ?? false);
const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.eventType ?? false);

const getRowId = (row: App.Entities.EventType) => {
  return `event-type-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.EventType, any>>>(() => [
  createTitleColumn<App.Entities.EventType>({
    accessorKey: 'name',
    routeName: 'eventTypes.edit',
    width: 300,
  }),
  createTextColumn<App.Entities.EventType>('slug', {
    title: 'Slug',
    width: 200,
  }),
  {
    accessorKey: 'is_active',
    header: () => $t('forms.fields.is_active'),
    cell: ({ row }) => {
      if (row.original.is_active) return null;

      return h(TruncatedBadge, { text: $t('Neaktyvus'), variant: 'secondary' });
    },
    size: 120,
  },
  createStandardActionsColumn<App.Entities.EventType>('eventTypes', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.EventType>>(() => {
  return {
    modelName,
    entityName,
    data: props.eventTypes.data,
    columns: columns.value,
    getRowId,
    totalCount: props.eventTypes.meta.total,
    initialPage: props.eventTypes.meta.current_page,
    pageSize: props.eventTypes.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'sort_order', desc: false }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,
    deletedCount: props.deletedCount,

    headerTitle: 'Renginių tipai',
    icon: CalendarIcon,
    createRoute: canCreate.value ? route('eventTypes.create') : undefined,
    canCreate: canCreate.value,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
