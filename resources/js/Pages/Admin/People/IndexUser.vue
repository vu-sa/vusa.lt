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

import { formatRelativeTime } from '@/Utils/IntlTime';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  users: {
    data: App.Entities.User[];
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

const modelName = 'users';
const entityName = 'user';

const indexTablePageRef = ref<any>(null);

const getRowId = (row: App.Entities.User) => {
  return `user-${row.id}`;
};

const columns = computed<ColumnDef<App.Entities.User, any>[]>(() => [
  {
    accessorKey: 'name',
    header: () => 'Vardas',
    cell: ({ row }) => row.getValue('name'),
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'email',
    header: () => 'El. paštas',
    cell: ({ row }) => {
      const { email } = row.original;
      if (!email) return null;
      return (
        <a href={`mailto:${email}`} class="transition hover:text-vusa-red">
          <div class="max-w-[200px] truncate" title={email}>
            {email}
          </div>
        </a>
      );
    },
    size: 200,
  },
  {
    accessorKey: 'phone',
    header: () => 'Telefonas',
    cell: ({ row }) => {
      const { phone } = row.original;
      if (!phone) return null;
      return (
        <a href={`tel:${phone}`} class="transition hover:text-vusa-red">
          {phone}
        </a>
      );
    },
    size: 150,
  },
  {
    accessorKey: 'last_action',
    header: () => 'Paskutinis prisijungimas',
    cell: ({ row }) => {
      const lastAction = row.original.last_action;
      return (
        <span class={lastAction ? '' : 'text-vusa-red'}>
          {lastAction
            ? formatRelativeTime(new Date(lastAction))
            : 'Niekada'}
        </span>
      );
    },
    size: 200,
  },
  {
    accessorKey: 'duties_count',
    header: () => 'Pareigų skaičius',
    cell: ({ row }) => row.getValue('duties_count'),
    size: 120,
  },
  createStandardActionsColumn<App.Entities.User>('users', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.User>>(() => {
  return {
    modelName,
    entityName,
    data: props.users.data,
    columns: columns.value,
    getRowId,
    totalCount: props.users.meta.total,
    initialPage: props.users.meta.current_page,
    pageSize: props.users.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,

    headerTitle: $t('Nariai'),
    icon: Icons.USER,
    createRoute: route('users.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
