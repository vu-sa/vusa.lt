<template>
  <div class="space-y-4">
    <!-- Table header with filters and actions -->
    <div v-if="$slots.filters || $slots.actions" class="flex flex-wrap items-center justify-between gap-2">
      <div v-if="$slots.filters" class="flex flex-wrap items-center gap-2">
        <slot name="filters"></slot>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
        <slot name="actions"></slot>
      </div>
    </div>
    
    <!-- Client-side table -->
    <DataTable
      v-if="!isServerSide"
      ref="dataTableRef"
      :columns="columns"
      :data="data"
      :row-class-name="rowClassName"
      :page-size="pageSize"
      :pagination="enablePagination"
      :empty-message="emptyMessage"
      :enable-filtering="enableFiltering"
      :enable-column-visibility="enableColumnVisibility"
      :initial-sort="clientSorting"
      :global-filter="globalFilter"
      :manual-sorting="false"
      :enable-row-selection="enableRowSelection"
      :enable-multi-row-selection="enableMultiRowSelection"
      :row-selection-state="rowSelectionState"
      :initial-row-selection="initialRowSelection"
      :get-row-id="getRowId"
      :enable-row-selection-column="enableRowSelectionColumn"
      @update:sorting="handleSortingChange"
      @update:global-filter="handleGlobalFilterChange"
      @update:rowSelection="handleRowSelectionChange"
    >
      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center py-6 text-center">
            <p class="text-sm text-muted-foreground">{{ emptyMessage || $t('No results found.') }}</p>
          </div>
        </slot>
      </template>
    </DataTable>
    
    <!-- Server side table with custom pagination -->
    <DataTable
      v-else
      ref="dataTableRef"
      :columns="columns"
      :data="data"
      :row-class-name="rowClassName"
      :enable-filtering="enableFiltering"
      :enable-column-visibility="enableColumnVisibility"
      :manual-sorting="true"
      :manual-filtering="true"
      :manual-pagination="true"
      :external-sorting="serverSorting"
      :external-pagination="serverPagination"
      :row-count="totalItems"
      :page-count="totalItems ? Math.ceil(totalItems / pageSize) : undefined"
      :pagination="true"
      :empty-message="emptyMessage"
      :global-filter="globalFilter"
      :enable-row-selection="enableRowSelection"
      :enable-multi-row-selection="enableMultiRowSelection"
      :row-selection-state="rowSelectionState"
      :initial-row-selection="initialRowSelection"
      :get-row-id="getRowId"
      :enable-row-selection-column="enableRowSelectionColumn"
      @update:sorting="handleSortingChange"
      @update:global-filter="handleGlobalFilterChange"
      @update:pagination="handlePaginationChange"
      @update:rowSelection="handleRowSelectionChange"
    >
      <template #pagination>
        <!-- Server-side pagination -->
        <div v-if="enablePagination && isServerSide" class="flex flex-wrap items-center justify-between gap-2 p-4 border-t">
          <!-- Show pagination controls when there are results -->
          <template v-if="totalItems > 0">
            <div class="text-sm text-muted-foreground">
              {{ $t('Showing') }}
              <strong>{{ (serverPagination?.pageIndex || 0) * pageSize + 1 }}</strong>
              {{ $t('to') }}
              <strong>{{ Math.min((serverPagination?.pageIndex || 0) * pageSize + pageSize, totalItems) }}</strong>
              {{ $t('of') }}
              <strong>{{ totalItems }}</strong>
              {{ $t('results') }}
            </div>
            <Pagination 
              v-slot="{ page }"
              :items-per-page="pageSize"
              :total="totalItems" 
              :default-page="(serverPagination?.pageIndex || 0) + 1"
              @update:page="(newPage: number) => emit('page-change', newPage - 1)"
            >
              <PaginationContent>
                <PaginationPrevious />
                
                <div class="flex items-center text-sm font-medium px-4">
                  {{ $t('Page') }} {{ page }} {{ $t('of') }} {{ Math.ceil(totalItems / pageSize) }}
                </div>
                
                <PaginationNext />
              </PaginationContent>
            </Pagination>
          </template>
          
          <!-- Show empty state when no results -->
          <template v-else>
            <div class="text-sm text-muted-foreground">
              {{ $t('No results found') }}
            </div>
          </template>
        </div>
      </template>
      
      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center py-6 text-center">
            <p class="text-sm text-muted-foreground">{{ emptyMessage || $t('No results found.') }}</p>
          </div>
        </slot>
      </template>
    </DataTable>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { computed, ref, watch, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState, PaginationState, RowSelectionState } from '@tanstack/vue-table';

