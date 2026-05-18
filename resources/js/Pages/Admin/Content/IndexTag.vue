<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
    @update:row-selection="handleRowSelectionChange"
  >
    <template #headerActions>
      <Button variant="outline" as-child class="gap-1.5">
        <Link :href="route('tags.merge')">
          <MergeIcon class="h-4 w-4" />
          {{ $t('Sulieti žymas') }}
        </Link>
      </Button>
    </template>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed, watch, capitalize } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { router, usePage, Link } from '@inertiajs/vue3';
import {
  MergeIcon,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import { Button } from '@/Components/ui/button';
import { TruncatedBadge, TruncatedText } from '@/Components/ui/data-table/cells';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { TagIcon } from '@/Components/icons';
import {
  createTitleColumn,
} from '@/Composables/useDataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  tags: {
    data: App.Entities.Tag[];
    meta: {
      total: number;
      current_page: number;
      per_page: number;
      last_page: number;
      from: number;
      to: number;
    };
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
}>();

// Component constants
const modelName = 'tags';
const entityName = 'tag';

// Component refs
const indexTablePageRef = ref<any>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.tag || false);
const canExport = computed(() => false); // Export functionality disabled for tags

// Row selection
const selectedRows = ref([]);

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.Tag) => {
  return `tag-${row.id}`;
};

// Table columns
const columns = computed<Array<ColumnDef<App.Entities.Tag, any>>>(() => [
  createTitleColumn<App.Entities.Tag>({
    accessorKey: 'name',
    routeName: 'tags.edit',
    width: 300,
  }),
  {
    accessorKey: 'alias',
    header: () => $t('Alias'),
    cell: ({ row }) => {
      const { alias } = row.original;
      if (!alias) return null;

      return h(TruncatedBadge, { text: alias, variant: 'outline' });
    },
    size: 150,
  },
  {
    accessorKey: 'created_at',
    header: () => $t('forms.fields.created_at'),
    cell: ({ row }) => {
      return h(TruncatedText, { text: new Date(row.original.created_at).toLocaleDateString('lt-LT') });
    },
    size: 150,
  },
  createStandardActionsColumn<App.Entities.Tag>('tags', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: false,
  }),
]);

// Simplified table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Tag>>(() => {
  return {
    // Essential table configuration
    modelName,
    entityName,
    data: props.tags.data,
    columns: columns.value,
    getRowId,
    totalCount: props.tags.meta.total,
    initialPage: props.tags.meta.current_page,
    pageSize: props.tags.meta.per_page,

    // Advanced features
    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: true,
    enableRowSelection: false,
    enableRowSelectionColumn: false,

    // Page layout
    headerTitle: 'Žymos',
    icon: TagIcon,
    createRoute: canCreate.value ? route('tags.create') : undefined,
    canCreate: canCreate.value,
  };
});

// Row selection handler
const handleRowSelectionChange = (selection: any) => {
  // Get the actual row objects from the selection state
  selectedRows.value = indexTablePageRef.value?.getSelectedRows() || [];
};

// Event handler for data loaded
const onDataLoaded = (data: any) => {
  // Additional handling after data is loaded if needed
};

// Event handler for sorting changes from IndexTablePage
const handleSortingChange = (sorting: any) => {
  // Additional handling for sorting changes if needed
};

// Event handler for page changes from IndexTablePage
const handlePageChange = (page: any) => {
  // Additional handling for page changes if needed
};

// Event handler for filter changes from IndexTablePage
const handleFilterChange = (filterKey: any, value: any) => {
  // Additional handling for filter changes if needed
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  // Handle any external filter changes if needed
}, { deep: true });
</script>
