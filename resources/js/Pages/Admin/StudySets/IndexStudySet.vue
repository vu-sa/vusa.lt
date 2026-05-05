<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange" @page-changed="handlePageChange" @filter-changed="handleFilterChange">
    <template #filters>
      <DataTableFilter v-if="tenantOptions.length > 0" v-model:value="selectedTenantIds" :options="tenantOptions"
        multiple @update:value="handleTenantFilterChange">
        {{ capitalize($tChoice('entities.tenant.model', 1)) }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';
import { ref, computed, watch, capitalize } from "vue";
import { usePage } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Badge } from "@/Components/ui/badge";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import {
  createTitleColumn,
  createTenantColumn,
  createBooleanColumn,
} from '@/Utils/DataTableColumns';
import type { IndexTablePageProps } from "@/Types/TableConfigTypes";

interface StudySetRow {
  id: string;
  name: string;
  tenant?: { id: number; shortname: string };
  order: number;
  is_visible: boolean;
  courses_count: number;
  deleted_at?: string | null;
}

const props = defineProps<{
  studySets: {
    data: StudySetRow[];
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

const modelName = 'studySets';
const entityName = 'studySet';

const indexTablePageRef = ref<any>(null);

const canCreate = computed(() => usePage().props.auth?.can?.create?.studySet || false);

const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map((tenant) => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

const getRowId = (row: StudySetRow) => `study-set-${row.id}`;

const columns = computed<ColumnDef<StudySetRow, any>[]>(() => [
  createTitleColumn<StudySetRow>({
    accessorKey: "name",
    routeName: "studySets.edit",
    width: 300,
  }),
  createTenantColumn({
    enableSorting: false,
  }),
  {
    accessorKey: "courses_count",
    header: () => $t("Dalykų sk."),
    cell: ({ row }) => {
      const count = row.original.courses_count;
      return (
        <Badge variant="outline">
          {count}
        </Badge>
      );
    },
    size: 120,
  },
  {
    accessorKey: "order",
    header: () => $t("Eilės nr."),
    size: 100,
  },
  createBooleanColumn<StudySetRow>("is_visible", {
    header: $t("Matomas"),
    size: 100,
  }),
  createStandardActionsColumn<StudySetRow>("studySets", {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: false,
  }),
]);

const tableConfig = computed<IndexTablePageProps<StudySetRow>>(() => ({
  modelName,
  entityName,
  data: props.studySets.data,
  columns: columns.value,
  getRowId,
  totalCount: props.studySets.meta.total,
  initialPage: props.studySets.meta.current_page,
  pageSize: props.studySets.meta.per_page,

  initialFilters: props.filters,
  initialSorting: props.sorting,
  enableFiltering: true,
  enableColumnVisibility: true,
  enableRowSelection: false,

  headerTitle: "Individualių studijų komplektai",
  icon: Icons.STUDY_SET,
  createRoute: canCreate.value ? route('studySets.create') : undefined,
  canCreate: canCreate.value,
}));

const handleTenantFilterChange = (tenantIds: number[]) => {
  selectedTenantIds.value = tenantIds;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('tenant.id', tenantIds);
  }
};

const onDataLoaded = (_data: any) => {};
const handleSortingChange = (_sorting: any) => {};
const handlePageChange = (_page: any) => {};

const handleFilterChange = (filterKey: any, value: any) => {
  if (filterKey === 'tenant.id') {
    selectedTenantIds.value = value;
  }
};

watch(() => props.filters, (newFilters) => {
  if (newFilters?.['tenant.id'] !== undefined) {
    selectedTenantIds.value = newFilters['tenant.id'];
  }
}, { deep: true });
</script>
