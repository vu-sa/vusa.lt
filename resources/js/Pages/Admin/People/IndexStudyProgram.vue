<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
    @update:rowSelection="handleRowSelectionChange"
  >
    <template #filters>
      <DataTableFilter
        v-model:value="selectedDegrees"
        :options="degreeOptions"
        @update:value="handleDegreeFilterChange"
        multiple
      >
        {{ $t('Degree') }}
      </DataTableFilter>
      
      <DataTableFilter
        v-if="tenantOptions.length > 0"
        v-model:value="selectedTenantIds"
        :options="tenantOptions"
        @update:value="handleTenantFilterChange"
        multiple
      >
        {{ capitalize($tChoice('entities.tenant.model', 1)) }}
      </DataTableFilter>
    </template>

    <template #headerActions>
      <Button variant="outline" as-child class="gap-1.5">
        <Link :href="route('studyPrograms.merge')">
          <MergeIcon class="h-4 w-4" />
          {{ $t('Sujungti programas') }}
        </Link>
      </Button>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';
import { ref, computed, watch, capitalize } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { 
  MergeIcon, 
  PlusIcon 
} from 'lucide-vue-next';
import { toast } from "vue-sonner";

import Icons from "@/Types/Icons/regular";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Button } from "@/Components/ui/button"; 
import { Badge } from "@/Components/ui/badge";
import { Link } from "@inertiajs/vue3";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { 
  createTitleColumn,
  createTenantColumn
} from '@/Utils/DataTableColumns';
import { 
  type IndexTablePageProps,
  type TableConfig,
  type PaginationConfig,
  type UIConfig,
  type FilteringConfig,
  type RowSelectionConfig
} from "@/Types/TableConfigTypes";

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
}>();

// Component constants
const modelName = 'studyPrograms';
const entityName = 'studyProgram';

// Component refs
const indexTablePageRef = ref<any>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.studyProgram || false);
const canExport = computed(() => false); // Export functionality disabled for study programs

// Filter states
const selectedDegrees = ref<string[]>(props.filters?.['degree'] || []);
const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);

// Row selection
const selectedRows = ref([]);

// Filter options computed values
const degreeOptions = computed(() => props.degreeOptions || []);

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map((tenant) => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.StudyProgram) => {
  return `study-program-${row.id}`;
};

// Table columns
const columns = computed<ColumnDef<App.Entities.StudyProgram, any>[]>(() => [
  createTitleColumn<App.Entities.StudyProgram>({
    accessorKey: "name",
    routeName: "studyPrograms.edit",
    width: 300,
    cell: ({ row }) => {
      const name = row.getValue("name");
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <div class="max-w-[290px] truncate">
                <a href={route("studyPrograms.edit", { id: row.original.id })} 
                   class="font-medium hover:underline">
                  {name}
                </a>
              </div>
            </TooltipTrigger>
            <TooltipContent side="top" align="start">
              <p>{name}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    }
  }),
  {
    accessorKey: "degree",
    header: () => $t("Laipsnis"),
    cell: ({ row }) => {
      const degree = row.original.degree;
      return (
        <Badge variant="outline">
          {degree}
        </Badge>
      );
    },
    size: 150,
  },
  createTenantColumn({
    cell: ({ row }) => {
      const tenant = row.original.tenant;
      return tenant ? (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="flex items-center gap-1">
                <span class="max-w-[150px] truncate">{$t(tenant.shortname)}</span>
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" align="start">
              <p>{$t(tenant.shortname)}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      ) : "";
    },
  }),
  createStandardActionsColumn<App.Entities.StudyProgram>("studyPrograms", {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: false
  })
]);

// Consolidated table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.StudyProgram>>(() => {
  // Core table configuration
  const tableConfig: TableConfig<App.Entities.StudyProgram> = {
    modelName,
    entityName,
    data: props.studyPrograms.data,
    columns: columns.value,
    getRowId
  };
  
  // Pagination configuration
  const paginationConfig: PaginationConfig = {
    totalCount: props.studyPrograms.meta.total,
    initialPage: props.studyPrograms.meta.current_page,
    pageSize: props.studyPrograms.meta.per_page
  };
  
  // UI configuration
  const uiConfig: UIConfig = {
    headerTitle: "Studijų programos",
    headerDescription: $t('Manage study programs and their degrees'),
    icon: Icons.STUDY_PROGRAM,
    createRoute: canCreate.value ? route('studyPrograms.create') : undefined,
    canCreate: canCreate.value
  };
  
  // Filtering configuration
  const filteringConfig: FilteringConfig = {
    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: true
  };
  
  // Row selection configuration
  const rowSelectionConfig: RowSelectionConfig = {
    enableRowSelection: false,
    enableRowSelectionColumn: false
  };
  
  // Return the combined configuration
  return {
    ...tableConfig,
    ...paginationConfig,
    ...uiConfig,
    ...filteringConfig,
    ...rowSelectionConfig
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
  // Get the actual row objects from the selection state
  selectedRows.value = indexTablePageRef.value?.getSelectedRows() || [];
};

// Export selected rows (placeholder for future implementation)
const exportSelectedRows = (format: 'xlsx' | 'csv') => {
  // This feature is not currently implemented for study programs
  toast.info($t('Export functionality is not available for study programs'));
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
  } else if (filterKey === 'tenant.id') {
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
