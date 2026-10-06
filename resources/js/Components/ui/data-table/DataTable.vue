<template>
  <div class="space-y-4">
    <div
      v-if="enableFiltering || enableColumnVisibility || (showSelectionCount && enableRowSelection && table.getSelectedRowModel().rows.length > 0)"
      class="flex w-full flex-wrap items-center justify-between gap-2"
    >
      <div class="flex flex-wrap items-center gap-2">
        <!-- Show selection count when rows are selected (opt-in) -->
        <div
          v-if="showSelectionCount && enableRowSelection && table.getSelectedRowModel().rows.length > 0"
          class="inline-flex items-center gap-2 border border-border bg-secondary/50 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-foreground"
        >
          <span>
            {{ $t('tables.selected') }}: <span class="tabular-nums text-brand">{{ table.getSelectedRowModel().rows.length }}</span>
          </span>
          <Button variant="ghost" size="sm" class="size-6 p-0" @click="table.resetRowSelection()">
            <span class="sr-only">{{ $t('tables.clear_selection') }}</span>
            <span aria-hidden="true" class="text-base leading-none">&times;</span>
          </Button>
        </div>

        <div v-if="enableFiltering && !manualFiltering" class="flex items-center gap-2">
          <Input
            class="max-w-sm"
            :placeholder="$t('tables.search_placeholder')"
            :value="globalFilter"
            @input="handleGlobalFilter"
          />
        </div>

        <slot name="filters" />
      </div>

      <div class="flex items-center gap-2">
        <slot name="actions" />

        <!-- Column visibility temporarily hidden -->
        <DropdownMenu v-if="false">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="ml-auto">
              <Sliders class="mr-2 size-4" />
              {{ $t('tables.columns') }}
              <ChevronDown class="ml-2 size-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="bg-popover border border-border">
            <DropdownMenuCheckboxItem
              v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
              :key="column.id"
              :model-value="column.getIsVisible()"
              @click="column.toggleVisibility()"
              @select.prevent
            >
              {{ column.id }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <div class="flex flex-col border border-border" data-slot="data-table">
      <div ref="tableScrollRef" class="w-full overflow-auto max-h-[50vh] md:max-h-[calc(100vh-360px)]">
        <Table class="w-full table-fixed isolate">
          <TableHeader>
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
              <TableHead
                v-for="header in headerGroup.headers"
                :key="header.id"
                class="sticky top-0 z-10 border-b border-border bg-secondary/80 backdrop-blur-xs"
                :class="{
                  'cursor-pointer select-none hover:bg-secondary transition-colors': header.column.getCanSort(),
                }"
                :style="{ width: header.column.columnDef.size ? `${header.column.columnDef.size}px` : 'auto' }"
                :aria-sort="header.column.getIsSorted() ? (header.column.getIsSorted() === 'asc' ? 'ascending' : 'descending') : undefined"
                @click="header.column.getCanSort() ? header.column.getToggleSortingHandler()?.($event) : undefined"
              >
                <div class="flex items-center justify-between">
                  <button
                    v-if="header.column.getCanSort()"
                    type="button"
                    tabindex="-1"
                    :class="[
                      '-mx-1 inline-flex w-full items-center justify-between gap-1 px-1',
                      'uppercase tracking-[inherit] hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring',
                    ]"
                  >
                    <span class="truncate">
                      <FlexRender v-if="!header.isPlaceholder" :header />
                    </span>
                    <span class="inline-flex shrink-0 items-center ml-1.5">
                      <ArrowUp v-if="header.column.getIsSorted() === 'asc'" class="size-3.5 text-brand" aria-hidden="true" />
                      <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" class="size-3.5 text-brand" aria-hidden="true" />
                      <ArrowUpDown v-else class="size-3.5 opacity-50" aria-hidden="true" />
                    </span>
                  </button>
                  <div v-else class="truncate">
                    <FlexRender v-if="!header.isPlaceholder" :header />
                  </div>
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="table.getRowModel().rows?.length">
              <TableRow
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                :data-state="row.getIsSelected() ? 'selected' : undefined"
                :class="[
                  'group border-b border-border/60 transition-colors last:border-b-0 hover:bg-secondary/40 data-[state=selected]:bg-brand/5',
                  rowClassName ? rowClassName(row.original) : '',
                ]"
              >
                <TableCell
                  v-for="cell in row.getVisibleCells()"
                  :key="cell.id"
                  class="min-w-0"
                  :style="{ width: cell.column.columnDef.size ? `${cell.column.columnDef.size}px` : 'auto' }"
                >
                  <FlexRender :cell />
                </TableCell>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center">
                  <slot name="empty">
                    {{ emptyMessage || $t('tables.no_results') }}
                  </slot>
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>

      <!-- Use custom pagination slot if available (for server-side mode) -->
      <slot name="pagination">
        <div v-if="pagination === true && table.getPageCount() > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-secondary/20 px-4 py-2.5">
          <div class="text-xs text-muted-foreground shrink-0 tabular-nums">
            {{ paginationState.pageIndex * paginationState.pageSize + 1 }}
            –
            {{ Math.min((paginationState.pageIndex + 1) * paginationState.pageSize, table.getRowCount()) }}
            / {{ table.getRowCount() }}
          </div>
          <Pagination
            :items-per-page="paginationState.pageSize"
            :total="table.getRowCount()"
            class="min-w-0"
          >
            <PaginationContent class="gap-1">
              <PaginationItem :value="1">
                <PaginationFirst :disabled="!table.getCanPreviousPage()" size="icon-sm" @click="table.setPageIndex(0)">
                  <ChevronsLeft class="size-4" />
                  <span class="sr-only">{{ $t('tables.first_page') }}</span>
                </PaginationFirst>
              </PaginationItem>
              <PaginationItem :value="paginationState.pageIndex">
                <PaginationPrevious :disabled="!table.getCanPreviousPage()" size="icon-sm" @click="table.previousPage()">
                  <ChevronLeft class="size-4" />
                  <span class="sr-only">{{ $t('tables.previous_page') }}</span>
                </PaginationPrevious>
              </PaginationItem>

              <div class="flex items-center text-xs font-bold px-2 tabular-nums">
                {{ paginationState.pageIndex + 1 }} / {{ table.getPageCount() }}
              </div>

              <PaginationItem :value="paginationState.pageIndex + 2">
                <PaginationNext :disabled="!table.getCanNextPage()" size="icon-sm" @click="table.nextPage()">
                  <ChevronRight class="size-4" />
                  <span class="sr-only">{{ $t('tables.next_page') }}</span>
                </PaginationNext>
              </PaginationItem>
              <PaginationItem :value="table.getPageCount()">
                <PaginationLast :disabled="!table.getCanNextPage()" size="icon-sm" @click="table.setPageIndex(table.getPageCount() - 1)">
                  <ChevronsRight class="size-4" />
                  <span class="sr-only">{{ $t('tables.last_page') }}</span>
                </PaginationLast>
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup lang="tsx" generic="TData, TValue">
import {
  columnFilteringFeature,
  columnVisibilityFeature,
  createFilteredRowModel,
  createPaginatedRowModel,
  createSortedRowModel,
  filterFn_includesString,
  globalFilteringFeature,
  rowPaginationFeature,
  rowSelectionFeature,
  rowSortingFeature,
  tableFeatures,
  useTable,
  FlexRender,
  type ColumnDef,
  type PaginationState,
  type RowSelectionState,
  type SortingState,
  type VisibilityState,
} from '@tanstack/vue-table';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  Sliders,
  X,
} from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
  PaginationFirst,
  PaginationLast,
} from '@/Components/ui/pagination';

