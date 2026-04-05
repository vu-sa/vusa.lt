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
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';

import { formatStaticTime } from '@/Utils/IntlTime';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createIdColumn,
  createTenantColumn,
} from '@/Utils/DataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  news: {
    data: App.Entities.News[];
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

const modelName = 'news';
const entityName = 'news';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.News) => {
  return `news-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.News, any>[]>(() => [
  createIdColumn<App.Entities.News>({ width: 70 }),
  {
    accessorKey: 'title',
    header: () => 'Pavadinimas',
    cell: ({ row }) => {
      return (
        <div class="max-w-[200px] text-wrap">
          {row.getValue('title')}
        </div>
      );
    },
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'lang',
    header: () => 'Kalba',
    cell: ({ row }) => {
      return row.original.lang === 'lt' ? '🇱🇹' : '🇬🇧';
    },
    size: 80,
  },
  {
    id: 'other_language_news',
    header: () => 'Kitos kalbos naujiena',
    cell: ({ row }) => {
      const otherNews = row.original.other_language_news;
      if (!otherNews) return null;
      return (
        <a
          href={route('news.edit', { id: otherNews.id })}
          target="_blank"
          class="hover:underline"
        >
          <div class="max-w-[100px] truncate" title={otherNews.title}>
            {otherNews.title}
          </div>
        </a>
      );
    },
    size: 110,
  },
  createTenantColumn<App.Entities.News>(),
  {
    accessorKey: 'publish_time',
    header: () => 'Paskelbimo data',
    cell: ({ row }) => {
      const publishTime = row.original.publish_time;
      if (!publishTime) return null;
      return formatStaticTime(new Date(publishTime), {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
      });
    },
    size: 150,
    enableSorting: true,
    sortDescFirst: true,
  },
  createStandardActionsColumn<App.Entities.News>('news', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canDuplicate: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.News>>(() => {
  return {
    modelName,
    entityName,
    data: props.news.data,
    columns: columns.value,
    getRowId,
    totalCount: props.news.meta.total,
    initialPage: props.news.meta.current_page,
    pageSize: props.news.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting ?? [{ id: 'publish_time', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Naujienos',
    icon: Icons.NEWS,
    createRoute: route('news.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
