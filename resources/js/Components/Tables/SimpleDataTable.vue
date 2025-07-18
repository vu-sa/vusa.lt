<template>
  <!-- 
    SimpleDataTable.vue - Lightweight client-side table
    Use this for small datasets that can be fully loaded in the browser
  -->
  <div class="space-y-4">
    <!-- Search and basic filters -->
    <div v-if="enableFiltering" class="flex gap-2">
      <Input 
        v-model="searchText" 
        :placeholder="`${$t('Search')}...`" 
        class="max-w-sm" 
      />
      <slot name="filters" />
    </div>

    <!-- Table -->
    <DataTableProvider 
      ref="dataTableProviderRef"
      :columns 
      :data 
      :is-server-side="false" 
      :page-size 
      :enable-pagination 
      :row-class-name
      :empty-message 
      :enable-filtering="false"
      :enable-column-visibility 
      :manual-filtering="false"
      :global-filter="searchText"
    >
      <template #empty>
        <slot name="empty">
          <div class="flex flex-col items-center justify-center py-8 text-center">
            <component :is="emptyIcon || CircleIcon" class="h-12 w-12 text-muted-foreground mb-4" />
            <h3 class="text-lg font-medium mb-2">
{{ emptyMessage || $t('No data found') }}
</h3>
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
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { CircleIcon } from 'lucide-vue-next';

import DataTableProvider from '../ui/data-table/DataTableProvider.vue';
import { Input } from '@/Components/ui/input';
import type { SimpleTableProps } from '@/Types/TableConfigTypes';

// Props using the simplified interface
const props = withDefaults(defineProps<SimpleTableProps<TData>>(), {
  enablePagination: true,
  pageSize: 10,
  enableFiltering: true,
  enableColumnVisibility: false,
});

// Component state
const searchText = ref('');

// Reference to the DataTableProvider
const dataTableProviderRef = ref<InstanceType<typeof DataTableProvider>>();

// Expose public methods for external control
const clearSearch = () => {
  searchText.value = '';
};

const getFilteredData = () => {
  return dataTableProviderRef.value?.getFilteredData() || [];
};

defineExpose({
  clearSearch,
  getFilteredData,
  get searchText() { return searchText.value; },
});
</script>
