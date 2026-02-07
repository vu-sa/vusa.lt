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
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from "vue";

import Icons from "@/Types/Icons/regular";
import { capitalize } from "@/Utils/String";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import {
  createTextColumn,
} from '@/Utils/DataTableColumns';
import {
  type IndexTablePageProps
} from "@/Types/TableConfigTypes";

const props = defineProps<{
  trainings: {
    data: App.Entities.Training[];
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

const modelName = 'trainings';
const entityName = 'training';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Training) => {
  return `training-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Training, any>[]>(() => [
  createTextColumn<App.Entities.Training>("name", {
    title: $t("forms.fields.name"),
    width: 400,
  }),
  createStandardActionsColumn<App.Entities.Training>("trainings", {
    canView: true,
    canEdit: true,
    canDelete: false,
  })
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Training>>(() => {
  return {
    modelName,
    entityName,
    data: props.trainings.data,
    columns: columns.value,
    getRowId,
    totalCount: props.trainings.meta.total,
    initialPage: props.trainings.meta.current_page,
    pageSize: props.trainings.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: capitalize($tChoice('entities.training.model', 2)),
    icon: Icons.TRAINING,
    createRoute: route('trainings.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