const props = defineProps<{
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  columns: ColumnDef<any, TData, TValue>[];
  data: TData[];
  rowClassName?: (row: TData) => string;
  pageSize?: number;
  pagination?: boolean;
  emptyMessage?: string;
  enableFiltering?: boolean;
  enableColumnVisibility?: boolean;
  initialSort?: { id: string; desc: boolean }[];
  globalFilter?: string;
  manualSorting?: boolean;
  manualFiltering?: boolean;
  manualPagination?: boolean;
  externalSorting?: SortingState;
  externalPagination?: PaginationState;
  rowCount?: number;
  pageCount?: number;
  // Row selection props
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  enableRowSelection?: boolean | ((row: any) => boolean);
  enableMultiRowSelection?: boolean;
  initialRowSelection?: RowSelectionState;
  rowSelectionState?: RowSelectionState;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  getRowId?: (originalRow: TData, index: number, parent?: any) => string;
  enableRowSelectionColumn?: boolean;
  showSelectionCount?: boolean;
}>();

const emit = defineEmits([
  'update:sorting',
  'update:global-filter',
  'update:pagination',
  'update:rowSelection',
]);

const features = tableFeatures({
  rowSortingFeature,
  sortedRowModel: createSortedRowModel(),
  columnFilteringFeature,
  filteredRowModel: createFilteredRowModel(),
  globalFilteringFeature,
  filterFns: {
    includesString: filterFn_includesString,
  },
  rowPaginationFeature,
  paginatedRowModel: createPaginatedRowModel(),
  rowSelectionFeature,
  columnVisibilityFeature,
});

