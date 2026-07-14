<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @filter-changed="handleFilterChange">
    <template #filters>
      <Button
        v-if="myTenantIds.length > 0"
        variant="outline"
        size="sm"
        :class="{ 'border-primary': isMyTenantFilterActive }"
        @click="toggleMyTenantFilter"
      >
        <Building2 class="mr-1.5 h-4 w-4" />
        {{ $t("Mano padalinio problemos") }}
      </Button>
      <Button
        variant="outline"
        size="sm"
        :class="{ 'border-primary': isMyProblemsFilterActive }"
        @click="toggleMyProblemsFilter"
      >
        <UserIcon class="mr-1.5 h-4 w-4" />
        {{ $t("Mano sukurtos problemos") }}
      </Button>
      <DataTableFilter
        v-model:value="selectedStatuses"
        :options="statusOptions"
        multiple
        @update:value="handleStatusFilterChange"
      >
        {{ capitalize($t("entities.problem.status")) }}
      </DataTableFilter>
      <DataTableFilter
        v-model:value="selectedCategories"
        :options="categoryOptions"
        multiple
        @update:value="handleCategoryFilterChange"
      >
        {{ capitalize($t("entities.problem.categories")) }}
      </DataTableFilter>
      <DataTableFilter
        v-if="tenantOptions.length > 0"
        v-model:value="selectedTenantIds"
        :options="tenantOptions"
        multiple
        searchable
        @update:value="handleTenantFilterChange"
      >
        {{ capitalize($tChoice("entities.tenant.model", 1)) }}
      </DataTableFilter>
      <DataTableFilter
        v-model:value="selectedInstitutions"
        :options="institutionOptions"
        multiple
        searchable
        @update:value="handleInstitutionFilterChange"
      >
        {{ capitalize($tChoice("entities.institution.model", 2)) }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, computed, ref, watch, capitalize } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { usePage } from '@inertiajs/vue3';
import { Building2, UserIcon } from 'lucide-vue-next';

