<template>
  <AdminContentPage>
    <div class="space-y-6">
      <!-- Optional description -->
      <p v-if="headerDescription" class="text-sm text-muted-foreground">
        {{ headerDescription }}
      </p>

      <!-- Main table -->
      <div class="relative min-h-[400px]">
        <!-- Loading Overlay -->
        <div v-if="isLoading"
          class="absolute inset-0 z-10 flex items-center justify-center rounded-md bg-background/40 backdrop-blur-[2px]">
          <div class="flex flex-col items-center gap-3 rounded-xl border border-border/50 bg-background/95 px-6 py-4 shadow-lg">
            <Spinner class="h-6 w-6 text-primary" />
            <p class="text-sm font-medium text-muted-foreground">
              {{ $t('Loading data') }}...
            </p>
          </div>
        </div>

        <ServerDataTable ref="dataTableRef" :model-name :entity-name="entityName || modelName" :data :columns
          :total-count :initial-page :page-size :can-create :create-route :enable-filtering :enable-column-visibility
          :initial-sorting :initial-filters :allow-toggle-deleted :show-deleted :empty-message
          :empty-icon="emptyIcon || PlusCircleIcon" :enable-row-selection :enable-multi-row-selection
          :enable-row-selection-column :initial-row-selection :get-row-id @data-loaded="handleDataLoaded"
          @update:row-selection="handleRowSelectionChange" @sorting-changed="handleSortingChanged"
          @page-changed="handlePageChanged" @filter-changed="handleFilterChanged">
          <!-- Pass through the slots -->
          <template #filters>
            <slot name="filters" />
          </template>
          <template #actions>
            <slot name="headerActions" />
            <Link v-if="canCreate && createRoute" :href="createRoute">
              <Button variant="default" class="gap-1.5 shadow-sm">
                <PlusCircleIcon class="h-4 w-4" />
                <span>{{ $t('forms.add') }}</span>
              </Button>
            </Link>
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
        </ServerDataTable>
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
import { trans as $t } from 'laravel-vue-i18n';
import type { RowSelectionState } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';

import AdminContentPage from './AdminContentPage.vue';

import ServerDataTable from '@/Components/Tables/ServerDataTable.vue';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { BreadcrumbHelpers, useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

// Props use the combined interface for better organization
const props = defineProps<IndexTablePageProps<TData>>();

// Auto-generate breadcrumbs from headerTitle/icon unless custom breadcrumbs are provided
const breadcrumbState = useBreadcrumbs();
if (props.headerTitle) {
  breadcrumbState.set(
    props.breadcrumbs ?? BreadcrumbHelpers.adminIndex(props.headerTitle, props.icon),
  );
}

const emit = defineEmits([
  'data-loaded',
  'sorting-changed',
  'page-changed',
  'filter-changed',
  'update:rowSelection',
]);

// Component refs
const dataTableRef = ref<InstanceType<typeof ServerDataTable>>();

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
  rowSelection,
});

</script>
