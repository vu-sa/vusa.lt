<template>
  <!-- 
    SimpleServerTable.vue - Lightweight server-side table
    Use this for basic server-side tables without admin features or page wrapper
  -->
  <div class="space-y-4">
    <!-- Search and basic filters -->
    <div v-if="enableFiltering" class="flex gap-2">
      <Input 
        v-model="searchText" 
        :placeholder="`${$t('Search')}...`" 
        class="max-w-sm" 
        @keydown.enter="handleSearch" 
      />
      <Button @click="handleSearch" variant="outline">
        {{ $t('Search') }}
      </Button>
    </div>

    <!-- Table -->
    <DataTableProvider 
      ref="dataTableProviderRef"
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
      :enable-filtering="false"
      :enable-column-visibility="enableColumnVisibility" 
      :global-filter="searchText"
      :loading="loading"
      @page-change="handlePageChange" 
      @update:sorting="handleSortChange" 
    >
      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center py-8 text-center">
            <component :is="emptyIcon || CircleIcon" class="h-12 w-12 text-muted-foreground mb-4" />
            <h3 class="text-lg font-medium mb-2">{{ emptyMessage || $t('No data found') }}</h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('Try adjusting your search or filters') }}
            </p>
          </div>
        </slot>
      </template>
    </DataTableProvider>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { ref, computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { CircleIcon } from 'lucide-vue-next';

import DataTableProvider from '../ui/data-table/DataTableProvider.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

// Simplified props for basic use cases
const props = withDefaults(defineProps<{
  // Essential data
  modelName: string,
  data: TData[],
  columns: ColumnDef<TData, any>[],
  totalCount: number,

  // Basic options
  pageSize?: number,
  initialPage?: number,
  enableFiltering?: boolean,
  enableColumnVisibility?: boolean,
  
  // Customization
  rowClassName?: (row: TData) => string,
  emptyMessage?: string,
  emptyIcon?: any,
  
  // Inertia options
  reloadOnly?: boolean,
}>(), {
  pageSize: 10,
  initialPage: 1,
  enableFiltering: true,
  enableColumnVisibility: false,
  reloadOnly: false,
});

const emit = defineEmits(['dataLoaded']);

// Component state
const searchText = ref('');
const pageIndex = ref((props.initialPage || 1) - 1);
const sorting = ref<SortingState>([]);
const loading = ref(false);

// Server pagination for UI
const serverPagination = computed(() => ({
  pageIndex: pageIndex.value,
  pageSize: props.pageSize
}));

// Reference to the DataTableProvider
const dataTableProviderRef = ref<InstanceType<typeof DataTableProvider>>();

// Debounce function for search
const debouncedReload = debounce(() => {
  reloadData();
}, 300);

// Event handlers
const handleSearch = () => {
  pageIndex.value = 0; // Go back to first page on search
  reloadData();
};

const handleSortChange = (newSorting: SortingState) => {
  sorting.value = newSorting;
  reloadData();
};

const handlePageChange = (newPageIndex: number) => {
  pageIndex.value = newPageIndex;
  reloadData();
};

// Encode table state for server requests
const encodeTableState = () => {
  const state: Record<string, any> = {
    page: pageIndex.value + 1, // Convert to 1-based indexing for backend
    per_page: props.pageSize
  };
  
  // Add sorting if present
  if (sorting.value.length > 0) {
    state.sorting = JSON.stringify(sorting.value);
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
      
      // Emit data loaded event
      emit('dataLoaded', {
        page: pageIndex.value,
        sorting: sorting.value,
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

// Expose public methods
defineExpose({
  reloadData,
  get currentPage() { return pageIndex.value; },
  get sorting() { return sorting.value; },
});
</script>