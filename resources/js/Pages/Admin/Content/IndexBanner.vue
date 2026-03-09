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
  createTenantColumn,
} from '@/Utils/DataTableColumns';
import {
  type IndexTablePageProps
} from "@/Types/TableConfigTypes";

const props = defineProps<{
  banners: {
    data: App.Entities.Banner[];
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

const modelName = 'banners';
const entityName = 'banner';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Banner) => {
  return `banner-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.Banner, any>[]>(() => [
  {
    accessorKey: "title",
    header: () => "Pavadinimas",
    cell: ({ row }) => {
      const banner = row.original;
      return (
        <a
          class={banner.is_active ? "font-bold text-green-700" : "text-red-700"}
          href={route("banners.edit", { id: banner.id })}
        >
          {banner.title}
        </a>
      );
    },
    size: 300,
    enableSorting: true,
  },
  createTenantColumn<App.Entities.Banner>(),
  createStandardActionsColumn<App.Entities.Banner>("banners", {
    canView: false,
    canEdit: true,
    canDelete: true,
  })
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Banner>>(() => {
  return {
    modelName,
    entityName,
    data: props.banners.data,
    columns: columns.value,
    getRowId,
    totalCount: props.banners.meta.total,
    initialPage: props.banners.meta.current_page,
    pageSize: props.banners.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: "Baneriai",
    icon: Icons.BANNER,
    createRoute: route('banners.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
