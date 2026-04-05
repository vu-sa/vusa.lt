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

import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import {
  createTextColumn,
  createTimestampColumn,
} from '@/Utils/DataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  permissions: {
    data: App.Entities.Permission[];
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

const modelName = 'permissions';
const entityName = 'permission';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Permission) => {
  return `permission-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Permission, any>[]>(() => [
  createTextColumn<App.Entities.Permission>('name', {
    title: $t('forms.fields.name'),
    width: 300,
  }),
  createTimestampColumn<App.Entities.Permission>('created_at', {
    title: $t('forms.fields.created_at'),
    width: 180,
  }),
  createTimestampColumn<App.Entities.Permission>('updated_at', {
    title: $t('Atnaujintas'),
    width: 180,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Permission>>(() => {
  return {
    modelName,
    entityName,
    data: props.permissions.data,
    columns: columns.value,
    getRowId,
    totalCount: props.permissions.meta.total,
    initialPage: props.permissions.meta.current_page,
    pageSize: props.permissions.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Leidimai',
    icon: Icons.PERMISSION,
    canCreate: false,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
