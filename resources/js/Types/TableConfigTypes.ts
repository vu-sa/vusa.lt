import { type ColumnDef, type SortingState, type RowSelectionState } from '@tanstack/vue-table';
import type { BreadcrumbItem } from "@/Composables/useBreadcrumbs";

/**
 * Core table configuration
 */
export interface TableConfig<TData> {
  /** The model name in plural form (e.g., 'institutions') */
  modelName: string;
  /** The entity name in singular form (e.g., 'institution') - defaults to singular of modelName */
  entityName?: string;
  /** The data array to display in the table */
  data: TData[];
  /** The columns configuration for the table */
  columns: ColumnDef<TData, any>[];
  /** Function to get a unique ID for each row (used for selection and keys) */
  getRowId?: (originalRow: TData, index: number, parent?: any) => string;
}

/**
 * Pagination configuration
 */
export interface PaginationConfig {
  /** Total count of items (across all pages) */
  totalCount: number;
  /** Current page number (1-based) */
  initialPage?: number;
  /** Number of items per page */
  pageSize?: number;
}

/**
 * UI customization options
 */
export interface UIConfig {
  /** Page title displayed in the header */
  headerTitle?: string;
  /** Description displayed under the title */
  headerDescription?: string;
  /** Icon to display next to the title */
  icon?: any;
  /** Route to create a new item */
  createRoute?: string;
  /** Whether the user can create new items */
  canCreate?: boolean;
  /** Back route (if applicable) */
  backRoute?: string;
  /** Custom message when table is empty */
  emptyMessage?: string;
  /** Custom description when table is empty */
  emptyDescription?: string;
  /** Custom icon when table is empty */
  emptyIcon?: any;
  /** Breadcrumbs for the page */
  breadcrumbs?: BreadcrumbItem[];
}

/**
 * Filtering and sorting configuration
 */
export interface FilteringConfig {
  /** Initial filters to apply */
  initialFilters?: Record<string, unknown>;
  /** Initial sorting to apply */
  initialSorting?: SortingState;
  /** Whether to allow toggling deleted items */
  allowToggleDeleted?: boolean;
  /** Whether to enable filtering UI */
  enableFiltering?: boolean;
  /** Whether to enable column visibility toggles */
  enableColumnVisibility?: boolean;
}

/**
 * Row selection configuration
 */
export interface RowSelectionConfig {
  /** Whether to enable row selection */
  enableRowSelection?: boolean;
  /** Whether to enable multi-row selection */
  enableMultiRowSelection?: boolean;
  /** Whether to show the row selection column */
  enableRowSelectionColumn?: boolean;
  /** Initial row selection state */
  initialRowSelection?: RowSelectionState;
}

/**
 * Combined table configuration
 */
export interface IndexTablePageProps<TData> extends 
  TableConfig<TData>, 
  PaginationConfig, 
  UIConfig, 
  FilteringConfig, 
  RowSelectionConfig {}