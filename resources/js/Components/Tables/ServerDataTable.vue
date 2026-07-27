<template>
  <div class="space-y-4">
    <!--
      `Alert` is a grid whose first column is zero-width unless it has a direct
      `svg` child, so its content must not be wrapped in a single plain element —
      that element lands in the zero-width track and the text wraps one word per
      line. Overriding the display with `flex` lays the banner out predictably.
    -->
    <Alert
      v-if="showDeleted"
      class="flex flex-col gap-3 border-amber-200 bg-amber-50 text-amber-950 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100"
    >
      <div class="flex items-start gap-2.5">
        <Trash2Icon class="mt-0.5 size-4 shrink-0" />
        <div class="space-y-0.5">
          <AlertTitle class="font-medium">
            {{ $t('trash.showing_deleted_only') }}
          </AlertTitle>
          <AlertDescription class="text-sm text-amber-900 dark:text-amber-100">
            {{ $t('trash.showing_deleted_only_description') }}
          </AlertDescription>
        </div>
      </div>
      <Button
        variant="outline"
        size="sm"
        class="shrink-0 border-amber-300 bg-white text-amber-950 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-100 dark:hover:bg-amber-900/40"
        @click="handleShowDeletedChange(false)"
      >
        {{ $t('trash.exit_trash_view') }}
      </Button>
    </Alert>

    <!-- Actual data table -->
    <DataTableProvider ref="dataTableProviderRef" :columns="displayColumns" :data :is-server-side="true" :total-items="totalCount"
      :server-pagination :server-sorting="sorting" :page-size :enable-pagination="true" :row-class-name="computedRowClassName" :empty-message
      :enable-filtering="false" :enable-column-visibility :global-filter="searchText" :enable-row-selection
      :enable-multi-row-selection :enable-row-selection-column :row-selection-state="rowSelection" :get-row-id :loading
      @page-change="handlePageChange" @update:sorting="handleSortChange" @update:global-filter="updateSearchText"
      @update:row-selection="handleRowSelectionChange">
      <template #filters>
        <div class="flex flex-col gap-3 w-full">
          <!-- Search -->
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div class="relative flex-1 min-w-0 max-w-full sm:max-w-xs md:max-w-sm">
              <SearchIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                v-model="searchText"
                :placeholder="$t('tables.search_placeholder')"
                class="pl-9 pr-8 w-full"
                @keydown.enter="handleSearch"
              />
              <button
                v-if="searchText"
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                @click="searchText = ''; handleSearch()"
              >
                <XIcon class="h-4 w-4" />
              </button>
            </div>
            <Button variant="default" size="sm" class="gap-1.5" @click="handleSearch">
              <SearchIcon class="h-4 w-4" />
              <span>{{ $t('tables.search') }}</span>
            </Button>
          </div>

          <!-- Custom filters from pages + active/deleted view toggle -->
          <div class="flex flex-wrap items-center gap-2">
            <slot name="filters" />

            <SpotlightPopover
              v-if="shouldShowDeletedToggle"
              :title="$t('trash.spotlight_title')"
              :description="$t('trash.spotlight_description')"
              :is-dismissed="!shouldShowTrashSpotlight || trashSpotlightIsDismissed"
              position="bottom"
              @dismiss="dismissTrashSpotlight"
            >
              <TrashViewToggle
                :show-deleted
                :deleted-count
                @update:show-deleted="handleShowDeletedChange"
              />
            </SpotlightPopover>
          </div>
        </div>
      </template>

      <template #actions>
        <!-- Page-level actions (Add button, headerActions, etc.) -->
        <slot name="actions" />
      </template>

      <template #empty>
        <slot name="empty">
          <!-- Enhanced empty state -->
          <EmptyState
            :title="emptyMessage || $t('tables.empty_title', { models: genitivePluralModelName })"
            :description="$t('tables.empty_description')"
            :icon="EmptyIcon"
          >
            <!-- Create button in empty state -->
            <Link v-if="canCreate && createRoute" :href="createRoute">
              <Button>
                {{ $t('forms.add') }}
              </Button>
            </Link>
          </EmptyState>
        </slot>
      </template>
    </DataTableProvider>
  </div>
</template>

