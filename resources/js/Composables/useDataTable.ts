import { ref, computed, watch, type Ref } from 'vue';
import { router } from "@inertiajs/vue3";
import type { ColumnDef, SortingState, VisibilityState, PaginationState } from '@tanstack/vue-table';
import { trans as $t } from "laravel-vue-i18n";
import { debounce } from 'lodash';

/**
 * Interface for table filters
 */
export interface TableFilters {
  [key: string]: any;
}

/**
 * Client-side table options
 */
export interface UseClientTableOptions<TData> {
  data: TData[];
  columns: ColumnDef<TData, any>[];
  initialSorting?: SortingState;
  initialFilters?: TableFilters;
  initialVisibility?: VisibilityState;
  initialPagination?: PaginationState;
  pageSize?: number;
}

/**
 * Server-side table options
 */
export interface UseServerTableOptions<TData> {
  modelName: string;
  columns: ColumnDef<TData, any>[] | ((
    sorting: Ref<SortingState>, 
    filters: Ref<TableFilters>
  ) => ColumnDef<TData, any>[]);
  initialData?: TData[];
  initialSorting?: SortingState;
  initialFilters?: TableFilters;
  initialVisibility?: VisibilityState;
  initialPagination?: { pageIndex: number; pageSize: number };
  paginationTotalItems?: number;
  onDataLoaded?: (data: any) => void;
  debounceTime?: number; // Debounce time for filter updates in ms
  reloadOnly?: boolean; // Whether to use router.reload instead of router.visit
}

/**
 * Common table state and methods
 */
interface TableState<TData> {
  data: Ref<TData[]>;
  columns: Ref<ColumnDef<TData, any>[]>;
  sorting: Ref<SortingState>;
  filters: Ref<TableFilters>;
  columnVisibility: Ref<VisibilityState>;
  pagination: Ref<PaginationState>;
  globalFilter: Ref<string>;
  loading: Ref<boolean>;
  
  // Methods
  updateSort: (sorting: SortingState) => void;
  updateFilter: (key: string, value: any) => void;
  updateGlobalFilter: (value: string) => void;
  updateColumnVisibility: (visibility: VisibilityState) => void;
  updatePage: (pageIndex: number) => void;
  clearFilters: () => void;
  resetTable: () => void;
}

/**
 * Client-side Table State with additional methods
 */
export interface ClientTableState<TData> extends TableState<TData> {
  filteredData: Ref<TData[]>;
}

/**
 * Server-side Table State with additional methods
 */
export interface ServerTableState<TData> extends TableState<TData> {
  totalItems: Ref<number>;
  reloadData: (page?: number) => void;
  encodeTableState: () => Record<string, any>;
}

/**
 * Client-side table implementation
 */
export function useClientTable<TData extends Record<string, any>>(
  options: UseClientTableOptions<TData>
): ClientTableState<TData> {
  const {
    data: initialData,
    columns: initialColumns,
    initialSorting = [],
    initialFilters = {},
    initialVisibility = {},
    initialPagination = { pageIndex: 0, pageSize: 10 },
    pageSize = 10
  } = options;

  // Core state
  const data = ref<TData[]>(initialData) as Ref<TData[]>;
  const columns = ref<ColumnDef<TData, any>[]>(initialColumns);
  const sorting = ref<SortingState>(initialSorting);
  const filters = ref<TableFilters>(initialFilters);
  const columnVisibility = ref<VisibilityState>(initialVisibility);
  const pagination = ref<PaginationState>({
    pageIndex: initialPagination.pageIndex,
    pageSize: initialPagination.pageSize || pageSize
  });
  const globalFilter = ref<string>('');
  const loading = ref<boolean>(false);

  // Methods
  const updateSort = (newSorting: SortingState) => {
    sorting.value = newSorting;
  };

  const updateFilter = (key: string, value: any) => {
    filters.value[key] = value;
  };

  const updateGlobalFilter = (value: string) => {
    globalFilter.value = value;
  };

  const updateColumnVisibility = (visibility: VisibilityState) => {
    columnVisibility.value = visibility;
  };

  const updatePage = (pageIndex: number) => {
    pagination.value.pageIndex = pageIndex;
  };

  const clearFilters = () => {
    for (const key in filters.value) {
      filters.value[key] = Array.isArray(filters.value[key]) ? [] : null;
    }
    globalFilter.value = '';
  };

  const resetTable = () => {
    clearFilters();
    sorting.value = initialSorting;
    columnVisibility.value = initialVisibility;
    pagination.value = {
      pageIndex: 0,
      pageSize: pageSize
    };
  };

  // Filtered data based on client-side filtering
  const filteredData = computed(() => {
    // Start with all data
    let filteredData = [...data.value];

    // Apply global filter if present
    if (globalFilter.value) {
      const searchTerm = globalFilter.value.toLowerCase();
      filteredData = filteredData.filter(row => {
        // Search through all string and number values in the row
        return Object.values(row).some(value => 
          (typeof value === 'string' || typeof value === 'number') && 
          String(value).toLowerCase().includes(searchTerm)
        );
      });
    }

    // Apply specific filters
    Object.entries(filters.value).forEach(([key, filterValue]) => {
      if (!filterValue || (Array.isArray(filterValue) && !filterValue.length)) {
        return;
      }

      filteredData = filteredData.filter(row => {
        const value = row[key];
        
        if (Array.isArray(filterValue)) {
          if (Array.isArray(value)) {
            // Handle array-to-array matching (e.g., tags)
            return filterValue.some(f => value.includes(f));
          }
          // Single value matching against array of filters
          return filterValue.includes(value);
        }
        
        // Direct equality check
        return value === filterValue;
      });
    });

    return filteredData;
  });

  return {
    data,
    columns,
    sorting,
    filters,
    columnVisibility, 
    pagination,
    globalFilter,
    loading,
    filteredData,
    updateSort,
    updateFilter,
    updateGlobalFilter,
    updateColumnVisibility,
    updatePage,
    clearFilters,
    resetTable
  };
}

