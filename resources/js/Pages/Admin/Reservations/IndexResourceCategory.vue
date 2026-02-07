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
import { type ColumnDef } from "@tanstack/vue-table";
import { ref, computed } from "vue";

import { Icon } from "@iconify/vue";
import { capitalize } from "@/Utils/String";
import { resolveTranslatable } from "@/Utils/DataTableColumns";
import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { type IndexTablePageProps } from "@/Types/TableConfigTypes";

const props = defineProps<{
  resourceCategories: {
    data: App.Entities.ResourceCategory[];
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

const modelName = "resourceCategories";
const entityName = "resource_category";

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.ResourceCategory) => {
  return `resource-category-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.ResourceCategory, any>[]>(() => [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => {
      const name = resolveTranslatable(row.getValue("name"));
      return (
        <div class="max-w-[300px] truncate" title={name}>
          {name}
        </div>
      );
    },
    size: 300,
  },
  {
    accessorKey: "icon",
    header: () => "Ikona",
    cell: ({ row }) => {
      const icon = row.original.icon;
      return icon ? (
        <div class="flex items-center gap-2">
          <Icon icon={`fluent:${icon}`} />
          <span>{icon}</span>
        </div>
      ) : (
        <span class="text-muted-foreground">-</span>
      );
    },
    size: 200,
  },
  createStandardActionsColumn<App.Entities.ResourceCategory>("resourceCategories", {
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.ResourceCategory>>(() => ({
  modelName,
  entityName,
  data: props.resourceCategories.data,
  columns: columns.value,
  getRowId,
  totalCount: props.resourceCategories.meta.total,
  initialPage: props.resourceCategories.meta.current_page,
  pageSize: props.resourceCategories.meta.per_page,

  initialFilters: props.filters,
  initialSorting: props.sorting,
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: false,

  headerTitle: capitalize($tChoice("entities.resource_category.model", 2)),
  icon: Icons.RESOURCE_CATEGORY,
  createRoute: route("resourceCategories.create"),
  canCreate: true,
}));

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
