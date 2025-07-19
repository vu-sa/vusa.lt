<template>
  <div>
    <!-- Actual data table -->
    <DataTableProvider ref="dataTableProviderRef" :columns :data :is-server-side="true" :total-items="totalCount"
      :server-pagination :server-sorting="sorting" :page-size :enable-pagination="true" :row-class-name="computedRowClassName" :empty-message
      :enable-filtering="false" :enable-column-visibility :global-filter="searchText" :enable-row-selection
      :enable-multi-row-selection :enable-row-selection-column :row-selection-state="rowSelection" :get-row-id :loading
      @page-change="handlePageChange" @update:sorting="handleSortChange" @update:global-filter="updateSearchText"
      @update:row-selection="handleRowSelectionChange">
      <template #filters>
        <div class="flex gap-2 w-full">
          <Input v-model="searchText" :placeholder="`${$t('Paieška')}...`" class="max-w-sm"
            @keydown.enter="handleSearch" />
          <Button @click="handleSearch">
            {{ $t('Paieška') }}
          </Button>
        </div>
        <div class="flex gap-2">
          <!-- Toggle deleted items -->
          <slot name="filters" />
          <div v-if="allowToggleDeleted" class="flex ml-2 items-center space-x-2">
            <Checkbox id="show-deleted" :model-value="showDeleted" @update:model-value="handleShowDeletedChange" />
            <Label for="show-deleted" class="text-sm font-medium">
              {{ $t('Show deleted') }}
            </Label>
          </div>
        </div>
      </template>

      <template #actions>
        <slot name="actions" />
      </template>

      <template #empty>
        <slot name="empty">
          <!-- Enhanced empty state -->
          <EmptyState :title="emptyMessage || $t(`No ${modelName} found`)"
            :description="$t(`There are no ${modelName} matching your criteria.`)" :icon="EmptyIcon">
            <!-- Create button in empty state -->
            <Link v-if="canCreate && createRoute" :href="createRoute">
            <Button>
              {{ $t('Create') }} {{ singularModelName }}
            </Button>
            </Link>
          </EmptyState>
        </slot>
      </template>
    </DataTableProvider>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { ref, watch, computed, onMounted } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState, RowSelectionState } from '@tanstack/vue-table';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { PlusCircleIcon } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

import DataTableProvider from '../ui/data-table/DataTableProvider.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import EmptyState from '@/Components/Empty/EmptyState.vue';

// Define the props with TypeScript generics support
const props = defineProps<{
  // Inertia integration
  modelName: string,
  reloadOnly?: boolean,

  // Model information
  entityName?: string,
  pluralModelName?: string,
  singularModelName?: string,

  // Data display
  columns: ColumnDef<TData, any>[],
  data: TData[],

  // Pagination
  totalCount: number,
  initialPage?: number,
  pageSize?: number,

  // Options
  rowClassName?: (row: TData) => string,
  emptyMessage?: string,
  emptyIcon?: any,
  enableFiltering?: boolean,
  enableColumnVisibility?: boolean,
  showDeleted?: boolean,

  // Admin features
  allowToggleDeleted?: boolean,
  canCreate?: boolean,
  createRoute?: string,

  // Initial state
  initialSorting?: SortingState,
  initialFilters?: Record<string, unknown>,

  // Row selection
  enableRowSelection?: boolean,
  enableMultiRowSelection?: boolean,
  enableRowSelectionColumn?: boolean,
  initialRowSelection?: RowSelectionState,
  getRowId?: (originalRow: TData, index: number, parent?: any) => string,
}>();

const emit = defineEmits(['dataLoaded', 'update:rowSelection', 'create', 'sorting-changed', 'page-changed', 'filter-changed']);

// Component state
const searchText = ref('');
const pageIndex = ref(props.initialPage ? props.initialPage - 1 : 0);
const sorting = ref<SortingState>(props.initialSorting || []);
const filters = ref<Record<string, unknown>>({
  ...props.initialFilters || {},
  showDeleted: props.showDeleted || false
});
const pageSize = computed(() => props.pageSize || 10);
const loading = ref(false);
const isInternalFilterUpdate = ref(false);

// Row selection state - maintain it outside of table state so it persists
const rowSelection = ref<RowSelectionState>(props.initialRowSelection || {});

// Admin features state
const showDeleted = ref(props.showDeleted || false);

// Computed properties for model names
const EmptyIcon = computed(() => props.emptyIcon || PlusCircleIcon);

// Computed row class name function that combines custom rowClassName with soft-deleted styling
const computedRowClassName = computed(() => {
  return (row: TData) => {
    const baseClasses = props.rowClassName ? props.rowClassName(row) : '';
    
    // Add soft-deleted styling if showDeleted is true and row has deleted_at
    if (showDeleted.value && row && (row as any).deleted_at) {
      return `${baseClasses} opacity-60`.trim();
    }
    
    return baseClasses;
  };
});

const pluralModelName = computed(() => {
  if (props.pluralModelName) return props.pluralModelName;
  return $tChoice(`entities.${props.entityName || props.modelName}.model`, 2);
});

const singularModelName = computed(() => {
  if (props.singularModelName) return props.singularModelName;
  return $tChoice(`entities.${props.entityName || props.modelName}.model`, 1);
});

// Server pagination for UI
const serverPagination = computed(() => ({
  pageIndex: pageIndex.value,
  pageSize: pageSize.value
}));

// Reference to the DataTableProvider
const dataTableProviderRef = ref<InstanceType<typeof DataTableProvider>>();

// Debounce function for search
const debouncedReload = debounce((resetPage = false) => {
  if (resetPage) {
    pageIndex.value = 0;
  }
  reloadData();
}, 300);

