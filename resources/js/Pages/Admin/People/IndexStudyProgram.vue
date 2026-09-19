<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange" @page-changed="handlePageChange" @filter-changed="handleFilterChange"
    @update:row-selection="handleRowSelectionChange">
    <template #filters>
      <DataTableFilter v-model:value="selectedDegrees" :options="degreeOptions" multiple
        @update:value="handleDegreeFilterChange">
        {{ $t('Degree') }}
      </DataTableFilter>

      <DataTableFilter v-if="tenantOptions.length > 0" v-model:value="selectedTenantIds" :options="tenantOptions"
        multiple @update:value="handleTenantFilterChange">
        {{ capitalize($tChoice('entities.tenant.model', 1)) }}
      </DataTableFilter>
    </template>

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
    type="study-programs"
    :records="mergeRecords"
    :submit-url="route('studyPrograms.mergeStudyPrograms')"
    target-field="target_study_program_id"
    source-field="source_study_program_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
</template>

<script setup lang="ts">
import { h, ref, computed, watch, capitalize } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { router, usePage } from '@inertiajs/vue3';
import {
  MergeIcon,
  PlusIcon,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { Button } from '@/Components/ui/button';
import { TruncatedBadge } from '@/Components/ui/data-table/cells';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { StudyProgramIcon } from '@/Components/icons';
import {
  createTitleColumn,
  createTenantColumn,
} from '@/Composables/useDataTableColumns';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';

const props = defineProps<{
  studyPrograms: {
    data: App.Entities.StudyProgram[];
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
  degreeOptions: Array<{ label: string; value: string }>;
  showDeleted?: boolean;
  deletedCount?: number;
}>();

// Component constants
const modelName = 'studyPrograms';
const entityName = 'studyProgram';

// Component refs
const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.studyProgram || false);
const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.studyProgram ?? false);

// Filter states
const selectedDegrees = ref<string[]>(props.filters?.['degree'] || []);
const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);

// Row selection
const selectedRows = ref<MergeRecord[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('studyPrograms.index', 'merge'));
const isMergeMode = computed(() => new URLSearchParams(usePage().url.split('?')[1] ?? '').get('merge') === '1');

// Filter options computed values
const degreeOptions = computed(() => props.degreeOptions || []);

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map(tenant => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.StudyProgram) => {
  return `study-program-${row.id}`;
};

// Table columns
const columns = computed<Array<ColumnDef<App.Entities.StudyProgram, any>>>(() => [
  createTitleColumn<App.Entities.StudyProgram>({
    accessorKey: 'name',
    routeName: 'studyPrograms.edit',
    width: 300,
  }),
  {
    accessorKey: 'degree',
    header: () => $t('Laipsnis'),
    cell: ({ row }) => {
      const { degree } = row.original;
      return h(TruncatedBadge, { text: degree, variant: 'outline' });
    },
    size: 150,
  },
  createTenantColumn({
    enableSorting: false,
  }),
  createStandardActionsColumn<App.Entities.StudyProgram>('studyPrograms', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
    customActions: canMerge.value
      ? [{
          key: 'merge',
          label: $t('Sujungti su…'),
          icon: MergeIcon,
          onSelect: (row) => { mergeRecords.value = [{ id: row.id, label: row.name, context: row.tenant?.shortname }]; },
        }]
      : [],
  }),
]);

// Simplified table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.StudyProgram>>(() => {
  return {
    // Essential table configuration
    modelName,
    entityName,
    data: props.studyPrograms.data,
    columns: columns.value,
    getRowId,
    totalCount: props.studyPrograms.meta.total,
    initialPage: props.studyPrograms.meta.current_page,
    pageSize: props.studyPrograms.meta.per_page,

    // Advanced features
    initialFilters: props.filters,
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'name', desc: false }],
    enableFiltering: true,
    enableColumnVisibility: true,
    enableRowSelection: isMergeMode.value,
    enableRowSelectionColumn: isMergeMode.value,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,
    deletedCount: props.deletedCount,

    // Page layout
    headerTitle: 'Studijų programos',
    icon: StudyProgramIcon,
    createRoute: canCreate.value ? route('studyPrograms.create') : undefined,
    canCreate: canCreate.value,
  };
});

// Event handlers
const handleDegreeFilterChange = (degrees: string[]) => {
  selectedDegrees.value = degrees;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('degree', degrees);
  }
};

const handleTenantFilterChange = (tenantIds: number[]) => {
  selectedTenantIds.value = tenantIds;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('tenant.id', tenantIds);
  }
};

// Row selection handler
const handleRowSelectionChange = (selection: any) => {
  selectedRows.value = (indexTablePageRef.value?.getSelectedRows() ?? []).map(row => ({
    id: row.id,
    label: row.name,
    context: row.tenant?.shortname,
  }));
};

const enterMergeMode = () => router.get(route('studyPrograms.index'), { merge: 1 }, { preserveState: true, preserveScroll: true });
const leaveMergeMode = () => router.get(route('studyPrograms.index'), {}, { preserveState: true, preserveScroll: true });
const merged = () => {
  mergeRecords.value = [];
  indexTablePageRef.value?.clearRowSelection();
  router.reload({ only: ['studyPrograms'] });
};

// Event handler for data loaded
const onDataLoaded = (data: any) => {
  // Additional handling after data is loaded if needed
};

// Event handler for sorting changes from IndexTablePage
const handleSortingChange = (sorting: any) => {
  // Additional handling for sorting changes if needed
};

// Event handler for page changes from IndexTablePage
const handlePageChange = (page: any) => {
  // Additional handling for page changes if needed
};

// Event handler for filter changes from IndexTablePage
const handleFilterChange = (filterKey: any, value: any) => {
  // Update local filter references if needed
  if (filterKey === 'degree') {
    selectedDegrees.value = value;
  }
  else if (filterKey === 'tenant.id') {
    selectedTenantIds.value = value;
  }
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters['degree'] !== undefined) {
      selectedDegrees.value = newFilters['degree'];
    }
    if (newFilters['tenant.id'] !== undefined) {
      selectedTenantIds.value = newFilters['tenant.id'];
    }
  }
}, { deep: true });
</script>
