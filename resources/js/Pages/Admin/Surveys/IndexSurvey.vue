<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
  />
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { ClipboardList } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { IndexTablePageInstance, IndexTablePageProps } from '@/Types/TableConfigTypes';
import { capitalize } from '@/Utils/String';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { createTextColumn } from '@/Composables/useDataTableColumns';
import { Badge } from '@/Components/ui/badge';

type StatusOption = { value: string; label: string };

const props = defineProps<{
  surveys: {
    data: App.Entities.Survey[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  statusOptions: StatusOption[];
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const modelName = 'surveys';
const entityName = 'survey';

const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.survey ?? false);

const getRowId = (row: App.Entities.Survey) => `survey-${row.id}`;

const statusLabels = computed(
  () => Object.fromEntries(props.statusOptions.map(option => [option.value, option.label])),
);

/** Only a running survey is worth highlighting; everything else is in-progress paperwork. */
const statusVariant = (status: string) =>
  status === 'active' || status === 'approved'
    ? 'default'
    : status === 'rejected'
      ? 'destructive'
      : 'secondary';

const columns = computed(() => [
  createTextColumn<App.Entities.Survey>('name', {
    title: $t('surveys.fields.name'),
    width: 320,
  }),
  createTextColumn<App.Entities.Survey>('tenant', {
    title: $t('surveys.fields.tenant'),
    width: 120,
    enableSorting: false,
  }),
  {
    accessorKey: 'status',
    header: () => $t('surveys.fields.status'),
    cell: ({ row }) => h(
      Badge,
      { variant: statusVariant(row.getValue('status')) },
      () => statusLabels.value[row.getValue('status') as string] ?? row.getValue('status'),
    ),
    size: 150,
  },
  {
    accessorKey: 'question_count',
    header: () => $t('surveys.sections.questions'),
    enableSorting: false,
    size: 100,
  },
  createStandardActionsColumn<App.Entities.Survey>('surveys', {
    canView: true,
    canEdit: true,
    canDelete: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
  }),
]);

const tableConfig = computed<IndexTablePageProps<App.Entities.Survey>>(() => ({
  modelName,
  entityName,
  data: props.surveys.data,
  columns: columns.value,
  getRowId,
  totalCount: props.surveys.meta.total,
  initialPage: props.surveys.meta.current_page,
  pageSize: props.surveys.meta.per_page,

  initialFilters: props.filters,
  initialSorting: props.sorting?.length ? props.sorting : [{ id: 'created_at', desc: true }],
  enableFiltering: true,
  enableColumnVisibility: false,
  enableRowSelection: false,
  allowToggleDeleted: true,
  showDeleted: props.showDeleted,
  deletedCount: props.deletedCount,

  headerTitle: capitalize($tChoice('entities.survey.model', 2)),
  icon: ClipboardList,
  createRoute: route('surveys.create'),
  canCreate: true,
}));
</script>
