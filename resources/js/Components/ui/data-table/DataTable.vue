<script setup lang="tsx" generic="TData, TValue">
import type { ColumnDef, SortingState, VisibilityState, PaginationState, RowSelectionState } from '@tanstack/vue-table'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'

import {
  FlexRender,
  getCoreRowModel,
  getSortedRowModel,
  getPaginationRowModel,
  getFilteredRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { TableEmpty } from '@/Components/ui/table';
import { ref, watch, computed } from 'vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { 
  ChevronDownIcon, 
  SlidersIcon, 
  ChevronLeftIcon, 
  ChevronRightIcon,
  ChevronsRightIcon,
  ChevronsLeftIcon 
} from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';
import { 
  Pagination, 
  PaginationContent, 
  PaginationItem, 
  PaginationNext, 
  PaginationPrevious,
  PaginationFirst,
  PaginationLast,
  PaginationEllipsis
} from '@/Components/ui/pagination';

const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
  rowClassName?: (row: TData) => string
  pageSize?: number
  pagination?: boolean
  emptyMessage?: string
  enableFiltering?: boolean
  enableColumnVisibility?: boolean
  initialSort?: { id: string; desc: boolean }[]
  globalFilter?: string
  manualSorting?: boolean
  manualFiltering?: boolean
  manualPagination?: boolean
  externalSorting?: SortingState
  externalPagination?: PaginationState
  rowCount?: number
  pageCount?: number
  // Row selection props
  enableRowSelection?: boolean
  enableMultiRowSelection?: boolean
  initialRowSelection?: RowSelectionState
  rowSelectionState?: RowSelectionState
  getRowId?: (originalRow: TData, index: number, parent?: any) => string
  enableRowSelectionColumn?: boolean
}>()

const emit = defineEmits([
  'update:sorting', 
  'update:global-filter',
  'update:pagination',
  'update:rowSelection'
])

// Use external sorting if provided, otherwise use initialSort or empty array
const sorting = ref<SortingState>(props.externalSorting || props.initialSort || [])
// TODO: doesn't work for client-side tables
const globalFilter = ref(props.globalFilter || '')
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref<RowSelectionState>(props.rowSelectionState || props.initialRowSelection || {})

// Create local pagination state that can be controlled externally
const pagination = ref<PaginationState>({
  pageIndex: props.externalPagination?.pageIndex || 0,
  pageSize: props.externalPagination?.pageSize || props.pageSize || 10
})

// Watch for external sorting changes
watch(() => props.externalSorting, (newVal) => {
  if (newVal && JSON.stringify(newVal) !== JSON.stringify(sorting.value)) {
    sorting.value = newVal
  }
}, { immediate: true })

// Watch for external pagination changes
watch(() => props.externalPagination, (newVal) => {
  if (newVal && (
    newVal.pageIndex !== pagination.value.pageIndex || 
    newVal.pageSize !== pagination.value.pageSize
  )) {
    pagination.value = newVal
  }
}, { immediate: true })

// Watch for external global filter changes
watch(() => props.globalFilter, (newVal) => {
  if (newVal !== undefined && newVal !== globalFilter.value) {
    globalFilter.value = newVal
  }
}, { immediate: true })

// Watch for external row selection changes
watch(() => props.rowSelectionState, (newVal) => {
  if (newVal && JSON.stringify(newVal) !== JSON.stringify(rowSelection.value)) {
    rowSelection.value = newVal
  }
}, { immediate: true })

