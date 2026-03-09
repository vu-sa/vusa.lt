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
import { trans as $t } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from "vue";

import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import {
  createTitleColumn,
  createTextColumn,
} from '@/Utils/DataTableColumns';
import {
  type IndexTablePageProps
} from "@/Types/TableConfigTypes";

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

const indexTablePageRef = ref<any>(null);

const canCreate = computed(() => true);

const getRowId = (row: App.Entities.Category) => {
  return `category-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Category, any>[]>(() => [
  createTitleColumn<App.Entities.Category>({
    accessorKey: "name",
    routeName: "categories.edit",
    width: 300,
  }),
  createTextColumn<App.Entities.Category>("alias", {
    title: "Slug",
    width: 200,
  }),
  createStandardActionsColumn<App.Entities.Category>("categories", {
    canView: false,
    canEdit: true,
    canDelete: false,
  })
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
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: "Kategorijos",
    icon: Icons.CATEGORY,
    createRoute: canCreate.value ? route('categories.create') : undefined,
    canCreate: canCreate.value,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
