import type { ColumnDef, SortingState, VisibilityState, PaginationState } from '@tanstack/vue-table';
import type { Ref, Component } from 'vue';

/**
 * Common route capabilities for model index pages
 */
export interface RouteCapabilities {
  create?: boolean;
  show?: boolean;
  edit?: boolean;
  duplicate?: boolean;
  destroy?: boolean;
}

/**
 * Base properties for any admin index page
 */
export interface AdminIndexPageProps<T = Record<string, any>> {
  /**
   * Paginated models returned from the server
   */
  paginatedModels: PaginatedModels<T[]>;
  
  /**
   * Model name in snake_case - used for route generation
   */
  modelName: string;
  
  /**
   * Entity name for display - e.g. "Institution" or "User"
   */
  entityName?: string;
  
  /**
   * Entity name plural for display in title - e.g. "Institutions" or "Users"
   */
  entityNamePlural?: string;
  
  /**
   * Initial sorter state
   */
  initialSorting?: SortingState;
  
  /**
   * Initial filter state
   */
  initialFilters?: TableFilters;
  
  /**
   * Function that builds the columns definition
   */
  columnBuilder: (sorting: Ref<SortingState>, filters: Ref<TableFilters>) => ColumnDef<T, any>[];
  
  /**
   * Which routes are enabled for this index page
   */
  canUseRoutes?: RouteCapabilities;
  
  /**
   * Icon component to display in the header
   */
  icon?: Component;
  
  /**
   * Optional custom title. If not provided, will be generated from entityName
   */
  title?: string;
}

/**
 * State returned by useServerTable for admin tables
 */
export interface AdminTableState<T = Record<string, any>> {
  data: Ref<T[]>;
  columns: Ref<ColumnDef<T, any>[]>;
  sorting: Ref<SortingState>;
  // filters: Ref<TableFilters>;
  columnVisibility: Ref<VisibilityState>;
  pagination: Ref<PaginationState>;
  globalFilter: Ref<string>;
  loading: Ref<boolean>;
  totalItems: Ref<number>;
  updateSort: (sorting: SortingState) => void;
  updateFilter: (key: string, value: any) => void;
  updateGlobalFilter: (value: string) => void;
  updateColumnVisibility: (visibility: VisibilityState) => void;
  updatePage: (pageIndex: number) => void;
  clearFilters: () => void;
  resetTable: () => void;
  reloadData: (page?: number) => void;
  encodeTableState: () => Record<string, any>;
}