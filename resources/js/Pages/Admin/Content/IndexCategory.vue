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
import { ref, computed } from 'vue';
import type { IndexTablePageInstance } from '@/Types/TableConfigTypes';

import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { CategoryIcon } from '@/Components/icons';
import {
  createTitleColumn,
  createTextColumn,
} from '@/Composables/useDataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  categories: {
    data: App.Entities.Category[];
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

const modelName = 'categories';
const entityName = 'category';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const canCreate = computed(() => true);

const getRowId = (row: App.Entities.Category) => {
  return `category-${row.id}`;
};

const columns = computed(() => [
  createTitleColumn<App.Entities.Category>({
    accessorKey: 'name',
    routeName: 'categories.edit',
    width: 300,
  }),
  createTextColumn<App.Entities.Category>('alias', {
    title: 'Slug',
    width: 200,
  }),
  createStandardActionsColumn<App.Entities.Category>('categories', {
    canView: false,
    canEdit: true,
    canDelete: false,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Category>>(() => {
  return {
    modelName,
    entityName,
    data: props.categories.data,
    columns: columns.value,
    getRowId,
    totalCount: props.categories.meta.total,
    initialPage: props.categories.meta.current_page,
    pageSize: props.categories.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'name', desc: false }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Kategorijos',
    icon: CategoryIcon,
    createRoute: canCreate.value ? route('categories.create') : undefined,
    canCreate: canCreate.value,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
