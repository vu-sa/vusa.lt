import {
  getCoreRowModel,
  useVueTable,
  type ColumnDef,
  type RowSelectionState,
  type SortingState,
  type Table,
  type Updater,
  type VisibilityState,
} from '@tanstack/vue-table';
import { useStorage } from '@vueuse/core';
import { computed, watch, type Ref } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import type { CollectionSortOption } from '@/Composables/useCollectionSource';

export const SELECT_COLUMN_ID = '__select';

interface CollectionTableOptions<T> {
  /** Scopes the remembered column visibility. */
  collection: string;
  items: Ref<readonly T[]>;
  itemKey: (item: T) => string;
  columns: Ref<CollectionColumn[]>;
  selectable: Ref<boolean>;
  /** Rows that may not be selected (a reservation already decided) get no checkbox. */
  canSelect?: (item: T) => boolean;
  /** Selected item keys; owned by the page so bulk actions can read it. */
  selection: Ref<string[]>;
  sortBy: Ref<string>;
  sortOptions: Ref<CollectionSortOption[]>;
  setSortBy: (value: string) => void;
}

function resolve<V>(updater: Updater<V>, current: V): V {
  return typeof updater === 'function' ? (updater as (old: V) => V)(current) : updater;
}

/**
 * TanStack Table as the state engine of a collection — selection, column visibility and header
 * sorting — while the collection source keeps owning the data. Everything the server does
 * (search, facets, sort order, paging) runs in manual mode, so the two never disagree about
 * what the rows are.
 */
export function useCollectionTable<T>(options: CollectionTableOptions<T>): {
  table: Table<T>;
  hideableColumns: Ref<CollectionColumn[]>;
  columnVisibility: Ref<VisibilityState>;
  isSortable: (column: CollectionColumn) => boolean;
  toggleRow: (rowId: string, value: boolean, options?: { range?: boolean }) => void;
} {
  const columnVisibility = useStorage<VisibilityState>(`admin-collection-columns:${options.collection}`, {});

  const sortValues = computed(() => new Set(options.sortOptions.value.map(option => option.value)));
  const isSortable = (column: CollectionColumn) =>
    column.sortField !== undefined
    && sortValues.value.has(`${column.sortField}:asc`)
    && sortValues.value.has(`${column.sortField}:desc`);

  const columnDefs = computed<ColumnDef<T>[]>(() => {
    const defs: ColumnDef<T>[] = options.columns.value.map((column, index) => ({
      id: column.key,
      // TanStack only sorts a column that has an accessor; the value itself is never read,
      // since the source sorts and cells render through the page's slot.
      accessorFn: row => (row as Record<string, unknown>)[column.key],
      header: column.label,
      enableSorting: isSortable(column),
      enableHiding: index > 0 && !column.pinned,
      meta: column,
    }));

    if (options.selectable.value) {
      defs.unshift({ id: SELECT_COLUMN_ID, header: '', enableSorting: false, enableHiding: false });
    }

    return defs;
  });

  const sorting = computed<SortingState>(() => {
    const [field, direction] = options.sortBy.value.split(':');
    const column = options.columns.value.find(candidate => candidate.sortField === field);

    return column && isSortable(column) ? [{ id: column.key, desc: direction === 'desc' }] : [];
  });

  const rowSelection = computed<RowSelectionState>(() =>
    Object.fromEntries(options.selection.value.map(key => [key, true])),
  );

  const table = useVueTable<T>({
    get data() {
      return options.items.value as T[];
    },
    get columns() {
      return columnDefs.value;
    },
    getRowId: row => options.itemKey(row),
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    enableMultiSort: false,
    enableSortingRemoval: false,
    get enableRowSelection() {
      return options.selectable.value ? row => options.canSelect?.(row.original) ?? true : false;
    },
    state: {
      get sorting() {
        return sorting.value;
      },
      get rowSelection() {
        return rowSelection.value;
      },
      get columnVisibility() {
        return columnVisibility.value;
      },
    },
    onSortingChange: (updater) => {
      const [next] = resolve(updater, sorting.value);
      const column = options.columns.value.find(candidate => candidate.key === next?.id);

      if (next && column?.sortField) {
        options.setSortBy(`${column.sortField}:${next.desc ? 'desc' : 'asc'}`);
      }
    },
    onRowSelectionChange: (updater) => {
      const next = resolve(updater, rowSelection.value);
      options.selection.value = Object.keys(next).filter(key => next[key]);
    },
    onColumnVisibilityChange: (updater) => {
      columnVisibility.value = resolve(updater, columnVisibility.value);
    },
  });

  // Shift-click selects every row between the last toggled one and this one.
  let anchorId: string | null = null;
  watch(options.items, () => {
    anchorId = null;
  });

  function toggleRow(rowId: string, value: boolean, { range = false } = {}): void {
    const { rows } = table.getRowModel();
    const from = range && anchorId !== null ? rows.findIndex(row => row.id === anchorId) : -1;
    const to = rows.findIndex(row => row.id === rowId);
    anchorId = rowId;

    if (from === -1 || to === -1) {
      table.getRow(rowId)?.toggleSelected(value);
      return;
    }

    const between = rows.slice(Math.min(from, to), Math.max(from, to) + 1).filter(row => row.getCanSelect());
    table.setRowSelection((current) => {
      const next = { ...current };
      between.forEach((row) => {
        if (value) {
          next[row.id] = true;
        }
        else {
          delete next[row.id];
        }
      });

      return next;
    });
  }

  return {
    table,
    toggleRow,
    hideableColumns: computed(() => options.columns.value.filter((column, index) => index > 0 && !column.pinned)),
    columnVisibility,
    isSortable,
  };
}
