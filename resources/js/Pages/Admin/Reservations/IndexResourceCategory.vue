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
import { h, ref, computed, capitalize } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { Icon } from '@iconify/vue';

import { resolveTranslatable } from '@/Composables/useDataTableColumns';
import { TruncatedText } from '@/Components/ui/data-table/cells';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import type { IndexTablePageInstance } from '@/Types/TableConfigTypes';
import { CategoryIcon } from '@/Components/icons';

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

const modelName = 'resourceCategories';
const entityName = 'resource_category';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const getRowId = (row: App.Entities.ResourceCategory) => {
  return `resource-category-${row.id}`;
};

const columns = computed<Array<ColumnDef<App.Entities.ResourceCategory, any>>>(() => [
  {
    accessorKey: 'name',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => h(TruncatedText, { text: resolveTranslatable(row.getValue('name')) }),
    size: 300,
  },
  {
    accessorKey: 'icon',
    header: () => $t('Ikona'),
    cell: ({ row }) => {
      const { icon } = row.original;
      if (!icon) {
        return h('span', { class: 'text-muted-foreground' }, '-');
      }

      return h('div', { class: 'flex items-center gap-2' }, [
        h(Icon, { icon: `fluent:${icon}` }),
        h('span', {}, icon),
      ]);
    },
    size: 200,
  },
  createStandardActionsColumn<App.Entities.ResourceCategory>('resourceCategories', {
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
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'name', desc: false }],
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: false,

  headerTitle: capitalize($tChoice('entities.resource_category.model', 2)),
  icon: CategoryIcon,
  createRoute: route('resourceCategories.create'),
  canCreate: true,
}));

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
