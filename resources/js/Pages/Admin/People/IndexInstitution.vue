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
        v-if="types.length > 0"
        v-model:value="selectedTypeIds"
        :options="typeOptions"
        @update:value="handleTypeFilterChange"
        multiple
      >
        {{ $tChoice('forms.fields.type', 2) }}
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
      <Button v-if="canImport" variant="outline" size="sm" class="gap-1.5" @click="showImportDialog = true">
        <UploadIcon class="h-4 w-4" />
        {{ $t('Import') }}
      </Button>
    </template>

    <template #tableActions v-if="selectedRows.length > 0">
      <div class="flex items-center gap-2">
        <Badge variant="secondary" class="mr-2">{{ selectedRows.length }} {{ $t('selected') }}</Badge>
        <Button v-if="canExport" size="sm" variant="outline" @click="exportSelectedRows('xlsx')" class="gap-1.5">
          <FileSpreadsheetIcon class="h-4 w-4" />
          {{ $t('Export XLSX') }}
        </Button>
        <Button v-if="canExport" size="sm" variant="outline" @click="exportSelectedRows('csv')" class="gap-1.5">
          <FileTextIcon class="h-4 w-4" />
          {{ $t('Export CSV') }}
        </Button>
        <!-- <Button v-if="canDelete && selectedRows.length > 0" size="sm" variant="destructive" class="gap-1.5" @click="confirmBulkDelete">
          <TrashIcon class="h-4 w-4" />
          {{ $t('Delete') }}
        </Button> -->
      </div>
    </template>
    
    <!-- <template #emptyDescription>
      {{ $t('No institutions found. You can add a new institution using the button above.') }}
    </template> -->
  </IndexTablePage>
  
  <!-- Import Dialog -->
  <Dialog v-model:open="showImportDialog">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('Import Institutions') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Upload a CSV or XLSX file with institution data.') }}
        </DialogDescription>
      </DialogHeader>
      <div class="grid gap-4 py-4">
        <div class="grid gap-2">
          <Label>{{ $t('File') }}</Label>
          <Input type="file" accept=".csv,.xlsx" @change="handleFileChange" />
        </div>
        <div class="flex items-center gap-2">
          <Checkbox id="headerRow" v-model:checked="importOptions.headerRow" />
          <Label for="headerRow">{{ $t('File contains header row') }}</Label>
        </div>
      </div>
      <DialogFooter>
        <Button variant="outline" @click="showImportDialog = false">{{ $t('Cancel') }}</Button>
        <Button 
          :disabled="!importFile || isImporting" 
          @click="importData"
        >
          <Loader2Icon v-if="isImporting" class="mr-2 h-4 w-4 animate-spin" />
          {{ $t('Import') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
  
  <!-- Delete Confirmation Dialog -->
  <!-- <AlertDialog v-model:open="showDeleteDialog">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ $t('Are you sure?') }}</AlertDialogTitle>
        <AlertDialogDescription>
          {{ $t('This action cannot be undone. This will permanently delete the selected institutions.') }}
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>{{ $t('Cancel') }}</AlertDialogCancel>
        <AlertDialogAction @click="confirmDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">{{ $t('Delete') }}</AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog> -->
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';
import { ref, computed, watch, capitalize } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { 
  FileSpreadsheetIcon, 
  FileTextIcon,
  BuildingIcon,
  UploadIcon,
  Loader2Icon,
  TrashIcon
} from 'lucide-vue-next';
import { toast } from "vue-sonner";

import { formatStaticTime } from "@/Utils/IntlTime";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Button } from "@/Components/ui/button"; 
import { Badge } from "@/Components/ui/badge";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { 
  createTitleColumn,
  createTenantColumn,
  createTagsColumn
} from '@/Utils/DataTableColumns';
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogDescription, 
  DialogFooter 
} from "@/Components/ui/dialog";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Checkbox } from "@/Components/ui/checkbox";
import { 
  type IndexTablePageProps,
  type TableConfig,
  type PaginationConfig,
  type UIConfig,
  type FilteringConfig,
  type RowSelectionConfig
} from "@/Types/TableConfigTypes";

