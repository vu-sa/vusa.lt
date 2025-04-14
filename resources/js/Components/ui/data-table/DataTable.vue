<script setup lang="ts" generic="TData, TValue">
import type { ColumnDef, SortingState, VisibilityState, PaginationState } from '@tanstack/vue-table'
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
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { ChevronDownIcon, SlidersIcon } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

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
}>()

const emit = defineEmits([
  'update:sorting', 
  'update:global-filter',
  'update:pagination'
])

// Use external sorting if provided, otherwise use initialSort or empty array
const sorting = ref<SortingState>(props.externalSorting || props.initialSort || [])
const globalFilter = ref(props.globalFilter || '')
const columnVisibility = ref<VisibilityState>({})

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

// Table options
const tableOptions = computed(() => {
  const options = {
    get data() { return props.data },
    get columns() { return props.columns },
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
    
    state: {
      get sorting() { return sorting.value },
      get globalFilter() { return globalFilter.value },
      get columnVisibility() { return columnVisibility.value },
      get pagination() { return pagination.value },
    },

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
</script>

<template>
  <div class="space-y-4">
    <div v-if="enableFiltering || enableColumnVisibility" class="flex flex-wrap items-center justify-between gap-2 w-fit">
      <div v-if="enableFiltering && !manualFiltering" class="flex items-center gap-2">
        <Input 
          class="max-w-sm"
          :placeholder="$t('Search...')"
          :value="globalFilter"
          @input="handleGlobalFilter"
        />
      </div>
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
          >
            {{ column.id }}
          </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
      </DropdownMenu>
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
                :class="rowClassName ? rowClassName(row.original) : ''"
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
          <div class="flex-1 text-sm text-muted-foreground whitespace-nowrap">
            {{ $t('Showing') }} 
            <strong>{{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}</strong>
            {{ $t('to') }} 
            <strong>{{ Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, 
              manualPagination && rowCount ? rowCount : props.data.length) }}</strong>
            {{ $t('of') }} 
            <strong>{{ manualPagination && rowCount ? rowCount : props.data.length }}</strong>
            {{ $t('results') }}
          </div>
          <div class="flex items-center space-x-2 flex-wrap">
            <Button
              variant="outline"
              size="sm"
              class="h-8"
              :disabled="!table.getCanPreviousPage()"
              @click="table.previousPage()"
            >
              {{ $t('Previous') }}
            </Button>
            <div class="flex items-center justify-center text-sm font-medium">
              {{ $t('Page') }} {{ table.getState().pagination.pageIndex + 1 }} {{ $t('of') }} {{ table.getPageCount() }}
            </div>
            <Button
              variant="outline"
              size="sm"
              class="h-8"
              :disabled="!table.getCanNextPage()"
              @click="table.nextPage()"
            >
              {{ $t('Next') }}
            </Button>
          </div>
        </div>
      </slot>
    </div>
  </div>
</template>