<script setup lang="ts" generic="TData">
import { h, ref, watch, computed, onMounted } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef, SortingState, RowSelectionState } from '@tanstack/vue-table';
import { router, Link, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { PlusCircleIcon, SearchIcon, Trash2Icon, XIcon } from 'lucide-vue-next';

import DataTableProvider from '../ui/data-table/DataTableProvider.vue';
import TrashViewToggle from './TrashViewToggle.vue';

import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import EmptyState from '@/Components/Empty/EmptyState.vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import DateCell from '@/Components/ui/data-table/cells/DateCell.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { LocaleEnum } from '@/Types/enums';

type DataTableProviderInstance = {
  getSelectedRows?: () => unknown[] | undefined;
  clearRowSelection?: () => void;
};

// Define the props with TypeScript generics support
const props = defineProps<{
  // Inertia integration
  modelName: string;
  reloadOnly?: boolean;

  // Model information
  entityName?: string;
  pluralModelName?: string;
  singularModelName?: string;

  // Data display
  columns: ColumnDef<TData, any>[];
  data: TData[];

  // Pagination
  totalCount: number;
  initialPage?: number;
  pageSize?: number;

  // Options
  rowClassName?: (row: TData) => string;
  emptyMessage?: string;
  emptyIcon?: any;
  enableFiltering?: boolean;
  enableColumnVisibility?: boolean;
  showDeleted?: boolean;
  deletedCount?: number;

  // Admin features
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

const emit = defineEmits(['dataLoaded', 'update:rowSelection', 'create', 'sorting-changed', 'page-changed', 'filter-changed']);

// Extract search from initialFilters so the input is pre-populated on back-navigation
const extractSearchFromFilters = (filterRecord?: Record<string, unknown>): string => {
  if (!filterRecord) return '';
  const { search } = filterRecord;
  return typeof search === 'string' ? search : '';
};

// Component state
const searchText = ref(extractSearchFromFilters(props.initialFilters));
const pageIndex = ref(props.initialPage ? props.initialPage - 1 : 0);
const sorting = ref<SortingState>(props.initialSorting || []);
const filters = ref<Record<string, unknown>>({
  ...props.initialFilters || {},
  showDeleted: props.showDeleted || false,
});
const pageSize = computed(() => props.pageSize || 10);
const loading = ref(false);
const isInternalFilterUpdate = ref(false);

// Row selection state - maintain it outside of table state so it persists
const rowSelection = ref<RowSelectionState>(props.initialRowSelection || {});

// Admin features state
const showDeleted = ref(props.showDeleted || false);

// Computed properties for model names
const EmptyIcon = computed(() => props.emptyIcon || PlusCircleIcon);

// Computed row class name function that combines custom rowClassName with soft-deleted styling
const computedRowClassName = computed(() => {
  return (row: TData) => {
    const baseClasses = props.rowClassName ? props.rowClassName(row) : '';

    // Add soft-deleted styling if showDeleted is true and row has deleted_at
    if (showDeleted.value && row && (row as any).deleted_at) {
      return `${baseClasses} opacity-60`.trim();
    }

    return baseClasses;
  };
});

/**
 * Lithuanian declines the model name after "Nėra …" into the genitive, which is the
 * `[10,*]` plural form these keys already carry — so the empty-state heading reads
 * correctly ("Nėra naujienų") without a case-specific translation key.
 */
const genitivePluralModelName = computed(() => {
  if (props.pluralModelName) return props.pluralModelName;
  return $tChoice(`entities.${props.entityName || props.modelName}.model`, 10);
});

const currentLocale = computed<LocaleEnum>(() => {
  return (usePage().props as any)?.app?.locale === 'en' ? LocaleEnum.EN : LocaleEnum.LT;
});

/**
 * The trash view adds a deletion-time column so a deleted record shows *when* it was
 * removed. Injected here rather than in each of the ~19 index pages, and placed before
 * the actions column so the actions stay flush with the right edge of the table.
 */
const displayColumns = computed<ColumnDef<TData, any>[]>(() => {
  if (!showDeleted.value) {
    return props.columns;
  }

  const deletedAtColumn: ColumnDef<TData, any> = {
    id: 'deleted_at',
    accessorKey: 'deleted_at',
    header: () => $t('tables.deleted_at'),
    enableSorting: false,
    size: 140,
    cell: ({ row }) => h(DateCell, {
      date: (row.original as { deleted_at?: string | null })?.deleted_at ?? null,
      mode: 'relative',
      locale: currentLocale.value,
    }),
  };

  const actionsIndex = props.columns.findIndex(column => column.id === 'actions');

  if (actionsIndex === -1) {
    return [...props.columns, deletedAtColumn];
  }

  return [
    ...props.columns.slice(0, actionsIndex),
    deletedAtColumn,
    ...props.columns.slice(actionsIndex),
  ];
});

// Server pagination for UI
const serverPagination = computed(() => ({
  pageIndex: pageIndex.value,
  pageSize: pageSize.value,
}));

const hasDeletedCount = computed(() => typeof props.deletedCount === 'number' && props.deletedCount > 0);

/**
 * The toggle is always offered when the table supports it, even at a count of zero,
 * so the trash view is a predictable, discoverable part of every list rather than
 * something that appears only once records happen to be deleted.
 */
const shouldShowDeletedToggle = computed(() => props.allowToggleDeleted === true);

// Only spotlight a trash view that actually has something in it.
const shouldShowTrashSpotlight = computed(() => shouldShowDeletedToggle.value && hasDeletedCount.value);

type TrashSpotlight = Partial<ReturnType<typeof useFeatureSpotlight>>;

const trashSpotlight = useFeatureSpotlight('trash-view-v1', { position: 'bottom' }) as TrashSpotlight | undefined;

const trashSpotlightIsDismissed = computed(() => trashSpotlight?.isDismissed?.value ?? true);

const dismissTrashSpotlight = () => {
  void trashSpotlight?.dismiss?.();
};

// Reference to the DataTableProvider
const dataTableProviderRef = ref<DataTableProviderInstance>();

// Debounce function for search
const debouncedReload = useDebounceFn((resetPage = false) => {
  if (resetPage) {
    pageIndex.value = 0;
  }
  reloadData();
}, 300);

// Event handlers
const updateSearchText = (text: string) => {
  searchText.value = text;
  pageIndex.value = 0; // Go back to first page on search change
  debouncedReload(true);
};

const handleSearch = () => {
  pageIndex.value = 0; // Go back to first page on search
  // Explicitly pass the current search text to ensure it's included in the request
  reloadData();
};

const handleSortChange = (newSorting: SortingState) => {
  sorting.value = newSorting;
  emit('sorting-changed', newSorting);
  reloadData(); // Reload data with new sorting
};

const handlePageChange = (newPageIndex: number) => {
  pageIndex.value = newPageIndex;
  emit('page-changed', newPageIndex + 1); // Convert to 1-based for external use
  reloadData(); // Reload data with new page
};

const updateFilter = (key: string, value: any) => {
  filters.value[key] = value;
  pageIndex.value = 0; // Reset to first page when filter changes
  emit('filter-changed', key, value);
  reloadData();
};

const handleRowSelectionChange = (selection: RowSelectionState) => {
  rowSelection.value = selection;
  emit('update:rowSelection', selection);
};

// Encode table state for server requests
const encodeTableState = () => {
  const state: Record<string, any> = {
    page: pageIndex.value + 1, // Convert to 1-based indexing for backend
    per_page: pageSize.value,
  };

  // Add sorting if present
  if (sorting.value.length > 0) {
    state.sorting = JSON.stringify(sorting.value);
  }

  // Create filters object without showDeleted/search (to avoid duplication)
  const filtersToSend = { ...filters.value };
  delete filtersToSend.showDeleted;
  delete filtersToSend.search;

  // Add filters if present (excluding showDeleted)
  if (Object.keys(filtersToSend).length > 0) {
    state.filters = JSON.stringify(filtersToSend);
  }

  // Add search text if present
  if (searchText.value) {
    // Using 'search' key to match what the backend expects
    state.search = searchText.value;
  }

  // Add showDeleted parameter (ensure boolean) - only as direct parameter, not in filters
  state.showDeleted = Boolean(showDeleted.value);

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
    onSuccess: (response: { props: Record<string, unknown> }) => {
      const responseData = response.props[props.modelName];
      loading.value = false;

      // Emit data loaded event with context
      emit('dataLoaded', {
        page: pageIndex.value,
        sorting: sorting.value,
        filters: filters.value,
        data: responseData,
        rowSelection: rowSelection.value,
      });
    },
    onError: (errors: unknown) => {
      console.error('Error loading data:', errors);
      loading.value = false;
    },
  };

  if (props.reloadOnly) {
    router.reload(options);
  }
  else {
    router.visit(window.location.pathname, options);
  }
};

// Handle show deleted toggle
const handleShowDeletedChange = (checked: boolean) => {
  dismissTrashSpotlight();
  showDeleted.value = checked;
  filters.value.showDeleted = checked;
  pageIndex.value = 0; // Reset to first page when toggling deleted items
  reloadData();
};

// Watch for props changes
watch(() => props.showDeleted, (newValue) => {
  if (newValue !== undefined && newValue !== showDeleted.value) {
    showDeleted.value = !!newValue;
    filters.value.showDeleted = !!newValue;
    pageIndex.value = 0; // Reset to first page on filter change
    reloadData();
  }
}, { immediate: true });

// Watch for show deleted changes
watch(() => showDeleted.value, (newValue) => {
  if (newValue !== filters.value.showDeleted) {
    filters.value.showDeleted = newValue;
    pageIndex.value = 0;
    reloadData();
  }
});

// Keep search input in sync with external filter changes (e.g. browser back)
watch(() => props.initialFilters?.search, (newSearch) => {
  const next = typeof newSearch === 'string' ? newSearch : '';
  if (searchText.value !== next) {
    searchText.value = next;
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
        value !== null
        && typeof value === 'object'
        && currentValue !== null
        && typeof currentValue === 'object'
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
        ...newValue,
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

// Row selection helper methods
const getSelectedRows = () => {
  return dataTableProviderRef.value?.getSelectedRows?.() || [];
};

const clearRowSelection = () => {
  if (dataTableProviderRef.value) {
    dataTableProviderRef.value.clearRowSelection?.();
    rowSelection.value = {};
  }
};

// Expose public methods and computed properties
defineExpose({
  reloadData,
  currentPage: computed(() => pageIndex.value),
  sorting: computed(() => sorting.value),
  filters: computed(() => filters.value),
  rowSelection: computed(() => rowSelection.value),
  updateFilter,
  getSelectedRows,
  clearRowSelection,
});
</script>
