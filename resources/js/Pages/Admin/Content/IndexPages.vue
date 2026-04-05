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

<script setup lang="tsx">
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import Icons from '@/Types/Icons/regular';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createIdColumn,
  createTenantColumn,
  createTimestampColumn,
} from '@/Utils/DataTableColumns';
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

const columns = computed<ColumnDef<App.Entities.Page, any>[]>(() => [
  createIdColumn<App.Entities.Page>({ width: 50 }),
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
    id: 'other_lang_id',
    header: () => 'Kitos kalbos puslapis',
    cell: ({ row }) => {
      const otherLangId = row.original.other_lang_id;
      if (!otherLangId) return null;
      return (
        <a
          href={route('pages.edit', { id: otherLangId })}
          target="_blank"
          class="hover:underline"
        >
          {otherLangId}
        </a>
      );
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
    icon: Icons.PAGE,
    createRoute: route('pages.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
