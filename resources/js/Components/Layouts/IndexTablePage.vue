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
              {{ $t('tables.loading') }}...
            </p>
          </div>
        </div>

        <ServerDataTable ref="dataTableRef" :model-name :entity-name="entityName || modelName" :data :columns
          :total-count :initial-page :page-size :can-create :create-route :enable-filtering :enable-column-visibility
          :initial-sorting :initial-filters :allow-toggle-deleted :show-deleted :deleted-count :empty-message
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

            <!--
              Everything that is not "create" collapses behind one trigger, so
              the header reads as a single primary action rather than a row of
              equally weighted buttons.
            -->
            <DropdownMenu v-if="secondaryActions?.length">
              <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon" class="size-9" data-testid="page-secondary-actions">
                  <MoreHorizontalIcon class="h-4 w-4" />
                  <span class="sr-only">{{ $t('tables.more_actions') }}</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-60">
                <template v-for="action in secondaryActions" :key="action.label">
                  <DropdownMenuItem v-if="action.href" as-child>
                    <Link :href="action.href">
                      <component :is="action.icon" v-if="action.icon" class="h-4 w-4" />
                      {{ action.label }}
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem v-else @select="action.onSelect?.()">
                    <component :is="action.icon" v-if="action.icon" class="h-4 w-4" />
                    {{ action.label }}
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>

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
                  {{ emptyMessage || $t('tables.no_results') }}
                </h3>
                <p class="text-sm text-muted-foreground">
                  <slot name="emptyDescription">
                    {{ emptyDescription || $t('tables.empty_description') }}
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
import { MoreHorizontalIcon, PlusCircleIcon } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';
import type { RowSelectionState } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';

import AdminContentPage from './AdminContentPage.vue';

import ServerDataTable from '@/Components/Tables/ServerDataTable.vue';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Spinner } from '@/Components/ui/spinner';
import { BreadcrumbHelpers, useBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

type ServerDataTableInstance = {
  reloadData: (page?: number) => void;
  updateFilter: (key: string, value: unknown) => void;
  getSelectedRows: () => unknown[];
  clearRowSelection: () => void;
};

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
const dataTableRef = ref<ServerDataTableInstance>();

// UI state
const isLoading = ref(false);

// Row selection state
const rowSelection = ref<RowSelectionState>(props.initialRowSelection || {});

// Computed properties
const isBackSupportNeeded = computed(() => props.backRoute !== undefined);

// Component event handlers
const handleDataLoaded = (data: unknown) => {
  isLoading.value = false;
  emit('data-loaded', data);
};

const handleSortingChanged = (sorting: unknown) => {
  isLoading.value = true;
  emit('sorting-changed', sorting);
};

const handlePageChanged = (page: number) => {
  isLoading.value = true;
  emit('page-changed', page);
};

const handleFilterChanged = (key: string, value: unknown) => {
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
