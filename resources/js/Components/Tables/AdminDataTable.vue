<template>
  <div>

    <!-- Server data table -->
    <ServerDataTable
      ref="serverTableRef"
      :model-name="modelName"
      :columns="columns"
      :data="data"
      :total-count="totalCount"
      :initial-page="initialPage"
      :page-size="pageSize"
      :row-class-name="rowClassName"
      :empty-message="emptyMessage"
      :enable-filtering="enableFiltering"
      :enable-column-visibility="enableColumnVisibility"
      :show-deleted="showDeleted"
      :initial-sorting="initialSorting"
      :initial-filters="initialFilters"
      :enable-row-selection="enableRowSelection"
      :enable-multi-row-selection="enableMultiRowSelection"
      :enable-row-selection-column="enableRowSelectionColumn"
      :initial-row-selection="initialRowSelection"
      :get-row-id="getRowId"
      @data-loaded="handleDataLoaded"
      @update:rowSelection="handleSelectionChange"
    >
      <!-- Pass through slots -->
      <template #filters>
        <slot name="filters"></slot>
      </template>

      <template #actions>
        <slot name="actions"></slot>
      </template>

      <template #empty>
        <slot name="empty">
          <!-- Default empty state -->
          <!-- <EmptyState
            :title="$t(`No ${modelName} found`)"
            :description="$t(`There are no ${modelName} matching your criteria.`)"
            :icon="EmptyIcon"
          > -->
            <!-- Create button in empty state -->
            <!-- <Button v-if="canCreate && createRoute" :href="createRoute">
              {{ $t('Create') }} {{ singularModelName }}
            </Button>
          </EmptyState> -->
        </slot>
      </template>
    </ServerDataTable>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { ref, computed, watch } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { type ColumnDef, type SortingState, type RowSelectionState } from '@tanstack/vue-table';
import { PlusCircleIcon } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
// import EmptyState from '@/Components/ui/EmptyState.vue';

// Import your ServerDataTable component
import ServerDataTable from '@/Components/Tables/ServerDataTable.vue';

// Define props
const props = defineProps<{
  // Model information
  modelName: string;
  entityName?: string;
  pluralModelName?: string;
  singularModelName?: string;

  // Data
  data: TData[];
  columns: ColumnDef<TData, any>[];
  
  // Pagination
  totalCount: number;
  initialPage?: number;
  pageSize?: number;
  
  // Styling
  rowClassName?: (row: TData) => string;
  emptyMessage?: string;
  emptyIcon?: any;
  
  // Features
  enableFiltering?: boolean;
  enableColumnVisibility?: boolean;
  allowToggleDeleted?: boolean;
  canCreate?: boolean;
  createRoute?: string;
  
  // Initial state
  initialSorting?: SortingState;
  initialFilters?: Record<string, unknown>;
  
  // Row selection
  enableRowSelection?: boolean;
  enableMultiRowSelection?: boolean;
  enableRowSelectionColumn?: boolean;
  initialRowSelection?: RowSelectionState;
  getRowId?: (originalRow: TData, index: number, parent?: any) => string;
}>();

const emit = defineEmits(['dataLoaded', 'create', 'update:rowSelection', 'sorting-changed', 'page-changed', 'filter-changed']);

// Set up references
const serverTableRef = ref<InstanceType<typeof ServerDataTable> | null>(null);

// State
const showDeleted = ref(false);
const rowSelection = ref<RowSelectionState>(props.initialRowSelection || {});

// Computed properties
const EmptyIcon = props.emptyIcon || PlusCircleIcon;

const pluralModelName = computed(() => {
  if (props.pluralModelName) return props.pluralModelName;
  return $tChoice(`entities.${props.entityName || props.modelName}.model`, 2);
});

const singularModelName = computed(() => {
  if (props.singularModelName) return props.singularModelName;
  return $tChoice(`entities.${props.entityName || props.modelName}.model`, 1);
});

// Methods exposed to parent components
const reloadData = (page?: number) => {
  if (serverTableRef.value) {
    serverTableRef.value.reloadData(page);
  }
};

const updateFilter = (key: string, value: unknown) => {
  if (serverTableRef.value) {
    serverTableRef.value.updateFilter(key, value);
    emit('filter-changed', key, value);
  }
};

// Watch for deleted toggle changes
watch(() => showDeleted.value, () => {
  if (serverTableRef.value) {
    serverTableRef.value.reloadData(0); // Reset to first page when toggling deleted items
  }
});

// Row selection handlers
const handleSelectionChange = (selection: RowSelectionState) => {
  rowSelection.value = selection;
  emit('update:rowSelection', selection);
};

// Get selected rows for external usage
const getSelectedRows = () => {
  return serverTableRef.value?.getSelectedRows() || [];
};

// Clear row selection
const clearRowSelection = () => {
  if (serverTableRef.value) {
    serverTableRef.value.clearRowSelection();
    rowSelection.value = {};
    emit('update:rowSelection', {});
  }
};

// Handle data load
const handleDataLoaded = (data) => {
  emit('dataLoaded', data);
};

// Handle sorting change
const handleSortingChange = (sorting) => {
  emit('sorting-changed', sorting);
};

// Handle page change
const handlePageChange = (page) => {
  emit('page-changed', page);
};

// Expose methods to parent
defineExpose({
  reloadData,
  updateFilter,
  getSelectedRows,
  clearRowSelection,
  rowSelection
});
</script>