import type { ColumnDef, RowData } from '@tanstack/vue-table';

/**
 * Configuration for basic client-side tables (`SimpleDataTable`). Full admin collections use
 * `CollectionPage` instead.
 */
export interface SimpleTableProps<TData extends RowData> {
  /** The data array to display in the table */
  data: TData[];
  /** The columns configuration for the table */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  columns: ColumnDef<any, TData, any>[];
  /** Whether to enable pagination (defaults to true) */
  enablePagination?: boolean;
  /** Number of items per page (defaults to 10) */
  pageSize?: number;
  /** Whether to enable filtering UI (defaults to true) */
  enableFiltering?: boolean;
  rowClassName?: (row: TData) => string;
  emptyMessage?: string;
}
