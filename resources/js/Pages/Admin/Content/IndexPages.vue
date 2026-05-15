<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <template #headerActions>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button size="sm" variant="outline">
            Redaguoti padalinio pagr. puslapį
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
          <DropdownMenuItem
            v-for="tenant in tenantOptions"
            :key="tenant.id"
            @click="handleTenantSelect(tenant.id)"
          >
            {{ tenant.shortname }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { router, usePage } from '@inertiajs/vue3';

import { Button } from '@/Components/ui/button';
import { TruncatedText } from '@/Components/ui/data-table/cells';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { PageIcon } from '@/Components/icons';
import {
  createIdColumn,
  createTenantColumn,
  createTimestampColumn,
} from '@/Composables/useDataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  pages: {
    data: App.Entities.Page[];
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

const modelName = 'pages';
const entityName = 'page';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.Page) => {
  return `page-${row.id}`;
};

const tenantOptions = computed(() => {
  return usePage().props.auth?.user.tenants || [];
});

function handleTenantSelect(tenantId: number) {
  router.visit(route('tenants.editMainPage', { tenant: tenantId }));
}

const columns = computed<Array<ColumnDef<App.Entities.Page, any>>>(() => [
  createIdColumn<App.Entities.Page>({ width: 50 }),
  {
    accessorKey: 'title',
    header: () => 'Pavadinimas',
    cell: ({ row }) => h(TruncatedText, {
      text: row.getValue('title') as string,
      lines: 2,
    }),
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
    id: 'other_lang_id',
    header: () => 'Kitos kalbos puslapis',
    cell: ({ row }) => {
      const otherLangId = row.original.other_lang_id;
      if (!otherLangId) return null;
      return h('a', {
        href: route('pages.edit', { id: otherLangId }),
        class: 'hover:underline block truncate',
        title: String(otherLangId),
      }, otherLangId);
    },
    size: 150,
  },
  {
    accessorKey: 'is_active',
    header: () => 'Aktyvus',
    cell: ({ row }) => {
      return row.original.is_active ? '✅' : '❌';
    },
    size: 80,
  },
  createTenantColumn<App.Entities.Page>(),
  createTimestampColumn<App.Entities.Page>('created_at', {
    title: 'Sukurta',
    format: 'yyyy-MM-dd',
    sortDescFirst: true,
  }),
  createStandardActionsColumn<App.Entities.Page>('pages', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Page>>(() => {
  return {
    modelName,
    entityName,
    data: props.pages.data,
    columns: columns.value,
    getRowId,
    totalCount: props.pages.meta.total,
    initialPage: props.pages.meta.current_page,
    pageSize: props.pages.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting ?? [{ id: 'created_at', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: 'Puslapiai',
    icon: PageIcon,
    createRoute: route('pages.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