// Use external sorting if provided, otherwise use initialSort or empty array
const sorting = ref<SortingState>(props.externalSorting || props.initialSort || []);
const globalFilter = ref(props.globalFilter || '');
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref<RowSelectionState>(props.rowSelectionState || props.initialRowSelection || {});

/**
 * Local pagination state. Deliberately NOT named `pagination`: a setup binding of that name
 * shadows the `pagination` prop inside the template, which silently made the
 * `v-if="pagination === true"` on the pagination controls compare a state object to `true` —
 * so client-side pagination controls never rendered.
 */
const paginationState = ref<PaginationState>({
  pageIndex: props.externalPagination?.pageIndex || 0,
  pageSize: props.externalPagination?.pageSize || props.pageSize || 10,
});

// Ref to the scrollable table container
const tableScrollRef = ref<HTMLDivElement>();

// Track mount state to avoid scrolling on initial load
const isTableMounted = ref(false);
onMounted(() => {
  isTableMounted.value = true;
});

// Scroll table to top when page changes
const scrollTableToTop = () => {
  nextTick(() => {
    tableScrollRef.value?.scrollTo({ top: 0, behavior: 'smooth' });
  });
};

watch(paginationState, (newVal, oldVal) => {
  if (isTableMounted.value && oldVal && newVal.pageIndex !== oldVal.pageIndex) {
    scrollTableToTop();
  }
});

// Watch for external sorting changes
watch(() => props.externalSorting, (newVal) => {
  if (newVal && JSON.stringify(newVal) !== JSON.stringify(sorting.value)) {
    sorting.value = newVal;
  }
}, { immediate: true });

// Watch for external pagination changes
watch(() => props.externalPagination, (newVal) => {
  if (newVal && (
    newVal.pageIndex !== paginationState.value.pageIndex
    || newVal.pageSize !== paginationState.value.pageSize
  )) {
    paginationState.value = newVal;
  }
}, { immediate: true });

// Watch for external global filter changes
watch(() => props.globalFilter, (newVal) => {
  if (newVal !== undefined && newVal !== globalFilter.value) {
    globalFilter.value = newVal;
  }
}, { immediate: true });

// Watch for external row selection changes
watch(() => props.rowSelectionState, (newVal) => {
  if (newVal && JSON.stringify(newVal) !== JSON.stringify(rowSelection.value)) {
    rowSelection.value = newVal;
  }
}, { immediate: true });

