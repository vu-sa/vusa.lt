<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @filter-changed="handleFilterChange">
    <template #filters>
      <DataTableFilter
        v-model:value="selectedStatuses"
        :options="statusFilterOptions"
        multiple
        @update:value="handleStatusFilterChange"
      >
        {{ $t('Būsena') }}
      </DataTableFilter>

      <DataTableFilter
        v-model:value="selectedTypes"
        :options="typeFilterOptions"
        multiple
        @update:value="handleTypeFilterChange"
      >
        {{ $t('Tipas') }}
      </DataTableFilter>

      <DataTableFilter
        v-model:value="selectedAreas"
        :options="areaFilterOptions"
        multiple
        @update:value="handleAreaFilterChange"
      >
        {{ $t('Sritis') }}
      </DataTableFilter>

      <DataTableFilter
        v-if="assigneeFilterOptions.length > 0"
        v-model:value="selectedAssignees"
        :options="assigneeFilterOptions"
        multiple
        searchable
        @update:value="handleAssigneeFilterChange"
      >
        {{ $t('Priskirta') }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';

import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { TruncatedBadge, TruncatedLink } from '@/Components/ui/data-table/cells';
import { createDateColumn } from '@/Composables/useDataTableColumns';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import type { SupportRequestItem } from '@/Types/supportRequests';
import type { BadgeVariants } from '@/Components/ui/badge';

const props = defineProps<{
  data: SupportRequestItem[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  filters?: Record<string, unknown>;
  sorting?: { id: string; desc: boolean }[];
  types: Array<{ id: number; name: string | Record<string, string> }>;
  areas: Array<{ id: number; name: string | Record<string, string> }>;
  assignees: Array<{ id: string; name: string }>;
  statusOptions: Array<{ value: string; label: string; badgeVariant: string }>;
  showDeleted?: boolean;
  deletedCount?: number;
}>();

const modelName = 'supportRequests';
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

const page = usePage();
const currentLocale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale || 'lt');

const selectedStatuses = ref<string[]>((props.filters?.status as string[]) || []);
const selectedTypes = ref<number[]>((props.filters?.type as number[]) || []);
const selectedAreas = ref<number[]>((props.filters?.area as number[]) || []);
const selectedAssignees = ref<string[]>((props.filters?.assigned_to as string[]) || []);

const statusFilterOptions = computed(() =>
  props.statusOptions.map(s => ({ label: s.label, value: s.value })),
);

const typeFilterOptions = computed(() =>
  props.types.map(t => ({ label: getTranslatedValue(t.name, currentLocale.value), value: t.id })),
);

const areaFilterOptions = computed(() =>
  props.areas.map(a => ({ label: getTranslatedValue(a.name, currentLocale.value), value: a.id })),
);

const assigneeFilterOptions = computed(() =>
  props.assignees.map(u => ({ label: u.name, value: u.id })),
);

const statusVariant = (status: string): BadgeVariants['variant'] => {
  const map: Record<string, BadgeVariants['variant']> = {
    new: 'sky',
    reviewing: 'warning',
    planned: 'zinc',
    in_progress: 'amber',
    done: 'success',
    declined: 'destructive',
  };
  return map[status] ?? 'secondary';
};

const statusLabel = (status: string) => {
  const opt = props.statusOptions.find(s => s.value === status);
  return opt ? opt.label : status;
};

const columns = computed<ColumnDef<SupportRequestItem>[]>(() => [
  {
    accessorKey: 'title',
    header: () => $t('Pavadinimas'),
    cell: ({ row }) => h(TruncatedLink, {
      href: route('supportRequests.show', row.original.id),
      text: row.original.title,
      lines: 2,
    }),
    enableSorting: true,
    size: 280,
  },
  {
    id: 'type',
    header: () => $t('Tipas'),
    cell: ({ row }) => getTranslatedValue(row.original.type?.name, currentLocale.value, '—'),
    size: 130,
  },
  {
    id: 'area',
    header: () => $t('Sritis'),
    cell: ({ row }) => getTranslatedValue(row.original.area?.name, currentLocale.value, '—'),
    size: 140,
  },
  {
    accessorKey: 'status',
    header: () => $t('Būsena'),
    cell: ({ row }) => h(TruncatedBadge, {
      text: statusLabel(String(row.original.status)),
      variant: statusVariant(String(row.original.status)),
    }),
    enableSorting: true,
    size: 120,
  },
  {
    id: 'creator',
    header: () => $t('Pateikė'),
    cell: ({ row }) => row.original.creator?.name ?? row.original.reporter_name ?? $t('Svečias'),
    size: 150,
  },
  {
    id: 'assigned_to',
    header: () => $t('Priskirta'),
    cell: ({ row }) => row.original.assignedTo?.name ?? '—',
    size: 150,
  },
  createDateColumn<SupportRequestItem>('created_at', {
    title: $t('Sukurta'),
    width: 120,
    enableSorting: true,
  }),
  createStandardActionsColumn(modelName, {
    canDelete: true,
    confirmDelete: true,
  }),
]);

const tableConfig = computed<IndexTablePageProps<SupportRequestItem>>(() => ({
  modelName,
  entityName: 'supportRequest',
  title: $t('vusa.lt pagalba'),
  data: props.data,
  columns: columns.value,
  pagination: props.meta,
  filters: props.filters,
  sorting: props.sorting,
  showDeleted: props.showDeleted,
  deletedCount: props.deletedCount,
  canCreate: false,
  createUrl: route('mySupportRequests.create'),
}));

function handleFilterChange(newFilters: Record<string, unknown>) {
  selectedStatuses.value = (newFilters.status as string[]) || [];
  selectedTypes.value = (newFilters.type as number[]) || [];
  selectedAreas.value = (newFilters.area as number[]) || [];
  selectedAssignees.value = (newFilters.assigned_to as string[]) || [];
}

function handleStatusFilterChange(values: string[]) {
  selectedStatuses.value = values;
  indexTablePageRef.value?.updateFilter('status', values);
}

function handleTypeFilterChange(values: number[]) {
  selectedTypes.value = values;
  indexTablePageRef.value?.updateFilter('type', values);
}

function handleAreaFilterChange(values: number[]) {
  selectedAreas.value = values;
  indexTablePageRef.value?.updateFilter('area', values);
}

function handleAssigneeFilterChange(values: string[]) {
  selectedAssignees.value = values;
  indexTablePageRef.value?.updateFilter('assigned_to', values);
}
</script>
