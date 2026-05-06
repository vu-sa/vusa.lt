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
import { h } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';

import { TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createTenantColumn,
} from '@/Composables/useDataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  quickLinks: {
    data: App.Entities.QuickLink[];
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

const modelName = 'quickLinks';
const entityName = 'quickLink';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.QuickLink) => {
  return `quickLink-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.QuickLink, any>>>(() => [
  {
    accessorKey: 'text',
    header: () => 'Pavadinimas',
    cell: ({ row }) => h(TruncatedText, { text: row.getValue('text') as string, lines: 2 }),
    size: 300,
    enableSorting: true,
  },
  createTenantColumn<App.Entities.QuickLink>(),
  {
    accessorKey: 'lang',
    header: () => 'Kalba',
    cell: ({ row }) => {
      return row.original.lang === 'lt' ? '🇱🇹' : '🇬🇧';
    },
    size: 80,
  },
  {
    accessorKey: 'link',
    header: () => 'Nuoroda',
    cell: ({ row }) => {
      const { link } = row.original;
      if (!link) return null;
      return h(TruncatedLink, {
        href: link,
        text: link,
        external: true,
      });
    },
    size: 200,
  },
  createStandardActionsColumn<App.Entities.QuickLink>('quickLinks', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.QuickLink>>(() => {
  return {
    modelName,
    entityName,
    data: props.quickLinks.data,
    columns: columns.value,
    getRowId,
    totalCount: props.quickLinks.meta.total,
    initialPage: props.quickLinks.meta.current_page,
    pageSize: props.quickLinks.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Greitosios nuorodos',
    icon: Icons.QUICK_LINK,
    createRoute: route('quickLinks.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
