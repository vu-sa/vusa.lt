import type { DataTableColumns, DataTableSortState } from 'naive-ui';
import type { Ref, Component } from 'vue';
import type { TableFilters } from '@/Composables/useTableState';

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
  initialSorters?: Record<string, DataTableSortState['order']>;
  
  /**
   * Initial filter state
   */
  initialFilters?: Record<string, any[]>;
  
  /**
   * Function that builds the columns definition
   */
  columnBuilder: (sorters: Ref<Record<string, DataTableSortState['order']>>, filters: Ref<TableFilters>) => DataTableColumns<T>;
  
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
 * State returned by useAdminTable
 */
export interface AdminTableState<T = Record<string, any>> {
  loading: Ref<boolean>;
  columns: Ref<DataTableColumns<T>>;
  sorters: Ref<Record<string, DataTableSortState['order']>>;
  filters: Ref<TableFilters>;
  searchText: Ref<string>;
  showSoftDeleted: Ref<boolean>;
  updateSorter: (key: string, order: DataTableSortState['order']) => void;
  updateFilter: (key: string, value: any) => void;
  updateSearch: (text: string) => void;
  toggleSoftDeleted: () => void;
  reloadData: (page?: number) => void;
  resetTableState: () => void;
  encodeTableState: () => Record<string, any>;
}