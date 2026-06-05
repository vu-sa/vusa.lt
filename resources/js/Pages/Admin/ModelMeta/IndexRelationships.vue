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
import { RelationshipIcon } from '@/Components/icons';
import {
  createTextColumn,
  createIdColumn,
} from '@/Composables/useDataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  relationships: {
    data: App.Entities.Relationship[];
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

const modelName = 'relationships';
const entityName = 'relationship';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const getRowId = (row: App.Entities.Relationship) => {
  return `relationship-${row.id}`;
};

const columns = computed(() => [
  createIdColumn<App.Entities.Relationship>(),
  createTextColumn<App.Entities.Relationship>('name', {
    title: $t('forms.fields.name'),
    width: 250,
  }),
  createTextColumn<App.Entities.Relationship>('slug', {
    title: $t('Techninė žymė'),
    width: 200,
  }),
  createTextColumn<App.Entities.Relationship>('description', {
    title: $t('forms.fields.description'),
    width: 300,
  }),
  createStandardActionsColumn<App.Entities.Relationship>('relationships', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Relationship>>(() => {
  return {
    modelName,
    entityName,
    data: props.relationships.data,
    columns: columns.value,
    getRowId,
    totalCount: props.relationships.meta.total,
    initialPage: props.relationships.meta.current_page,
    pageSize: props.relationships.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Ryšiai',
    icon: RelationshipIcon,
    createRoute: route('relationships.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
