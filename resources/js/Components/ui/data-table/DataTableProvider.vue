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
      :columns="columns"
      :data="data"
      :row-className="rowClassName"
      :page-size="pageSize"
      :pagination="enablePagination"
      :empty-message="emptyMessage"
      :enable-filtering="enableFiltering"
      :enable-column-visibility="enableColumnVisibility"
      :initial-sort="clientSorting"
      :global-filter="globalFilter"
      :manual-sorting="false"
      @update:sorting="handleSortingChange"
      @update:global-filter="handleGlobalFilterChange"
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
      :columns="columns"
      :data="data"
      :row-className="rowClassName"
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
      @update:sorting="handleSortingChange"
      @update:global-filter="handleGlobalFilterChange"
      @update:pagination="handlePaginationChange"
    >
      <template #pagination>
        <!-- Server-side pagination -->
        <div v-if="enablePagination && totalItems > 0" class="flex flex-wrap items-center justify-between gap-2 p-4 border-t">
          <div class="text-sm text-muted-foreground">
            {{ $t('Showing') }}
            <strong>{{ (serverPagination?.pageIndex || 0) * pageSize + 1 }}</strong>
            {{ $t('to') }}
            <strong>{{ Math.min((serverPagination?.pageIndex || 0) * pageSize + pageSize, totalItems) }}</strong>
            {{ $t('of') }}
            <strong>{{ totalItems }}</strong>
            {{ $t('results') }}
          </div>
          <div class="flex items-center space-x-2">
            <Button
              variant="outline"
              size="sm"
              :disabled="(serverPagination?.pageIndex || 0) === 0"
              @click="handlePrevPage"
            >
              {{ $t('Previous') }}
            </Button>
            <div class="flex items-center text-sm font-medium">
              {{ $t('Page') }} {{ (serverPagination?.pageIndex || 0) + 1 }} 
              {{ $t('of') }} {{ Math.ceil(totalItems / pageSize) }}
            </div>
            <Button
              variant="outline"
              size="sm"
              :disabled="(serverPagination?.pageIndex || 0) >= Math.ceil(totalItems / pageSize) - 1"
              @click="handleNextPage"
            >
              {{ $t('Next') }}
            </Button>
          </div>
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
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';

import DataTable from './DataTable.vue';
import { Button } from '@/Components/ui/button';

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
  globalFilter?: string
}>();

const emit = defineEmits<{
  'update:sorting': [sorting: SortingState],
  'update:global-filter': [filter: string],
  'page-change': [pageIndex: number]
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
</script>