import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { Button } from '@/Components/ui/button';
import { DateCell, TagList, TruncatedBadge, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { createDateColumn } from '@/Composables/useDataTableColumns';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type { IndexTablePageProps } from '@/Types/TableConfigTypes';
import { ProblemIcon } from '@/Components/icons';

const props = defineProps<{
  data: App.Entities.Problem[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  categories: App.Entities.ProblemCategory[];
  institutions: App.Entities.Institution[];
}>();

const modelName = 'problems';
const entityName = 'problem';

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

const canCreate = computed(() => usePage().props.auth?.can?.create?.problem || false);

const selectedStatuses = ref<string[]>(props.filters?.['status'] || []);
const selectedCategories = ref<number[]>(props.filters?.['category'] || []);
const selectedInstitutions = ref<string[]>(props.filters?.['institution'] || []);
const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);
const selectedCreatedBy = ref<string[]>(props.filters?.['created_by'] || []);

const statusOptions = computed(() => [
  { label: $t('Atvira'), value: 'open' },
  { label: $t('Vykdoma'), value: 'in_progress' },
  { label: $t('Išspręsta'), value: 'resolved' },
]);

const categoryOptions = computed(() =>
  props.categories.map(cat => ({ label: cat.name as string, value: cat.id })),
);

const institutionOptions = computed(() =>
  props.institutions.map(inst => ({ label: inst.name as string, value: inst.id })),
);

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map(tenant => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

// --- Quick filters ---
const myTenantIds = computed<number[]>(() =>
  (usePage().props.auth?.user?.tenants ?? []).map(tenant => tenant.id),
);

const isMyTenantFilterActive = computed(() => {
  const selected = selectedTenantIds.value;
  return selected.length > 0
    && selected.length === myTenantIds.value.length
    && myTenantIds.value.every(id => selected.includes(id));
});

const toggleMyTenantFilter = () => {
  const values = isMyTenantFilterActive.value ? [] : [...myTenantIds.value];
  selectedTenantIds.value = values;
  indexTablePageRef.value?.updateFilter('tenant.id', values);
};

const isMyProblemsFilterActive = computed(() => {
  const userId = usePage().props.auth?.user?.id;
  return selectedCreatedBy.value.length === 1 && selectedCreatedBy.value[0] === userId;
});

const toggleMyProblemsFilter = () => {
  const userId = usePage().props.auth?.user?.id;
  const values = isMyProblemsFilterActive.value || !userId ? [] : [String(userId)];
  selectedCreatedBy.value = values;
  indexTablePageRef.value?.updateFilter('created_by', values);
};

const statusVariant = (status: string) =>
  ({ open: 'destructive', in_progress: 'warning', resolved: 'success' }[status] ?? 'secondary');

const statusLabel = (status: string) =>
  ({ open: $t('Atvira'), in_progress: $t('Vykdoma'), resolved: $t('Išspręsta') }[status] ?? status);

const columns = computed(() => [
  {
    accessorKey: 'title',
    header: () => capitalize($t('entities.problem.title')),
    cell: ({ row }) => h(TruncatedLink, {
      href: route('problems.show', row.original.id),
      text: row.original.title,
      lines: 2,
    }),
    enableSorting: true,
    size: 300,
  },
  {
    accessorKey: 'status',
    header: () => capitalize($t('entities.problem.status')),
    cell: ({ row }) => h(TruncatedBadge, {
      text: statusLabel(row.original.status),
      variant: statusVariant(row.original.status),
    }),
    enableSorting: true,
    size: 120,
  },
  createDateColumn<App.Entities.Problem>('occurred_at', {
    title: capitalize($t('entities.problem.occurred_at')),
    width: 120,
    enableSorting: true,
  }),
  createDateColumn<App.Entities.Problem>('resolved_at', {
    title: capitalize($t('entities.problem.resolved_at')),
    width: 120,
  }),
  {
    id: 'responsible_user',
    header: () => capitalize($t('entities.problem.responsible_user')),
    cell: ({ row }) => h(TruncatedText, { text: row.original.responsible_user?.name }),
    size: 160,
  },
  {
    id: 'categories',
    header: () => capitalize($t('entities.problem.categories')),
    cell: ({ row }) => {
      const cats = row.original.categories ?? [];
      if (cats.length === 0) return h(TruncatedText, { text: null });
      return h(TagList, { items: cats, labelKey: 'name', maxVisible: 2 });
    },
    size: 180,
  },
  {
    id: 'tenant',
    header: () => capitalize($tChoice('entities.tenant.model', 1)),
    cell: ({ row }) => h(TruncatedText, { text: row.original.tenant?.shortname }),
    size: 100,
  },
  createStandardActionsColumn<App.Entities.Problem>(modelName, {
    canEdit: true,
    canDelete: true,
    canRestore: true,
  }),
]) as Array<ColumnDef<App.Entities.Problem, any>>;

const tableConfig = computed<IndexTablePageProps<App.Entities.Problem>>(() => ({
  modelName,
  entityName,
  data: props.data,
  columns: columns.value,
  totalCount: props.meta.total,
  initialPage: props.meta.current_page,
  pageSize: props.meta.per_page,
  initialFilters: props.filters,
  initialSorting: props.sorting?.length ? props.sorting : [{ id: 'occurred_at', desc: true }],
  enableFiltering: true,
  enableColumnVisibility: true,
  allowToggleDeleted: false,
  headerTitle: capitalize($tChoice('entities.problem.model', 2)),
  icon: ProblemIcon,
  createRoute: canCreate.value ? route('problems.create') : undefined,
  canCreate: canCreate.value,
}));

const handleStatusFilterChange = (values: string[]) => {
  indexTablePageRef.value?.updateFilter('status', values);
};

const handleCategoryFilterChange = (values: number[]) => {
  indexTablePageRef.value?.updateFilter('category', values);
};

const handleInstitutionFilterChange = (values: string[]) => {
  indexTablePageRef.value?.updateFilter('institution', values);
};

const handleTenantFilterChange = (values: number[]) => {
  indexTablePageRef.value?.updateFilter('tenant.id', values);
};

const handleFilterChange = (filterKey: string, value: any) => {
  if (filterKey === 'status') selectedStatuses.value = value;
  if (filterKey === 'category') selectedCategories.value = value;
  if (filterKey === 'institution') selectedInstitutions.value = value;
  if (filterKey === 'tenant.id') selectedTenantIds.value = value;
  if (filterKey === 'created_by') selectedCreatedBy.value = value;
};

watch(
  () => props.filters,
  (newFilters) => {
    if (!newFilters) return;
    if (newFilters['status'] !== undefined) selectedStatuses.value = newFilters['status'];
    if (newFilters['category'] !== undefined) selectedCategories.value = newFilters['category'];
    if (newFilters['institution'] !== undefined) selectedInstitutions.value = newFilters['institution'];
    if (newFilters['tenant.id'] !== undefined) selectedTenantIds.value = newFilters['tenant.id'];
    if (newFilters['created_by'] !== undefined) selectedCreatedBy.value = newFilters['created_by'];
  },
  { deep: true },
);
</script>
