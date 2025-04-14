<template>
  <div>
    <!-- Actual data table -->
    <DataTableProvider 
      :columns="columns" 
      :data="data" 
      :is-server-side="true" 
      :total-items="totalCount"
      :server-pagination="serverPagination" 
      :server-sorting="sorting"
      :page-size="pageSize" 
      :enable-pagination="true" 
      :row-class-name="rowClassName"
      :empty-message="emptyMessage" 
      :enable-filtering="enableFiltering"
      :enable-column-visibility="enableColumnVisibility" 
      :global-filter="searchText"
      @page-change="handlePageChange" 
      @update:sorting="handleSortChange" 
      @update:global-filter="updateSearchText"
    >
      <template #filters>
        <Input 
          v-model="searchText" 
          :placeholder="$t('Search...')" 
          class="max-w-sm" 
          @keydown.enter="handleSearch" 
        />
        <slot name="filters" />
      </template>

      <template #actions>
        <slot name="actions" />
      </template>

      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center py-6 text-center">
            <p class="text-sm text-muted-foreground">{{ emptyMessage || $t('No results found.') }}</p>
          </div>
        </slot>
      </template>
    </DataTableProvider>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { ref, watch, computed, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';

import DataTableProvider from '../ui/data-table/DataTableProvider.vue';
import { Input } from '@/Components/ui/input';

// Define the props with TypeScript generics support
const props = defineProps<{
  // Inertia integration
  modelName: string,
  reloadOnly?: boolean,

  // Data display
  columns: ColumnDef<TData, any>[],
  data: TData[],

  // Pagination
  totalCount: number,
  initialPage?: number,
  pageSize?: number,

  // Options
  rowClassName?: (row: TData) => string,
  emptyMessage?: string,
  enableFiltering?: boolean,
  enableColumnVisibility?: boolean,
  showDeleted?: boolean,

  // Initial state
  initialSorting?: SortingState,
  initialFilters?: Record<string, unknown>
}>();

const emit = defineEmits(['dataLoaded']);

// Component state
const searchText = ref('');
const pageIndex = ref(props.initialPage ? props.initialPage - 1 : 0);
const sorting = ref<SortingState>(props.initialSorting || []);
const filters = ref<Record<string, unknown>>({
  ...props.initialFilters || {},
  showDeleted: props.showDeleted || false
});
const pageSize = computed(() => props.pageSize || 10);
const loading = ref(false);
const isInternalFilterUpdate = ref(false);

// Server pagination for UI
const serverPagination = computed(() => ({
  pageIndex: pageIndex.value,
  pageSize: pageSize.value
}));

// Debounce function for search
const debouncedReload = debounce((resetPage = false) => {
  if (resetPage) {
    pageIndex.value = 0;
  }
  reloadData();
}, 300);

// Event handlers
const handleSearch = () => {
  pageIndex.value = 0; // Go back to first page on search
  reloadData();
};

const updateSearchText = (text: string) => {
  searchText.value = text;
  pageIndex.value = 0; // Go back to first page on search change
  debouncedReload(true);
};

const handleSortChange = (newSorting: SortingState) => {
  sorting.value = newSorting;
  reloadData(); // Reload data with new sorting
};

const handlePageChange = (newPageIndex: number) => {
  pageIndex.value = newPageIndex;
  reloadData(); // Reload data with new page
};

const updateFilter = (key: string, value: any) => {
  filters.value[key] = value;
  pageIndex.value = 0; // Reset to first page when filter changes
  reloadData();
};

// Encode table state for server requests
const encodeTableState = () => {
  const state: Record<string, any> = {
    page: pageIndex.value + 1, // Convert to 1-based indexing for backend
    per_page: pageSize.value
  };
  
  // Add sorting if present
  if (sorting.value.length > 0) {
    state.sorting = JSON.stringify(sorting.value);
  }
  
  // Add filters if present
  if (Object.keys(filters.value).length > 0) {
    state.filters = JSON.stringify(filters.value);
  }
  
  // Add search text if present
  if (searchText.value) {
    state.search = searchText.value;
  }
  
  return state;
};

// Load data from server
const reloadData = (page?: number) => {
  if (page !== undefined) {
    pageIndex.value = page;
  }
  
  loading.value = true;
  const state = encodeTableState();
  
  const options = {
    data: state,
    preserveScroll: true,
    preserveState: true,
    onSuccess: (response) => {
      const responseData = response.props[props.modelName];
      loading.value = false;
      
      // Emit data loaded event with context
      emit('dataLoaded', {
        page: pageIndex.value,
        sorting: sorting.value,
        filters: filters.value,
        data: responseData
      });
    },
    onError: (errors) => {
      console.error('Error loading data:', errors);
      loading.value = false;
    }
  };
  
  if (props.reloadOnly) {
    router.reload(options);
  } else {
    router.visit(window.location.pathname, options);
  }
};

// Watch for props changes
watch(() => props.showDeleted, (newValue) => {
  if (newValue !== filters.value.showDeleted) {
    filters.value.showDeleted = !!newValue;
    pageIndex.value = 0; // Reset to first page on filter change
    reloadData();
  }
}, { immediate: true });

watch(() => props.initialFilters, (newValue) => {
  // Skip if this is an internal update
  if (isInternalFilterUpdate.value) {
    isInternalFilterUpdate.value = false;
    return;
  }
  
  if (newValue) {
    // Improved comparison that handles arrays properly
    const hasChanges = Object.entries(newValue).some(([key, value]) => {
      const currentValue = filters.value[key];
      
      // Handle arrays specifically
      if (Array.isArray(value) && Array.isArray(currentValue)) {
        // Check if arrays have different length
        if (value.length !== currentValue.length) return true;
        
        // Compare each element
        return value.some((item, i) => item !== currentValue[i]);
      }
      
      // Handle objects specifically
      if (
        value !== null && 
        typeof value === 'object' && 
        currentValue !== null && 
        typeof currentValue === 'object'
      ) {
        return JSON.stringify(value) !== JSON.stringify(currentValue);
      }
      
      // Simple comparison for primitives
      return currentValue !== value;
    });
    
    if (hasChanges) {
      // Update filters keeping existing ones
      filters.value = {
        ...filters.value,
        ...newValue
      };
      reloadData();
    }
  }
}, { deep: true });

watch(() => props.initialSorting, (newValue) => {
  if (newValue && JSON.stringify(newValue) !== JSON.stringify(sorting.value)) {
    sorting.value = newValue;
    reloadData();
  }
}, { deep: true });

// Expose public methods and computed properties
defineExpose({
  reloadData,
  currentPage: computed(() => pageIndex.value),
  sorting: computed(() => sorting.value),
  filters: computed(() => filters.value),
  updateFilter
});
</script>