// Event handlers
const updateSearchText = (text: string) => {
  searchText.value = text;
  pageIndex.value = 0; // Go back to first page on search change
  debouncedReload(true);
};

const handleSearch = () => {
  pageIndex.value = 0; // Go back to first page on search
  // Explicitly pass the current search text to ensure it's included in the request
  reloadData();
};

const handleSortChange = (newSorting: SortingState) => {
  sorting.value = newSorting;
  emit('sorting-changed', newSorting);
  reloadData(); // Reload data with new sorting
};

const handlePageChange = (newPageIndex: number) => {
  pageIndex.value = newPageIndex;
  emit('page-changed', newPageIndex + 1); // Convert to 1-based for external use
  reloadData(); // Reload data with new page
};

const updateFilter = (key: string, value: any) => {
  filters.value[key] = value;
  pageIndex.value = 0; // Reset to first page when filter changes
  emit('filter-changed', key, value);
  reloadData();
};

const handleRowSelectionChange = (selection: RowSelectionState) => {
  rowSelection.value = selection;
  emit('update:rowSelection', selection);
};

// Encode table state for server requests
const encodeTableState = () => {
  const state: Record<string, any> = {
    page: pageIndex.value + 1, // Convert to 1-based indexing for backend
    per_page: pageSize.value
  };

  // Add sorting if present
  if (sorting.value.length > 0) {
    state.sorting = JSON.stringify(sorting.value);
  }

  // Create filters object without showDeleted (to avoid duplication)
  const filtersToSend = { ...filters.value };
  delete filtersToSend.showDeleted;

  // Add filters if present (excluding showDeleted)
  if (Object.keys(filtersToSend).length > 0) {
    state.filters = JSON.stringify(filtersToSend);
  }

  // Add search text if present
  if (searchText.value) {
    // Using 'search' key to match what the backend expects
    state.search = searchText.value;
  }

  // Add showDeleted parameter (ensure boolean) - only as direct parameter, not in filters
  state.showDeleted = Boolean(showDeleted.value);

  return state;
};

// Load data from server
const reloadData = (page?: number) => {
  if (page !== undefined) {
    pageIndex.value = page;
  }

  loading.value = true;
  const state = encodeTableState();

  const options = {
    data: state,
    preserveScroll: true,
    preserveState: true,
    onSuccess: (response) => {
      const responseData = response.props[props.modelName];
      loading.value = false;

      // Emit data loaded event with context
      emit('dataLoaded', {
        page: pageIndex.value,
        sorting: sorting.value,
        filters: filters.value,
        data: responseData,
        rowSelection: rowSelection.value
      });
    },
    onError: (errors) => {
      console.error('Error loading data:', errors);
      loading.value = false;
    }
  };

  if (props.reloadOnly) {
    router.reload(options);
  } else {
    router.visit(window.location.pathname, options);
  }
};

// Handle show deleted toggle
const handleShowDeletedChange = (checked: boolean) => {
  showDeleted.value = checked;
  filters.value.showDeleted = checked;
  pageIndex.value = 0; // Reset to first page when toggling deleted items
  reloadData();
};

// Watch for props changes
watch(() => props.showDeleted, (newValue) => {
  if (newValue !== undefined && newValue !== showDeleted.value) {
    showDeleted.value = !!newValue;
    filters.value.showDeleted = !!newValue;
    pageIndex.value = 0; // Reset to first page on filter change
    reloadData();
  }
}, { immediate: true });

// Watch for show deleted changes
watch(() => showDeleted.value, (newValue) => {
  if (newValue !== filters.value.showDeleted) {
    filters.value.showDeleted = newValue;
    pageIndex.value = 0;
    reloadData();
  }
});

watch(() => props.initialFilters, (newValue) => {
  // Skip if this is an internal update
  if (isInternalFilterUpdate.value) {
    isInternalFilterUpdate.value = false;
    return;
  }

  if (newValue) {
    // Improved comparison that handles arrays properly
    const hasChanges = Object.entries(newValue).some(([key, value]) => {
      const currentValue = filters.value[key];

      // Handle arrays specifically
      if (Array.isArray(value) && Array.isArray(currentValue)) {
        // Check if arrays have different length
        if (value.length !== currentValue.length) return true;

        // Compare each element
        return value.some((item, i) => item !== currentValue[i]);
      }

      // Handle objects specifically
      if (
        value !== null &&
        typeof value === 'object' &&
        currentValue !== null &&
        typeof currentValue === 'object'
      ) {
        return JSON.stringify(value) !== JSON.stringify(currentValue);
      }

      // Simple comparison for primitives
      return currentValue !== value;
    });

    if (hasChanges) {
      // Update filters keeping existing ones
      filters.value = {
        ...filters.value,
        ...newValue
      };
      reloadData();
    }
  }
}, { deep: true });

watch(() => props.initialSorting, (newValue) => {
  if (newValue && JSON.stringify(newValue) !== JSON.stringify(sorting.value)) {
    sorting.value = newValue;
    reloadData();
  }
}, { deep: true });

// Row selection helper methods
const getSelectedRows = () => {
  return dataTableProviderRef.value?.getSelectedRows() || [];
};

const clearRowSelection = () => {
  if (dataTableProviderRef.value) {
    dataTableProviderRef.value.clearRowSelection();
    rowSelection.value = {};
  }
};

// Expose public methods and computed properties
defineExpose({
  reloadData,
  currentPage: computed(() => pageIndex.value),
  sorting: computed(() => sorting.value),
  filters: computed(() => filters.value),
  rowSelection: computed(() => rowSelection.value),
  updateFilter,
  getSelectedRows,
  clearRowSelection,
});
</script>