const props = defineProps<{
  data: App.Entities.Institution[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  types: App.Entities.Type[];
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
}>();

// Component constants
const modelName = 'institutions';
const entityName = 'institution';

// Component refs
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.institution || false);
const canExport = computed(() => usePage().props.auth?.can?.export?.institution || false);
const canImport = computed(() => usePage().props.auth?.can?.import?.institution || false);
const canDelete = computed(() => usePage().props.auth?.can?.delete?.institution || false);

// Filter states
const selectedTypeIds = ref<number[]>(props.filters?.['types.id'] || []);
const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);

// Row selection
const selectedRows = ref([]);

// Import dialog state
const showImportDialog = ref(false);
const importFile = ref<File | null>(null);
const isImporting = ref(false);
const importOptions = ref({
  headerRow: true
});

// Delete dialog state
const showDeleteDialog = ref(false);
const itemsToDelete = ref([]);

// Filter options computed values
const typeOptions = computed(() => {
  return props.types.map((type) => ({
    label: $t(type.title),
    value: type.id,
  }));
});

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map((tenant) => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.Institution) => {
  return `institution-${row.id}`;
};

// Table columns
const columns = computed<ColumnDef<App.Entities.Institution, any>[]>(() => [
  createTitleColumn<App.Entities.Institution>({
    accessorKey: "name",
    routeName: "institutions.edit",
    width: 300,
    cell: ({ row }) => {
      const name = row.getValue("name");
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <div class="max-w-[290px] truncate">
                <a href={route("institutions.edit", { id: row.original.id })} 
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
    accessorKey: "alias",
    header: () => '',
    cell: ({ row }) => {
      const alias = row.original.alias;
      if (!alias) return null;
      
      return (
        <PreviewModelButton
          publicRoute="contacts.alias"
          routeProps={{
            institution: alias,
            lang: "lt",
            subdomain: "www",
          }}
        />
      );
    },
    size: 60,
    enableSorting: false,
  },
  createTenantColumn({
    cell: ({ row }) => {
      const tenant = row.original.tenant;
      return tenant ? (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="flex items-center gap-1">
                {tenant.logo_url && <img src={tenant.logo_url} alt={tenant.shortname} class="h-4 w-4 rounded-full" />}
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
  createTagsColumn("types", {
    title: $tChoice("forms.fields.type", 2),
    labelKey: "title",
    cell: ({ row }) => {
      const types = row.original.types || [];
      return (
        <div class="flex flex-wrap gap-1">
          {types.map((type) => (
            <Badge variant="outline" key={type.id}>
              {$t(type.title)}
            </Badge>
          ))}
        </div>
      );
    },
    width: 200
  }),
  {
    id: "meetings",
    header: () => $t("Meetings"),
    cell: ({ row }) => {
      const meetings = row.original.meetings || [];
      if (meetings.length === 0) return null;
      
      return (
        <div class="flex flex-wrap items-center gap-2">
          {meetings.slice(0, 3).map((meeting) => (
            <a 
              key={meeting.id} 
              class="hover:underline text-xs px-2 py-1 rounded-md bg-muted" 
              href={route("meetings.show", meeting.id)}
            >
              {formatStaticTime(meeting.start_time)}
            </a>
          ))}
          {meetings.length > 3 && <span class="text-xs text-muted-foreground">+{meetings.length - 3} more</span>}
        </div>
      );
    },
    size: 250,
    enableSorting: false,
  },
  createStandardActionsColumn<App.Entities.Institution>("institutions", {
    canView: true,
    canEdit: true,
    canDelete: true,
    canRestore: true
  })
]);

// Consolidated table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Institution>>(() => {
  // Core table configuration
  const tableConfig: TableConfig<App.Entities.Institution> = {
    modelName,
    entityName,
    data: props.data,
    columns: columns.value,
    getRowId
  };
  
  // Pagination configuration
  const paginationConfig: PaginationConfig = {
    totalCount: props.meta.total,
    initialPage: props.meta.current_page,
    pageSize: props.meta.per_page
  };
  
  // UI configuration
  const uiConfig: UIConfig = {
    headerTitle: "Institutions",
    headerDescription: $t('Manage institution records and their types'),
    icon: BuildingIcon,
    createRoute: canCreate.value ? route('institutions.create') : undefined,
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
    enableRowSelection: canExport.value,
    enableRowSelectionColumn: canExport.value
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
const handleTypeFilterChange = (typeIds: number[]) => {
  selectedTypeIds.value = typeIds;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('types.id', typeIds);
  }
};

const handleTenantFilterChange = (tenantIds: number[]) => {
  selectedTenantIds.value = tenantIds;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('tenant.id', tenantIds);
  }
};

// Row selection handler
const handleRowSelectionChange = (selection) => {
  // Get the actual row objects from the selection state
  selectedRows.value = indexTablePageRef.value?.getSelectedRows() || [];
};

// Export selected rows
const exportSelectedRows = (format: 'xlsx' | 'csv') => {
  const selectedIds = selectedRows.value.map(row => row.original.id);
  
  if (selectedIds.length > 0) {
    const baseUrl = route('institutions.export');
    const queryString = `format=${format}&ids=${selectedIds.join(',')}`;
    window.open(`${baseUrl}?${queryString}`, '_blank');
    
    toast.success(
      $t('Export started'), 
      { description: $t('Your export will be ready shortly.') }
    );
  } else {
    toast.warning(
      $t('No rows selected'), 
      { description: $t('Please select at least one row to export.') }
    );
  }
};

// Import file handlers
const handleFileChange = (event) => {
  const files = event.target.files;
  if (files && files.length > 0) {
    importFile.value = files[0];
  }
};

const importData = () => {
  if (!importFile.value) return;
  
  const formData = new FormData();
  formData.append('file', importFile.value);
  formData.append('has_header', importOptions.value.headerRow ? '1' : '0');
  
  isImporting.value = true;
  
  router.post(route('institutions.import'), formData, {
    onSuccess: () => {
      toast.success(
        $t('Import successful'), 
        { description: $t('The institutions have been imported.') }
      );
      showImportDialog.value = false;
      isImporting.value = false;
      importFile.value = null;
      
      // Refresh the table data
      indexTablePageRef.value?.reloadData();
    },
    onError: (errors) => {
      toast.error(
        $t('Import failed'), 
        { description: Object.values(errors).flat()[0] }
      );
      isImporting.value = false;
    }
  });
};

// Bulk delete handlers
// const confirmBulkDelete = () => {
//   itemsToDelete.value = selectedRows.value.map(row => row.original.id);
//   showDeleteDialog.value = true;
// };

const confirmDelete = () => {
  if (itemsToDelete.value.length === 0) return;
  
  router.delete(route('institutions.bulk-delete'), {
    data: { ids: itemsToDelete.value },
    onSuccess: () => {
      toast.success(
        $t('Delete successful'), 
        { 
          description: $t('The selected institutions have been deleted.') 
        }
      );
      showDeleteDialog.value = false;
      selectedRows.value = [];
      
      // Refresh the table data
      indexTablePageRef.value?.reloadData();
    },
    onError: (errors) => {
      toast.error(
        $t('Delete failed'), 
        { description: Object.values(errors).flat()[0] }
      );
    }
  });
};

// Event handler for data loaded
const onDataLoaded = (data) => {
  // Additional handling after data is loaded if needed
};

// Event handler for sorting changes from IndexTablePage
const handleSortingChange = (sorting) => {
  // Additional handling for sorting changes if needed
};

// Event handler for page changes from IndexTablePage
const handlePageChange = (page) => {
  // Additional handling for page changes if needed
};

// Event handler for filter changes from IndexTablePage
const handleFilterChange = (filterKey, value) => {
  // Update local filter references if needed
  if (filterKey === 'types.id') {
    selectedTypeIds.value = value;
  } else if (filterKey === 'tenant.id') {
    selectedTenantIds.value = value;
  }
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters['types.id'] !== undefined) {
      selectedTypeIds.value = newFilters['types.id'];
    }
    if (newFilters['tenant.id'] !== undefined) {
      selectedTenantIds.value = newFilters['tenant.id'];
    }
  }
}, { deep: true });
</script>
