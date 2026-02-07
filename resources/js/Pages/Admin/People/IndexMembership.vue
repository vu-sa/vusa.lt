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

import { capitalize } from "@/Utils/String";
import { resolveTranslatable } from "@/Utils/DataTableColumns";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { type IndexTablePageProps } from "@/Types/TableConfigTypes";

const props = defineProps<{
  memberships: {
    data: App.Entities.Membership[];
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

const modelName = "memberships";
const entityName = "membership";

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Membership) => {
  return `membership-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Membership, any>[]>(() => [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => resolveTranslatable(row.getValue("name")),
    size: 250,
  },
  {
    accessorKey: "tenant",
    header: () => $t("Padalinys"),
    cell: ({ row }) => {
      const tenant = row.original.tenant;
      return tenant ? $t(tenant.shortname ?? "") : null;
    },
    size: 200,
  },
  createStandardActionsColumn<App.Entities.Membership>("memberships", {
    canEdit: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Membership>>(() => ({
  modelName,
  entityName,
  data: props.memberships.data,
  columns: columns.value,
  getRowId,
  totalCount: props.memberships.meta.total,
  initialPage: props.memberships.meta.current_page,
  pageSize: props.memberships.meta.per_page,

  initialFilters: props.filters,
  initialSorting: props.sorting,
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: false,

  headerTitle: capitalize($tChoice("entities.membership.model", 2)),
  createRoute: route("memberships.create"),
  canCreate: true,
}));

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
