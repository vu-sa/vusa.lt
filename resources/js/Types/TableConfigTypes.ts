import { type ColumnDef, type SortingState, type RowSelectionState } from '@tanstack/vue-table';
import type { BreadcrumbItem } from "@/Composables/useBreadcrumbs";

/**
 * Essential table configuration - required props only
 */
export interface BaseTableConfig<TData> {
  /** The model name in plural form (e.g., 'institutions') */
  modelName: string;
  /** The data array to display in the table */
  data: TData[];
  /** The columns configuration for the table */
  columns: ColumnDef<TData, any>[];
  /** Total count of items (across all pages) - required for server-side tables */
  totalCount: number;
  
  // Optional but commonly used
  /** The entity name in singular form (e.g., 'institution') - defaults to singular of modelName */
  entityName?: string;
  /** Function to get a unique ID for each row (used for selection and keys) */
  getRowId?: (originalRow: TData, index: number, parent?: any) => string;
  /** Current page number (1-based, defaults to 1) */
  initialPage?: number;
  /** Number of items per page (defaults to 15) */
  pageSize?: number;
}

/**
 * Advanced table features - sorting, filtering, row selection
 */
export interface AdvancedTableConfig {
  // Filtering and sorting
  /** Initial filters to apply */
  initialFilters?: Record<string, unknown>;
  /** Initial sorting to apply */
  initialSorting?: SortingState;
  /** Whether to enable filtering UI (defaults to true) */
  enableFiltering?: boolean;
  /** Whether to enable column visibility toggles (defaults to false) */
  enableColumnVisibility?: boolean;
  /** Whether to allow toggling deleted items */
  allowToggleDeleted?: boolean;
  /** Whether to show deleted items initially */
  showDeleted?: boolean;
  
  // Row selection
  /** Whether to enable row selection (defaults to false) */
  enableRowSelection?: boolean;
  /** Whether to enable multi-row selection (defaults to true if enableRowSelection is true) */
  enableMultiRowSelection?: boolean;
  /** Whether to show the row selection column (defaults to true if enableRowSelection is true) */
  enableRowSelectionColumn?: boolean;
  /** Initial row selection state */
  initialRowSelection?: RowSelectionState;
}

/**
 * Page and UI customization - for full-page table layouts
 */
export interface PageTableConfig {
  // Page layout
  /** Page title displayed in the header */
  headerTitle?: string;
  /** Description displayed under the title */
  headerDescription?: string;
  /** Icon to display next to the title */
  icon?: any;
  /** Breadcrumbs for the page */
  breadcrumbs?: BreadcrumbItem[];
  /** Back route (if applicable) */
  backRoute?: string;
  
  // Create functionality
  /** Route to create a new item */
  createRoute?: string;
  /** Whether the user can create new items (defaults to false) */
  canCreate?: boolean;
  
  // Customization
  /** Custom message when table is empty */
  emptyMessage?: string;
  /** Custom description when table is empty */
  emptyDescription?: string;
  /** Custom icon when table is empty */
  emptyIcon?: any;
  /** Function to apply custom CSS classes to rows */
  rowClassName?: (row: any) => string;
}

/**
 * Complete configuration for IndexTablePage component
 */
export interface IndexTablePageProps<TData> extends 
  BaseTableConfig<TData>, 
  AdvancedTableConfig, 
  PageTableConfig {}

/**
 * Configuration for ServerDataTable component (no page wrapper)
 */
export interface ServerTableProps<TData> extends 
  BaseTableConfig<TData>, 
  AdvancedTableConfig {
  /** Custom message when table is empty */
  emptyMessage?: string;
  /** Custom icon when table is empty */
  emptyIcon?: any;
  /** Function to apply custom CSS classes to rows */
  rowClassName?: (row: TData) => string;
  /** Whether to reload only this component (for Inertia.js optimization, defaults to false) */
  reloadOnly?: boolean;
  /** Whether to show deleted items initially */
  showDeleted?: boolean;
}

/**
 * Configuration for basic client-side tables
 */
export interface SimpleTableProps<TData> {
  /** The data array to display in the table */
  data: TData[];
  /** The columns configuration for the table */
  columns: ColumnDef<TData, any>[];
  /** Whether to enable pagination (defaults to true) */
  enablePagination?: boolean;
  /** Number of items per page (defaults to 10) */
  pageSize?: number;
  /** Whether to enable filtering UI (defaults to true) */
  enableFiltering?: boolean;
  /** Whether to enable column visibility toggles (defaults to false) */
  enableColumnVisibility?: boolean;
  /** Custom message when table is empty */
  emptyMessage?: string;
  /** Custom icon when table is empty */
  emptyIcon?: any;
}

// Legacy type aliases for backward compatibility
/** @deprecated Use BaseTableConfig instead */
export type TableConfig<TData> = BaseTableConfig<TData>;
/** @deprecated Use AdvancedTableConfig instead */
export type PaginationConfig = Pick<BaseTableConfig<any>, 'totalCount' | 'initialPage' | 'pageSize'>;
/** @deprecated Use PageTableConfig instead */
export type UIConfig = PageTableConfig;
/** @deprecated Use AdvancedTableConfig instead */
export type FilteringConfig = Pick<AdvancedTableConfig, 'initialFilters' | 'initialSorting' | 'allowToggleDeleted' | 'enableFiltering' | 'enableColumnVisibility'>;
/** @deprecated Use AdvancedTableConfig instead */
export type RowSelectionConfig = Pick<AdvancedTableConfig, 'enableRowSelection' | 'enableMultiRowSelection' | 'enableRowSelectionColumn' | 'initialRowSelection'>;

/**
 * Preset configurations for common table types
 */
export const TablePresets = {
  /**
   * Basic read-only table with minimal features
   */
  readonly: {
    enableFiltering: false,
    enableColumnVisibility: false,
    enableRowSelection: false,
    canCreate: false,
    pageSize: 10
  } as Partial<AdvancedTableConfig & PageTableConfig>,

  /**
   * Standard admin table with search and basic features
   */
  basic: {
    enableFiltering: true,
    enableColumnVisibility: false,
    enableRowSelection: false,
    canCreate: true,
    pageSize: 15
  } as Partial<AdvancedTableConfig & PageTableConfig>,

  /**
   * Full-featured admin table with all capabilities
   */
  admin: {
    enableFiltering: true,
    enableColumnVisibility: true,
    enableRowSelection: true,
    enableMultiRowSelection: true,
    enableRowSelectionColumn: true,
    allowToggleDeleted: true,
    canCreate: true,
    pageSize: 15
  } as Partial<AdvancedTableConfig & PageTableConfig>
} as const;

/**
 * Helper function to merge preset with custom configuration
 */
export function createTableConfig<TData>(
  preset: keyof typeof TablePresets,
  customConfig: Partial<IndexTablePageProps<TData>>
): IndexTablePageProps<TData> {
  return {
    ...TablePresets[preset],
    ...customConfig
  } as IndexTablePageProps<TData>;
}