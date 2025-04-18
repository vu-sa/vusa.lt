<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <template #filters>
      <DataTableFilter
        v-if="modelTypeOptions.length > 0"
        v-model:value="selectedModelType"
        :options="modelTypeOptions"
        @update:value="handleModelTypeFilterChange"
      >
        {{ $t("Model Type") }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { computed, ref, watch } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';

import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Badge } from "@/Components/ui/badge";
import { 
  createIdColumn, 
  createTimestampColumn,
  createTextColumn,
  createTitleColumn
} from '@/Utils/DataTableColumns';
import { createStandardActionsColumn } from "@/Composables/useTableActions";
import { 
  type IndexTablePageProps,
  type TableConfig,
  type PaginationConfig,
  type UIConfig,
  type FilteringConfig
} from "@/Types/TableConfigTypes";

const props = defineProps<{
  data: App.Entities.Type[];
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
}>();

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Component constants
const modelName = 'types';
const entityName = 'type';

// Extract unique model types for filtering
const modelTypes = computed(() => {
  const types = new Set<string>();
  props.data.forEach(type => {
    if (type.model_type) {
      types.add(type.model_type);
    }
  });
  return Array.from(types);
});

// Initialize filter states
const selectedModelType = ref<string | null>(props.filters?.['model_type'] || null);

// Filter options
const modelTypeOptions = computed(() => {
  return modelTypes.value.map(type => ({
    label: type,
    value: type,
  }));
});

// Table columns
const columns = computed<ColumnDef<App.Entities.Type, any>[]>(() => [
  createIdColumn(),
  createTitleColumn<App.Entities.Type>({
    accessorKey: "title",
    routeName: "types.edit",
    width: 200
  }),
  createTextColumn("slug", { 
    title: $t("forms.fields.slug"),
    cell: ({ row }) => (
      <Badge variant="outline">{row.getValue("slug")}</Badge>
    )
  }),
  createTextColumn("model_type", {
    cell: ({ row }) => (
      <Badge variant="secondary">{row.getValue("model_type")}</Badge>
    )
  }),
  createTimestampColumn("created_at"),
  createTimestampColumn("updated_at"),
  createStandardActionsColumn<App.Entities.Type>("types", {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: true
  })
]);

// Consolidated table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Type>>(() => {
  // Core table configuration
  const tableConfig: TableConfig<App.Entities.Type> = {
    modelName,
    entityName,
    data: props.data,
    columns: columns.value
  };
  
  // Pagination configuration
  const paginationConfig: PaginationConfig = {
    totalCount: props.meta.total,
    initialPage: props.meta.current_page,
    pageSize: props.meta.per_page
  };
  
  // UI configuration
  const uiConfig: UIConfig = {
    headerTitle: $t("Turinio tipai"),
    icon: Icons.TYPE,
    createRoute: route('types.create'),
    canCreate: true
  };
  
  // Filtering configuration
  const filteringConfig: FilteringConfig = {
    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: true,
    allowToggleDeleted: true
  };
  
  // Return the combined configuration
  return {
    ...tableConfig,
    ...paginationConfig,
    ...uiConfig,
    ...filteringConfig
  };
});

// Event handlers
const handleModelTypeFilterChange = (modelType: string | null) => {
  selectedModelType.value = modelType;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('model_type', modelType);
  }
};

const onDataLoaded = (data) => {
  // Handle any additional logic after data is loaded
  console.log('Types data loaded:', data);
};

const handleSortingChange = (sorting) => {
  // Additional handling for sorting changes if needed
  console.log('Sorting changed:', sorting);
};

const handlePageChange = (page) => {
  // Additional handling for page changes if needed
  console.log('Page changed:', page);
};

const handleFilterChange = (filterKey, value) => {
  // Update local filter references if needed
  if (filterKey === 'model_type') {
    selectedModelType.value = value;
  }
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters['model_type'] !== undefined) {
      selectedModelType.value = newFilters['model_type'];
    }
  }
}, { deep: true });
</script>
