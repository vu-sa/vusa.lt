import { ref, type Ref, provide, computed, watch } from 'vue';
import { router } from "@inertiajs/vue3";
import type { DataTableColumns, DataTableFilterState, DataTableSortState } from 'naive-ui';

export interface TableFilters {
  [key: string]: any;
}

export interface UseSortersOptions {
  initialSorters?: Record<string, DataTableSortState['order']>;
}

export interface UseFiltersOptions {
  initialFilters?: TableFilters;
}

export function useSorters(options: UseSortersOptions = {}) {
  const { initialSorters = {} } = options;
  const sorters = ref<Record<string, DataTableSortState['order']>>(initialSorters);

  function updateSorter(key: string, order: DataTableSortState['order']) {
    sorters.value[key] = order;
  }

  return {
    sorters,
    updateSorter
  };
}

export function useFilters(options: UseFiltersOptions = {}) {
  const { initialFilters = {} } = options;
  const filters = ref<TableFilters>(initialFilters);

  function updateFilter(key: string, value: any) {
    filters.value[key] = value;
  }

  function clearFilters() {
    for (const key in filters.value) {
      filters.value[key] = [];
    }
  }

  return {
    filters,
    updateFilter,
    clearFilters
  };
}

export interface UseTableStateOptions {
  injectSorters?: boolean;
  injectFilters?: boolean;
}

export function useTableState(
  sortersOptions: UseSortersOptions = {}, 
  filtersOptions: UseFiltersOptions = {},
  options: UseTableStateOptions = { injectSorters: true, injectFilters: true }
) {
  const { sorters, updateSorter } = useSorters(sortersOptions);
  const { filters, updateFilter, clearFilters } = useFilters(filtersOptions);
  
  // Auto-provide to dependency injection system for IndexDataTable
  if (options.injectSorters !== false) {
    provide('sorters', sorters);
  }
  
  if (options.injectFilters !== false) {
    provide('filters', filters);
  }

  return {
    sorters,
    updateSorter,
    filters,
    updateFilter,
    clearFilters
  };
}

export interface UseAdminTableOptions<T extends Record<string, any>> {
  modelName: string;
  initialSorters?: Record<string, DataTableSortState['order']>;
  initialFilters?: TableFilters;
  tableColumns: (sorters: Ref<Record<string, DataTableSortState['order']>>, filters: Ref<TableFilters>) => DataTableColumns<T>;
  reloadOnStateChange?: boolean;
}

export function useAdminTable<T extends Record<string, any>>(options: UseAdminTableOptions<T>) {
  const { 
    modelName, 
    initialSorters = {}, 
    initialFilters = {}, 
    tableColumns,
    reloadOnStateChange = false 
  } = options;
  
  const loading = ref(false);
  const searchText = ref('');
  const showSoftDeleted = ref(false);
  
  // Use the existing useTableState
  const { 
    sorters, 
    updateSorter, 
    filters, 
    updateFilter, 
    clearFilters 
  } = useTableState(
    { initialSorters },
    { initialFilters }
  );

  // Computed columns using the provided function
  const columns = computed(() => tableColumns(sorters, filters));

  // Handle URL parameter encoding
  const encodeTableState = () => {
    const encodedState: Record<string, any> = {
      page: 1
    };
    
    // Encode filters if not empty
    if (filters.value && Object.keys(filters.value).length > 0) {
      const hasActiveFilters = Object.values(filters.value).some(
        value => Array.isArray(value) ? value.length > 0 : value !== null
      );
      
      if (hasActiveFilters) {
        // Send as JSON string directly instead of base64
        encodedState.filters = JSON.stringify(filters.value);
      }
    }
    
    // Encode sorters if not empty
    if (sorters.value && Object.keys(sorters.value).length > 0) {
      const hasActiveSorters = Object.values(sorters.value).some(
        value => value !== false
      );
      
      if (hasActiveSorters) {
        // Send as JSON string directly instead of base64
        encodedState.sorters = JSON.stringify(sorters.value);
      }
    }

    // Add search text if it exists
    if (searchText.value) {
      encodedState.text = searchText.value;
    }

    // Add soft delete filter if enabled
    if (showSoftDeleted.value) {
      encodedState.showSoftDeleted = 'true';
    }
    
    return encodedState;
  };

  // Reload data with current state
  const reloadData = (page = 1) => {
    loading.value = true;
    
    const state = encodeTableState();
    state.page = page;
    
    router.reload({
      data: state,
      only: [modelName],
      onSuccess: () => {
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      }
    });
  };

  // Clear all filters and sorters
  const resetTableState = () => {
    clearFilters();
    
    // Reset sorters
    Object.keys(sorters.value).forEach(key => {
      sorters.value[key] = false;
    });
    
    searchText.value = '';
    showSoftDeleted.value = false;
    
    reloadData();
  };

  // Update search text
  const updateSearch = (text: string) => {
    searchText.value = text;
    if (reloadOnStateChange) {
      reloadData();
    }
  };

  // Toggle soft deleted items
  const toggleSoftDeleted = () => {
    showSoftDeleted.value = !showSoftDeleted.value;
    if (reloadOnStateChange) {
      reloadData();
    }
  };

  // Watch for changes in filters or sorters if reloadOnStateChange is true
  if (reloadOnStateChange) {
    watch([filters, sorters], () => {
      reloadData();
    }, { deep: true });
  }

  return {
    loading,
    columns,
    sorters,
    filters,
    searchText,
    showSoftDeleted,
    updateSorter,
    updateFilter,
    updateSearch,
    toggleSoftDeleted,
    reloadData,
    resetTableState,
    encodeTableState
  };
}