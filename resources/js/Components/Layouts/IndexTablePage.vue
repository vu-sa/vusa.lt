<template>
  <AdminContentPage :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Page Header -->
      <div v-if="headerTitle" class="flex flex-col space-y-2 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center space-x-4">
          <div v-if="icon" class="hidden rounded-md bg-primary/10 p-2 text-primary md:block">
            <component :is="icon" class="h-5 w-5" />
          </div>
          <div>
            <h2 class="text-2xl font-bold tracking-tight">
              {{ headerTitle }}
            </h2>
            <p v-if="headerDescription" class="text-sm text-muted-foreground">
              {{ headerDescription }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <slot name="headerActions" />
          <Button as-child v-if="canCreate && createRoute" variant="default" class="ml-auto gap-1.5">
            <Link :href="createRoute">
            <PlusCircleIcon class="h-4 w-4" />
            <span>{{ $t('forms.add') }}</span>
            </Link>
          </Button>
        </div>
      </div>

      <!-- Main table -->
      <div class="relative min-h-[400px]">
        <!-- Loading Skeleton -->
        <div v-if="isLoading"
          class="absolute inset-0 z-10 flex items-center justify-center rounded-md bg-background/80 backdrop-blur-sm">
          <div class="flex flex-col items-center space-y-4">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-primary" />
            <p class="text-sm text-muted-foreground">
              {{ $t('Loading data') }}...
            </p>
          </div>
        </div>

        <AdminDataTable 
          ref="dataTableRef" 
          :model-name="modelName" 
          :entity-name="entityName || modelName" 
          :data="data"
          :columns="columns" 
          :total-count="totalCount" 
          :initial-page="initialPage" 
          :page-size="pageSize"
          :can-create="canCreate" 
          :create-route="createRoute" 
          :enable-filtering="enableFiltering"
          :enable-column-visibility="enableColumnVisibility" 
          :initial-sorting="initialSorting"
          :initial-filters="initialFilters" 
          :allow-toggle-deleted="allowToggleDeleted" 
          :empty-message="emptyMessage"
          :empty-icon="emptyIcon || PlusCircleIcon" 
          :enable-row-selection="enableRowSelection"
          :enable-multi-row-selection="enableMultiRowSelection" 
          :enable-row-selection-column="enableRowSelectionColumn"
          :initial-row-selection="initialRowSelection" 
          :get-row-id="getRowId" 
          @data-loaded="handleDataLoaded"
          @update:row-selection="handleRowSelectionChange" 
          @sorting-changed="handleSortingChanged"
          @page-changed="handlePageChanged" 
          @filter-changed="handleFilterChanged"
        >
          <!-- Pass through the slots -->
          <template #tableActions>
            <slot name="tableActions" />
          </template>

          <template #filters>
            <slot name="filters" />
          </template>
          <template #actions>
            <slot name="actions" />
          </template>

          <template #empty>
            <div class="flex min-h-[200px] flex-col items-center justify-center space-y-3 p-8 text-center">
              <div class="rounded-full bg-muted/50 p-3">
                <component :is="emptyIcon || PlusCircleIcon" class="h-6 w-6 text-muted-foreground" />
              </div>
              <div class="max-w-md space-y-1">
                <h3 class="text-lg font-medium">
                  {{ emptyMessage || $t('No data available') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                  <slot name="emptyDescription">
                    {{ emptyDescription || ($t('You can add new items with the button above')) }}
                  </slot>
                </p>
              </div>
              <slot name="emptyActions">
                <Link v-if="canCreate && createRoute" :href="createRoute">
                  <Button variant="outline" class="gap-1.5">
                    <PlusCircleIcon class="h-4 w-4" />
                    <span>{{ $t('forms.add') }}</span>
                  </Button>

                </Link>
              </slot>
            </div>
          </template>
        </AdminDataTable>
      </div>

      <!-- Pagination -->
      <slot name="pagination" />

      <!-- Additional content -->
      <slot />
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts" generic="TData">
import { ref, computed, watch } from 'vue';
import { PlusCircleIcon } from 'lucide-vue-next';
import { trans as $t } from "laravel-vue-i18n";
import { type RowSelectionState } from '@tanstack/vue-table';

import AdminDataTable from '@/Components/Tables/AdminDataTable.vue';
import { Button } from '@/Components/ui/button';
import AdminContentPage from './AdminContentPage.vue';
import { useComponentBreadcrumbs } from "@/Composables/useBreadcrumbs";
import { Link } from '@inertiajs/vue3';
import { 
  type TableConfig, 
  type PaginationConfig, 
  type UIConfig,
  type FilteringConfig,
  type RowSelectionConfig,
  type IndexTablePageProps
} from '@/Types/TableConfigTypes';

// Props use the combined interface for better organization
const props = defineProps<IndexTablePageProps<TData>>();

const emit = defineEmits([
  'data-loaded',
  'sorting-changed',
  'page-changed',
  'filter-changed',
  'update:rowSelection'
]);

// Component refs
const dataTableRef = ref<InstanceType<typeof AdminDataTable>>();

// UI state
const isLoading = ref(false);

// Row selection state
const rowSelection = ref<RowSelectionState>(props.initialRowSelection || {});

// Computed properties
const isBackSupportNeeded = computed(() => props.backRoute !== undefined);

// Component event handlers
const handleDataLoaded = (data) => {
  isLoading.value = false;
  emit('data-loaded', data);
};

const handleSortingChanged = (sorting) => {
  isLoading.value = true;
  emit('sorting-changed', sorting);
};

const handlePageChanged = (page) => {
  isLoading.value = true;
  emit('page-changed', page);
};

const handleFilterChanged = (key, value) => {
  isLoading.value = true;
  emit('filter-changed', key, value);
};

const handleRowSelectionChange = (selection: RowSelectionState) => {
  rowSelection.value = selection;
  emit('update:rowSelection', selection);
};

// Watch for data changes to control loading state
watch(() => props.data, (newData) => {
  if (newData && isLoading.value) {
    // Short delay to prevent flashing of loading state for quick data loads
    setTimeout(() => {
      isLoading.value = false;
    }, 300);
  }
}, { deep: true });

// Exposed methods
const reloadData = (page?: number) => {
  isLoading.value = true;
  dataTableRef.value?.reloadData(page);
};

const updateFilter = (key: string, value: unknown) => {
  isLoading.value = true;
  dataTableRef.value?.updateFilter(key, value);
};

const getSelectedRows = () => {
  return dataTableRef.value?.getSelectedRows() || [];
};

const clearRowSelection = () => {
  if (dataTableRef.value) {
    dataTableRef.value.clearRowSelection();
    rowSelection.value = {};
    emit('update:rowSelection', {});
  }
};

defineExpose({
  reloadData,
  updateFilter,
  getSelectedRows,
  clearRowSelection,
  rowSelection
});

// Use the improved breadcrumbs composable
useComponentBreadcrumbs(() => props.breadcrumbs);
</script>
