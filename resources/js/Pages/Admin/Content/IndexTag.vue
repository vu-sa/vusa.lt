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
    <template #headerActions>
      <Button variant="outline" as-child class="gap-1.5">
        <Link :href="route('tags.merge')">
          <MergeIcon class="h-4 w-4" />
          {{ $t('Sulieti žymas') }}
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
  MergeIcon
} from 'lucide-vue-next';
import { toast } from "vue-sonner";

import Icons from "@/Types/Icons/regular";
import { Button } from "@/Components/ui/button"; 
import { Badge } from "@/Components/ui/badge";
import { Link } from "@inertiajs/vue3";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { 
  createTitleColumn
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
  tags: {
    data: App.Entities.Tag[];
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

// Component constants
const modelName = 'tags';
const entityName = 'tag';

// Component refs
const indexTablePageRef = ref<any>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.tag || false);
const canExport = computed(() => false); // Export functionality disabled for tags

// Row selection
const selectedRows = ref([]);

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.Tag) => {
  return `tag-${row.id}`;
};

// Table columns
const columns = computed<ColumnDef<App.Entities.Tag, any>[]>(() => [
  createTitleColumn<App.Entities.Tag>({
    accessorKey: "name",
    routeName: "tags.edit",
    width: 300,
    cell: ({ row }) => {
      const name = row.getValue("name");
      const displayName = typeof name === 'object' ? (name.lt || name.en || '-') : name;
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <div class="max-w-[290px] truncate">
                <a href={route("tags.edit", { id: row.original.id })} 
                   class="font-medium hover:underline">
                  {displayName}
                </a>
              </div>
            </TooltipTrigger>
            <TooltipContent side="top" align="start">
              <p>{displayName}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    }
  }),
  {
    accessorKey: "alias",
    header: () => $t("Alias"),
    cell: ({ row }) => {
      const alias = row.original.alias;
      return alias ? (
        <Badge variant="outline">
          {alias}
        </Badge>
      ) : null;
    },
    size: 150,
  },
  {
    accessorKey: "created_at",
    header: () => $t("forms.fields.created_at"),
    cell: ({ row }) => {
      return new Date(row.original.created_at).toLocaleDateString("lt-LT");
    },
    size: 150,
  },
  createStandardActionsColumn<App.Entities.Tag>("tags", {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: false
  })
]);

// Consolidated table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Tag>>(() => {
  // Core table configuration
  const tableConfig: TableConfig<App.Entities.Tag> = {
    modelName,
    entityName,
    data: props.tags.data,
    columns: columns.value,
    getRowId
  };
  
  // Pagination configuration
  const paginationConfig: PaginationConfig = {
    totalCount: props.tags.meta.total,
    initialPage: props.tags.meta.current_page,
    pageSize: props.tags.meta.per_page
  };
  
  // UI configuration
  const uiConfig: UIConfig = {
    headerTitle: "Žymos",
    headerDescription: $t('Tvarkykite turinio žymas ir kategorijas'),
    icon: Icons.TAG,
    createRoute: canCreate.value ? route('tags.create') : undefined,
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

// Row selection handler
const handleRowSelectionChange = (selection: any) => {
  // Get the actual row objects from the selection state
  selectedRows.value = indexTablePageRef.value?.getSelectedRows() || [];
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
  // Additional handling for filter changes if needed
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  // Handle any external filter changes if needed
}, { deep: true });
</script>