// Generate a selection column definition
const selectionColumn = computed<ColumnDef<TData, any>>(() => {
  return {
    id: 'select',
    header: ({ table }) => (
      <div class="px-1">
        <Checkbox 
          checked={table.getIsAllPageRowsSelected()}
          onUpdate:checked={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label={$t('Select all')}
          class="data-[state=checked]:bg-primary"
        />
      </div>
    ),
    cell: ({ row }) => (
      <div class="px-1">
        <Checkbox 
          checked={row.getIsSelected()}
          onUpdate:checked={(value) => row.toggleSelected(!!value)}
          aria-label={$t('Select row')}
          disabled={!row.getCanSelect()}
          class="data-[state=checked]:bg-primary"
        />
      </div>
    ),
    enableSorting: false,
    enableHiding: false,
    size: 40,
  }
})

// Merge selection column with user columns if selection is enabled
const tableColumns = computed(() => {
  if (props.enableRowSelection && props.enableRowSelectionColumn) {
    return [selectionColumn.value, ...props.columns]
  }
  return props.columns
})

// Table options
const tableOptions = computed(() => {
  const options = {
    get data() { return props.data },
    get columns() { return tableColumns.value },
    getCoreRowModel: getCoreRowModel(),
    
    onSortingChange: (updaterOrValue) => {
      if (typeof updaterOrValue === 'function') {
        sorting.value = updaterOrValue(sorting.value)
      } else {
        sorting.value = updaterOrValue
      }
      emit('update:sorting', sorting.value)
    },
    
    onPaginationChange: (updaterOrValue) => {
      if (typeof updaterOrValue === 'function') {
        pagination.value = updaterOrValue(pagination.value)
      } else {
        pagination.value = updaterOrValue
      }
      emit('update:pagination', pagination.value)
    },
    
    onColumnVisibilityChange: (updaterOrValue) => {
      if (typeof updaterOrValue === 'function') {
        columnVisibility.value = updaterOrValue(columnVisibility.value)
      } else {
        columnVisibility.value = updaterOrValue
      }
    },
    
    onGlobalFilterChange: (value) => {
      globalFilter.value = value
      emit('update:global-filter', value)
    },

    onRowSelectionChange: (updaterOrValue) => {
      if (typeof updaterOrValue === 'function') {
        rowSelection.value = updaterOrValue(rowSelection.value)
      } else {
        rowSelection.value = updaterOrValue
      }
      emit('update:rowSelection', rowSelection.value)
    },
    
    state: {
      get sorting() { return sorting.value },
      get globalFilter() { return globalFilter.value },
      get columnVisibility() { return columnVisibility.value },
      get pagination() { return pagination.value },
      get rowSelection() { return rowSelection.value },
    },

    // Row selection options
    enableRowSelection: props.enableRowSelection,
    enableMultiRowSelection: props.enableMultiRowSelection !== false, // Default to true if not specified
    getRowId: props.getRowId, // Custom row ID function if provided

    // Manual flags for server-side operations
    manualSorting: props.manualSorting || false,
    manualFiltering: props.manualFiltering || false,
    manualPagination: props.manualPagination || false,
    
    // Total counts for pagination
    pageCount: props.pageCount,
    rowCount: props.rowCount,
    
    // Define global filter function - default is text-based filtering
    globalFilterFn: 'includesString',
  }
  
  // Include getFilteredRowModel only when not using manual filtering
  if (!props.manualFiltering) {
    options.getFilteredRowModel = getFilteredRowModel()
  }
  
  // Only include getSortedRowModel when NOT using manual sorting
  if (!props.manualSorting) {
    options.getSortedRowModel = getSortedRowModel()
  }
  
  // Only include pagination when needed and not manual
  if (props.pagination && !props.manualPagination) {
    options.getPaginationRowModel = getPaginationRowModel()
  }
  
  return options
})

const table = useVueTable(tableOptions.value)

watch(() => props.pageSize, (newVal) => {
  if (newVal && !props.manualPagination) {
    table.setPageSize(newVal)
  }
}, { immediate: true })

const handleGlobalFilter = (e: Event) => {
  const target = e.target as HTMLInputElement
  globalFilter.value = target.value
  emit('update:global-filter', target.value)
}

// Selection helper functions
const getSelectedRows = () => {
  return table.getSelectedRowModel().rows
}

const clearRowSelection = () => {
  table.resetRowSelection()
}

// Expose methods to parent components
defineExpose({
  table,
  getSelectedRows,
  clearRowSelection,
  rowSelection,
})
</script>

<template>
  <div class="space-y-4">
    <div v-if="enableFiltering || enableColumnVisibility || (enableRowSelection && table.getSelectedRowModel().rows.length > 0)" class="flex flex-wrap items-center justify-between gap-2 w-full">
      <div class="flex flex-wrap items-center gap-2">
        <!-- Show selection count when rows are selected -->
        <div v-if="enableRowSelection && table.getSelectedRowModel().rows.length > 0" class="bg-muted rounded-md px-2 py-1 text-sm flex items-center">
          {{ $t('Selected') }}: {{ table.getSelectedRowModel().rows.length }}
          <Button variant="ghost" size="sm" class="ml-2 h-6 w-6 p-0" @click="table.resetRowSelection()">
            <span class="sr-only">{{ $t('Clear selection') }}</span>
            &times;
          </Button>
        </div>
      
        <div v-if="enableFiltering && !manualFiltering" class="flex items-center gap-2">
          <Input 
            class="max-w-sm"
            :placeholder="$t('Search...')"
            :value="globalFilter"
            @input="handleGlobalFilter"
          />
        </div>
        
        <slot name="filters"></slot>
      </div>
      
      <div class="flex items-center gap-2">
        <slot name="actions"></slot>
      
        <DropdownMenu v-if="enableColumnVisibility">
          <DropdownMenuTrigger asChild>
            <Button variant="outline" size="sm" class="ml-auto">
              <SlidersIcon class="mr-2 h-4 w-4" />
              {{ $t('Columns') }}
              <ChevronDownIcon class="ml-2 h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="bg-popover border border-border bg-white dark:bg-zinc-800">
            <DropdownMenuCheckboxItem
              v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
              :key="column.id"
              :modelValue="column.getIsVisible()"
              @click="column.toggleVisibility()"
              @select.prevent
            >
              {{ column.id }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
    
    <div class="border rounded-md">
      <div class="w-full overflow-auto">
        <Table class="w-full table-fixed">
          <TableHeader>
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
              <TableHead 
                v-for="header in headerGroup.headers" 
                :key="header.id" 
                :class="{ 'cursor-pointer select-none': header.column.getCanSort() }" 
                @click="header.column.getToggleSortingHandler()?.({})"
                :style="{ width: header.column.columnDef.size ? `${header.column.columnDef.size}px` : 'auto' }"
              >
                <div class="flex items-center space-x-1">
                  <FlexRender
                    v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                    :props="header.getContext()"
                  />
                  <!-- Improved sorting indicators with proper styling -->
                  <span 
                    v-if="header.column.getIsSorted()" 
                    class="text-xs ml-1"
                    :class="header.column.getIsSorted() === 'desc' ? 'text-muted-foreground' : ''"
                  >
                    {{ header.column.getIsSorted() === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <template v-if="table.getRowModel().rows?.length">
              <TableRow
                v-for="row in table.getRowModel().rows" :key="row.id"
                :data-state="row.getIsSelected() ? 'selected' : undefined"
                :class="[
                  rowClassName ? rowClassName(row.original) : '',
                  row.getIsSelected() ? 'bg-muted/50' : ''
                ]"
              >
                <TableCell 
                  v-for="cell in row.getVisibleCells()" 
                  :key="cell.id"
                  :style="{ width: cell.column.columnDef.size ? `${cell.column.columnDef.size}px` : 'auto' }"
                >
                  <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                </TableCell>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center">
                  <slot name="empty">
                    {{ emptyMessage || $t('No results.') }}
                  </slot>
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>

      <!-- Use custom pagination slot if available (for server-side mode) -->
      <slot name="pagination">
        <div v-if="pagination && table.getPageCount() > 0" class="flex flex-wrap items-center justify-between gap-2 p-4 border-t">
          <Pagination 
            :items-per-page="table.getState().pagination.pageSize"
            :total="table.getPrePaginationRowModel().rows.length"
          >
            <PaginationContent>
              <PaginationItem :value="1">
                <PaginationFirst :disabled="!table.getCanPreviousPage()" @click="table.setPageIndex(0)">
                  <ChevronsLeftIcon class="h-4 w-4" />
                  <span class="sr-only">{{ $t('First page') }}</span>
                </PaginationFirst>
              </PaginationItem>
              <PaginationItem :value="table.getState().pagination.pageIndex">
                <PaginationPrevious :disabled="!table.getCanPreviousPage()" @click="table.previousPage()">
                  <ChevronLeftIcon class="h-4 w-4" />
                  <span class="sr-only">{{ $t('Previous page') }}</span>
                </PaginationPrevious>
              </PaginationItem>
              
              <PaginationItem 
                v-for="page in table.getPageCount()"
                :key="page"
                :value="page"
                :is-active="table.getState().pagination.pageIndex === page - 1"
                @click="table.setPageIndex(page - 1)"
              >
                {{ page }}
              </PaginationItem>
              
              <PaginationItem :value="table.getState().pagination.pageIndex + 2">
                <PaginationNext :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                  <span class="sr-only">{{ $t('Next page') }}</span>
                  <ChevronRightIcon class="h-4 w-4" />
                </PaginationNext>
              </PaginationItem>
              <PaginationItem :value="table.getPageCount()">
                <PaginationLast :disabled="!table.getCanNextPage()" @click="table.setPageIndex(table.getPageCount() - 1)">
                  <span class="sr-only">{{ $t('Last page') }}</span>
                  <ChevronsRightIcon class="h-4 w-4" />
                </PaginationLast>
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      </slot>
    </div>
  </div>
</template>
