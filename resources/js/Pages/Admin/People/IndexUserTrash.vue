<template>
  <!-- @deprecated The trash view only: the live list is IndexUser on CollectionPage.
       Move it onto CollectionPage's trash filter, as reservations and tags do. -->
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
    @update:row-selection="handleRowSelectionChange"
  >
    <template #headerActions>
      <Button v-if="canMerge" variant="outline" class="gap-1.5" @click="enterMergeMode">
        <MergeIcon class="h-4 w-4" />
        {{ $t('Sujungti įrašus') }}
      </Button>
    </template>
    <template #actions>
      <Button v-if="isMergeMode && selectedRows.length > 1" variant="secondary" @click="mergeRecords = selectedRows">
        {{ $t('Sujungti pasirinktus') }}
      </Button>
      <Button v-if="isMergeMode" variant="ghost" @click="leaveMergeMode">
        {{ $t('Cancel') }}
      </Button>
    </template>
  </IndexTablePage>
  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="users"
    :records="mergeRecords"
    :submit-url="route('users.mergeUsers')"
    target-field="kept_user_id"
    source-field="source_user_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
</template>

<script setup lang="ts">
import { h, ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { router, usePage } from '@inertiajs/vue3';
import { MergeIcon } from 'lucide-vue-next';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import { DateCell, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { Badge } from '@/Components/ui/badge';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { UserIcon } from '@/Components/icons';
import { Button } from '@/Components/ui/button';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';

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
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const modelName = 'users';
const entityName = 'user';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);
const selectedRows = ref<MergeRecord[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('users.index', 'merge'));
const isMergeMode = computed(() => new URLSearchParams(usePage().url.split('?')[1] ?? '').get('merge') === '1');

const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.user ?? false);

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
    cell: ({ row }) => {
      const count = Number(row.getValue('duties_count') ?? 0);

      // A member with no duties belongs to no unit, so they are invisible to every
      // other tenant admin until someone assigns them one. Flag them so they get
      // picked up rather than quietly accumulating.
      if (count === 0) {
        return h(Badge, { variant: 'warning' }, () => $t('Be padalinio'));
      }

      return h(TruncatedText, { text: String(count) });
    },
    size: 140,
  },
  createStandardActionsColumn<App.Entities.User>('users', {
    canView: true,
    canEdit: true,
    canDelete: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
    customActions: canMerge.value
      ? [{
          key: 'merge',
          label: $t('Sujungti su…'),
          icon: MergeIcon,
          onSelect: (row) => { mergeRecords.value = [{ id: row.id, label: row.name, context: row.email }]; },
        }]
      : [],
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
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'name', desc: false }],
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: isMergeMode.value,
    enableRowSelectionColumn: isMergeMode.value,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,
    deletedCount: props.deletedCount,

    headerTitle: $t('Nariai'),
    icon: UserIcon,
    createRoute: route('users.create'),
    canCreate: true,
  };
});

const enterMergeMode = () => router.get(route('users.index'), { merge: 1 }, { preserveState: true, preserveScroll: true });
const leaveMergeMode = () => router.get(route('users.index'), {}, { preserveState: true, preserveScroll: true });
const merged = () => {
  mergeRecords.value = [];
  indexTablePageRef.value?.clearRowSelection();
  router.reload({ only: ['users'] });
};

const handleRowSelectionChange = () => {
  selectedRows.value = (indexTablePageRef.value?.getSelectedRows() ?? []).map(row => ({
    id: row.id,
    label: row.name,
    context: row.email,
  }));
};

const onDataLoaded = (data: any) => {};
const handleSortingChange = (sorting: any) => {};
const handlePageChange = (page: any) => {};
const handleFilterChange = (filterKey: any, value: any) => {};
</script>