/**
 * Server-side table implementation
 */
export function useServerTable<TData extends Record<string, any>>(
  options: UseServerTableOptions<TData>
): ServerTableState<TData> {
  const {
    modelName,
    columns: columnsOption,
    initialData = [] as TData[],
    initialSorting = [],
    initialFilters = {},
    initialVisibility = {},
    initialPagination = { pageIndex: 0, pageSize: 10 },
    paginationTotalItems = 0,
    onDataLoaded,
    debounceTime = 300,
    reloadOnly = false
  } = options;

  // Core state
  const data = ref<TData[]>(initialData) as Ref<TData[]>;
  const sorting = ref<SortingState>(initialSorting);
  const filters = ref<TableFilters>(initialFilters);
  const columnVisibility = ref<VisibilityState>(initialVisibility);
  const globalFilter = ref<string>('');
  const pagination = ref<PaginationState>({
    pageIndex: initialPagination.pageIndex,
    pageSize: initialPagination.pageSize
  });
  const totalItems = ref<number>(paginationTotalItems);
  const loading = ref<boolean>(false);

  // Handle both static columns and function-based columns
  const columns = computed(() => {
    if (typeof columnsOption === 'function') {
      return columnsOption(sorting, filters);
    }
    return columnsOption;
  });

  // Methods
  const updateSort = (newSorting: SortingState) => {
    sorting.value = newSorting;
    updatePage(0); // Reset to first page on sort change
    reloadData(); // Automatically reload data when sorting changes
  };

  const updateFilter = (key: string, value: any) => {
    filters.value[key] = value;
    // Don't automatically reload - this will be handled by the component
    // through the reloadData call
  };

  // Debounced version of the reload function for text inputs
  const debouncedReloadData = debounce((page?: number) => {
    reloadData(page);
  }, debounceTime);

  const updateGlobalFilter = (value: string) => {
    globalFilter.value = value;
    updatePage(0); // Reset to first page on search change
    debouncedReloadData(0); // Use debounced reload for global filter
  };

  const updateColumnVisibility = (visibility: VisibilityState) => {
    columnVisibility.value = visibility;
  };

  const updatePage = (pageIndex: number) => {
    pagination.value.pageIndex = pageIndex;
  };

  const clearFilters = () => {
    for (const key in filters.value) {
      filters.value[key] = Array.isArray(filters.value[key]) ? [] : null;
    }
    globalFilter.value = '';
    updatePage(0);
    reloadData(0);
  };

  const resetTable = () => {
    clearFilters();
    sorting.value = initialSorting;
    columnVisibility.value = initialVisibility;
    pagination.value = {
      pageIndex: 0,
      pageSize: pagination.value.pageSize
    };
    reloadData();
  };

  // Encode table state for server requests
  const encodeTableState = () => {
    const encodedState: Record<string, any> = {
      page: pagination.value.pageIndex + 1, // Convert from 0-based to 1-based for backend
      per_page: pagination.value.pageSize
    };
    
    // Encode sorting if present
    if (sorting.value.length > 0) {
      encodedState.sorting = JSON.stringify(sorting.value);
    }
    
    // Encode filters if not empty
    if (filters.value && Object.keys(filters.value).length > 0) {
      // Filter out empty values before encoding
      const activeFilters = Object.entries(filters.value).reduce((acc, [key, value]) => {
        // Skip empty arrays or null values
        if (
          (Array.isArray(value) && value.length > 0) || 
          (value !== null && value !== undefined && value !== '')
        ) {
          acc[key] = value;
        }
        return acc;
      }, {} as Record<string, any>);
      
      if (Object.keys(activeFilters).length > 0) {
        encodedState.filters = JSON.stringify(activeFilters);
      }
    }
    
    // Add global search if present
    if (globalFilter.value) {
      encodedState.search = globalFilter.value;
    }
    
    return encodedState;
  };

  // Reload data from server
  const reloadData = (page?: number) => {
    if (page !== undefined) {
      pagination.value.pageIndex = page;
    }
    
    loading.value = true;
    const state = encodeTableState();
    
    // Common options for both reload and visit
    const options = {
      data: state,
      preserveScroll: true,
      onSuccess: (response) => {
        // Handle the response
        const responseData = response.props[modelName];
        if (responseData) {
          // Update data with the response
          if (Array.isArray(responseData.data)) {
            data.value = responseData.data;
          } else if (responseData.data !== undefined) {
            // Some API endpoints might nest the data differently
            data.value = responseData;
          }
          
          // Update total items for pagination if available
          if (responseData.meta) {
            totalItems.value = responseData.meta.total;
          } else if (responseData.total !== undefined) {
            totalItems.value = responseData.total;
          }
          
          // Call the callback if provided
          if (onDataLoaded) {
            onDataLoaded(responseData);
          }
        }
        loading.value = false;
      },
      onError: (errors) => {
        console.error('Error fetching data:', errors);
        loading.value = false;
      }
    };
    
    // Use either reload or visit depending on configuration
    if (reloadOnly) {
      router.reload(options);
    } else {
      router.visit(window.location.pathname, options);
    }
  };

  // Return all state and methods
  return {
    data,
    columns,
    sorting,
    filters,
    columnVisibility,
    pagination,
    globalFilter,
    loading,
    totalItems,
    updateSort,
    updateFilter,
    updateGlobalFilter,
    updateColumnVisibility,
    updatePage,
    clearFilters,
    resetTable,
    reloadData,
    encodeTableState
  };
}