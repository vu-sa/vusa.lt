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
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { TenantIcon } from '@/Components/icons';
import {
  createTextColumn,
} from '@/Composables/useDataTableColumns';

const props = defineProps<{
  tenants: {
    data: App.Entities.Tenant[];
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

const modelName = 'tenants';
const entityName = 'tenant';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const getRowId = (row: App.Entities.Tenant) => {
  return `tenant-${row.id}`;
};

const columns = computed(() => [
  createTextColumn<App.Entities.Tenant>('fullname', {
    title: $t('forms.fields.fullname'),
    width: 300,
  }),
  createTextColumn<App.Entities.Tenant>('shortname', {
    title: $t('forms.fields.shortname'),
    width: 200,
  }),
  createTextColumn<App.Entities.Tenant>('alias', {
    title: $t('forms.fields.alias'),
    width: 100,
  }),
  createTextColumn<App.Entities.Tenant>('type', {
    title: $tChoice('forms.fields.type', 1),
    width: 150,
  }),
  createStandardActionsColumn<App.Entities.Tenant>('tenants', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Tenant>>(() => {
  return {
    modelName,
    entityName,
    data: props.tenants.data,
    columns: columns.value,
    getRowId,
    totalCount: props.tenants.meta.total,
    initialPage: props.tenants.meta.current_page,
    pageSize: props.tenants.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'fullname', desc: false }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Padaliniai',
    icon: TenantIcon,
    createRoute: route('tenants.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
