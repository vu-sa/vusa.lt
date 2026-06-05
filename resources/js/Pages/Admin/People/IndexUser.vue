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
import { h, ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import type { IndexTablePageInstance } from '@/Types/TableConfigTypes';

import { DateCell, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { UserIcon } from '@/Components/icons';
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

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const getRowId = (row: App.Entities.User) => {
  return `user-${row.id}`;
};

const columns = computed(() => [
  {
    accessorKey: 'name',
    header: () => $t('Vardas'),
    cell: ({ row }) => h(TruncatedText, { text: row.getValue('name') as string }),
    size: 200,
    enableSorting: true,
  },
  {
    accessorKey: 'email',
    header: () => $t('El. paštas'),
    cell: ({ row }) => {
      const { email } = row.original;
      if (!email) return null;
      return h(TruncatedLink, {
        href: `mailto:${email}`,
        text: email,
        external: true,
        class: 'transition hover:text-vusa-red',
      });
    },
    size: 200,
  },
  {
    accessorKey: 'phone',
    header: () => $t('Telefonas'),
    cell: ({ row }) => {
      const { phone } = row.original;
      if (!phone) return null;
      return h(TruncatedLink, {
        href: `tel:${phone}`,
        text: phone,
        external: true,
        class: 'transition hover:text-vusa-red',
      });
    },
    size: 150,
  },
  {
    accessorKey: 'last_action',
    header: () => $t('Paskutinis prisijungimas'),
    cell: ({ row }) => {
      const lastAction = row.original.last_action;
      if (!lastAction) return h('span', { class: 'text-vusa-red' }, 'Niekada');
      return h(DateCell, { date: lastAction, mode: 'relative' });
    },
    size: 200,
  },
  {
    accessorKey: 'duties_count',
    header: () => $t('Pareigų skaičius'),
    cell: ({ row }) => h(TruncatedText, { text: String(row.getValue('duties_count')) }),
    size: 120,
  },
  createStandardActionsColumn<App.Entities.User>('users', {
    canView: false,
    canEdit: true,
    canDelete: true,
  }),
]) as Array<ColumnDef<App.Entities.User, any>>;

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
    icon: UserIcon,
    createRoute: route('users.create'),
    canCreate: true,
  };
});

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