// Generate a selection column definition
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const selectionColumn = computed<ColumnDef<typeof features, TData, any>>(() => {
  return {
    id: 'select',
    header: ({ table }) => {
      const allSelected = table.getIsAllPageRowsSelected();
      const someSelected = table.getIsSomePageRowsSelected() && !allSelected;
      return (
        <div class="px-1">
          <Checkbox
            modelValue={allSelected ? true : someSelected ? 'indeterminate' : false}
            onUpdate:modelValue={value => table.toggleAllPageRowsSelected(!!value)}
            aria-label={$t('tables.select_all')}
          />
        </div>
      );
    },
    cell: ({ row }) => (
      <div class="px-1">
        <Checkbox
          modelValue={row.getIsSelected()}
          onUpdate:modelValue={value => row.toggleSelected(!!value)}
          aria-label={$t('tables.select_row')}
          disabled={!row.getCanSelect()}
        />
      </div>
    ),
    enableSorting: false,
    enableHiding: false,
    size: 40,
  };
});

// Merge selection column with user columns if selection is enabled
const tableColumns = computed(() => {
  if (props.enableRowSelection && props.enableRowSelectionColumn) {
    return [selectionColumn.value, ...props.columns];
  }
  return props.columns;
});

const table = useTable<typeof features, TData>({
  features,
  get data() { return props.data; },
  get columns() { return tableColumns.value; },
  manualSorting: props.manualSorting || false,
  manualFiltering: props.manualFiltering || false,
  manualPagination: props.manualPagination || false,
  pageCount: props.pageCount,
  rowCount: props.rowCount,
  enableRowSelection: typeof props.enableRowSelection === 'function'
    ? props.enableRowSelection
    : props.enableRowSelection,
  enableMultiRowSelection: props.enableMultiRowSelection !== false,
  getRowId: props.getRowId,
  globalFilterFn: 'includesString',
  state: {
    get sorting() { return sorting.value; },
    get globalFilter() { return globalFilter.value; },
    get columnVisibility() { return columnVisibility.value; },
    get pagination() { return paginationState.value; },
    get rowSelection() { return rowSelection.value; },
  },
  onSortingChange: (updaterOrValue) => {
    if (typeof updaterOrValue === 'function') {
      sorting.value = updaterOrValue(sorting.value);
    }
    else {
      sorting.value = updaterOrValue;
    }
    emit('update:sorting', sorting.value);
  },
  onPaginationChange: (updaterOrValue) => {
    if (typeof updaterOrValue === 'function') {
      paginationState.value = updaterOrValue(paginationState.value);
    }
    else {
      paginationState.value = updaterOrValue;
    }
    emit('update:pagination', paginationState.value);
  },
  onColumnVisibilityChange: (updaterOrValue) => {
    if (typeof updaterOrValue === 'function') {
      columnVisibility.value = updaterOrValue(columnVisibility.value);
    }
    else {
      columnVisibility.value = updaterOrValue;
    }
  },
  onGlobalFilterChange: (value) => {
    globalFilter.value = value;
    emit('update:global-filter', value);
  },
  onRowSelectionChange: (updaterOrValue) => {
    if (typeof updaterOrValue === 'function') {
      rowSelection.value = updaterOrValue(rowSelection.value);
    }
    else {
      rowSelection.value = updaterOrValue;
    }
    emit('update:rowSelection', rowSelection.value);
  },
});

// Backward compatibility aliases for v8
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(table as any).getPrePaginationRowModel = table.getPrePaginatedRowModel;
// eslint-disable-next-line @typescript-eslint/no-explicit-any
(table as any).getState = () => table.store.get();

watch(() => props.pageSize, (newVal) => {
  if (newVal && !props.manualPagination) {
    table.setPageSize(newVal);
  }
}, { immediate: true });

const handleGlobalFilter = (e: Event) => {
  const target = e.target as HTMLInputElement;
  globalFilter.value = target.value;
  emit('update:global-filter', target.value);
};

// Selection helper functions
const getSelectedRows = () => {
  return table.getSelectedRowModel().rows;
};

const clearRowSelection = () => {
  table.resetRowSelection();
};

// Expose methods to parent components
defineExpose({
  table,
  getSelectedRows,
  clearRowSelection,
  rowSelection,
});
</script>