import DataTable from './DataTable.vue';
import { Button } from '@/Components/ui/button';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/Components/ui/pagination';

const props = defineProps<{
  // Data props
  columns: ColumnDef<TData, any>[],
  data: TData[],
  
  // Optional customization
  rowClassName?: (row: TData) => string,
  emptyMessage?: string,
  
  // Pagination config
  enablePagination?: boolean,
  pageSize?: number,
  
  // Feature flags
  enableFiltering?: boolean,
  enableColumnVisibility?: boolean,
  
  // Server-side related props
  isServerSide?: boolean,
  totalItems?: number,
  serverPagination?: {
    pageIndex: number,
    pageSize: number
  },
  serverSorting?: SortingState,
  globalFilter?: string,
  
  // Row selection props
  enableRowSelection?: boolean,
  enableMultiRowSelection?: boolean,
  enableRowSelectionColumn?: boolean,
  rowSelectionState?: RowSelectionState,
  initialRowSelection?: RowSelectionState,
  getRowId?: (originalRow: TData, index: number, parent?: any) => string
}>();

const emit = defineEmits<{
  'update:sorting': [sorting: SortingState],
  'update:global-filter': [filter: string],
  'page-change': [pageIndex: number],
  'update:rowSelection': [selection: RowSelectionState]
}>();

// Default values
const enablePagination = props.enablePagination ?? false;
const pageSize = props.pageSize ?? 10;

// Watch for global filter changes from parent
const currentGlobalFilter = ref(props.globalFilter || '');
watch(() => props.globalFilter, (newValue) => {
  if (newValue !== undefined && newValue !== currentGlobalFilter.value) {
    currentGlobalFilter.value = newValue;
  }
}, { immediate: true });

// Convert server sorting to client format if provided
const clientSorting = computed(() => {
  if (!props.serverSorting) return undefined;
  
  return props.serverSorting.map(item => ({
    id: item.id,
    desc: item.desc
  }));
});

// Pagination handling for server-side mode
const handlePrevPage = () => {
  if (props.serverPagination && props.serverPagination.pageIndex > 0) {
    emit('page-change', props.serverPagination.pageIndex - 1);
  }
};

const handleNextPage = () => {
  if (props.serverPagination && props.totalItems) {
    const maxPage = Math.ceil(props.totalItems / pageSize) - 1;
    if (props.serverPagination.pageIndex < maxPage) {
      emit('page-change', props.serverPagination.pageIndex + 1);
    }
  }
};

const handleSortingChange = (sorting: SortingState) => {
  emit('update:sorting', sorting);
};

const handleGlobalFilterChange = (filter: string) => {
  currentGlobalFilter.value = filter;
  emit('update:global-filter', filter);
};

const handlePaginationChange = (pagination: { pageIndex: number, pageSize: number }) => {
  emit('page-change', pagination.pageIndex);
};

// Create a ref to the DataTable component for forwarding methods
const dataTableRef = ref<InstanceType<typeof DataTable>>();

onMounted(() => {
  // Initial handling if needed
});

// Watch for changes to row selection state and emit events
watch(() => dataTableRef.value?.rowSelection, (newVal) => {
  if (newVal && !props.isServerSide) {
    emit('update:rowSelection', newVal);
  }
}, { deep: true });

// Handle row selection changes
const handleRowSelectionChange = (selection: RowSelectionState) => {
  emit('update:rowSelection', selection);
};

// Expose methods from the DataTable to parent components
defineExpose({
  getSelectedRows: () => dataTableRef.value?.getSelectedRows(),
  clearRowSelection: () => dataTableRef.value?.clearRowSelection(),
  rowSelection: () => dataTableRef.value?.rowSelection,
});
</script>