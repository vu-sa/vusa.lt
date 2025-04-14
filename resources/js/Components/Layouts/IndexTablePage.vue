<template>
  <div>
    <!-- <PageTitle :icon="icon" :showBack="showBackButton">
      <template #title>{{ pageTitle }}</template>
      <template #description v-if="pageDescription || $slots.description">
        <slot name="description">{{ pageDescription }}</slot>
      </template>
      <template #actions>
        <slot name="pageActions"></slot>
      </template>
    </PageTitle> -->

    <div class="space-y-6">
      <slot name="above-table"></slot>

      <AdminDataTable
        ref="adminTableRef"
        :model-name="modelName"
        :entity-name="entityName"
        :data="data"
        :columns="columns"
        :total-count="totalCount"
        :initial-page="initialPage"
        :page-size="pageSize"
        :row-class-name="rowClassName"
        :empty-message="emptyMessage || emptyText"
        :enable-filtering="enableFiltering"
        :enable-column-visibility="enableColumnVisibility"
        :allow-toggle-deleted="allowToggleDeleted"
        :can-create="canCreate"
        :create-route="createRoute"
        :initial-sorting="initialSorting"
        :initial-filters="initialFilters"
        @data-loaded="handleDataLoaded"
        @sorting-changed="handleSortingChanged"
        @filter-changed="handleFilterChanged"
        @page-changed="handlePageChanged"
      >
        <!-- Pass through slots -->
        <template #filters>
          <slot name="filters"></slot>
        </template>

        <template #tableActions>
          <slot name="tableActions"></slot>
        </template>

        <template #actions>
          <slot name="actions"></slot>
        </template>

        <template #empty>
          <slot name="empty"></slot>
        </template>
      </AdminDataTable>

      <slot name="below-table"></slot>
    </div>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { ref, computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';

import AdminDataTable from '@/Components/Tables/AdminDataTable.vue';
// import PageTitle from '@/Components/Admin/PageTitle.vue';

// Define props
const props = defineProps<{
  // Model information
  modelName: string;
  entityName?: string;
  
  // UI elements
  icon?: string;
  showBackButton?: boolean;
  pageTitle?: string;
  pageDescription?: string;
  
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
  
  // Features
  enableFiltering?: boolean;
  enableColumnVisibility?: boolean;
  allowToggleDeleted?: boolean;
  canCreate?: boolean;
  createRoute?: string;
  
  // Initial state
  initialSorting?: SortingState;
  initialFilters?: Record<string, unknown>;
}>();

const emit = defineEmits([
  'data-loaded', 
  'create', 
  'sorting-changed', 
  'filter-changed', 
  'page-changed'
]);

// Set up references
const adminTableRef = ref<InstanceType<typeof AdminDataTable> | null>(null);

// Computed properties
const pageTitle = computed(() => {
  if (props.pageTitle) return props.pageTitle;
  const baseTitle = props.entityName 
    ? $tChoice(`entities.${props.entityName}.model`, 2) 
    : props.modelName;
  return baseTitle.charAt(0).toUpperCase() + baseTitle.slice(1);
});

const emptyText = computed(() => {
  const baseText = props.entityName 
    ? $tChoice(`entities.${props.entityName}.model`, 2)
    : props.modelName;
  return $t(`No ${baseText} found.`);
});

// Methods
const reloadData = (page?: number) => {
  if (adminTableRef.value) {
    adminTableRef.value.reloadData(page);
  }
};

const updateFilter = (key: string, value: unknown) => {
  if (adminTableRef.value) {
    adminTableRef.value.updateFilter(key, value);
  }
};

// Event handlers
const handleDataLoaded = (data: any) => {
  emit('data-loaded', data);
};

const handleSortingChanged = (sorting: SortingState) => {
  emit('sorting-changed', sorting);
};

const handleFilterChanged = (key: string, value: unknown) => {
  emit('filter-changed', key, value);
};

const handlePageChanged = (page: number) => {
  emit('page-changed', page);
};

// Expose methods to parent
defineExpose({
  reloadData,
  updateFilter,
  currentPage: computed(() => adminTableRef.value?.currentPage || 0),
  currentSorting: computed(() => adminTableRef.value?.currentSorting || []),
  currentFilters: computed(() => adminTableRef.value?.currentFilters || {})
});
</script>