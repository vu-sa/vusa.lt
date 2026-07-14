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

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { RoleIcon } from '@/Components/icons';
import {
  createTextColumn,
  createTimestampColumn,
} from '@/Composables/useDataTableColumns';

const props = defineProps<{
  roles: {
    data: App.Entities.Role[];
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

const modelName = 'roles';
const entityName = 'role';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const getRowId = (row: App.Entities.Role) => {
  return `role-${row.id}`;
};

const columns = computed(() => [
  createTextColumn<App.Entities.Role>('name', {
    title: $t('forms.fields.name'),
    width: 300,
  }),
  createTimestampColumn<App.Entities.Role>('created_at', {
    title: $t('forms.fields.created_at'),
    width: 180,
  }),
  createTimestampColumn<App.Entities.Role>('updated_at', {
    title: $t('Atnaujintas'),
    width: 180,
  }),
  createStandardActionsColumn<App.Entities.Role>('roles', {
    canView: true,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Role>>(() => {
  return {
    modelName,
    entityName,
    data: props.roles.data,
    columns: columns.value,
    getRowId,
    totalCount: props.roles.meta.total,
    initialPage: props.roles.meta.current_page,
    pageSize: props.roles.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'created_at', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Rolės',
    icon: RoleIcon,
    createRoute: route('roles.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
