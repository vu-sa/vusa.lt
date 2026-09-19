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
      <Button v-if="canMerge" variant="outline" class="gap-1.5" @click="enterMergeMode">
        <MergeIcon class="h-4 w-4" />
        {{ $t('Sujungti įrašus') }}
      </Button>
    </template>
    <template #actions>
      <Button v-if="isMergeMode && selectedRows.length > 1" variant="secondary" @click="mergeRecords = selectedRows">
        {{ $t('Sujungti pasirinktus') }}
      </Button>
      <Button v-if="isMergeMode" variant="ghost" @click="leaveMergeMode">
        {{ $t('Cancel') }}
      </Button>
    </template>
  </IndexTablePage>
  <MergeRecordsDialog
    :open="mergeRecords.length > 0"
    type="tags"
    :records="mergeRecords"
    :submit-url="route('tags.processMerge')"
    target-field="target_tag_id"
    source-field="source_tag_ids"
    @close="mergeRecords = []"
    @merged="merged"
  />
</template>

<script setup lang="ts">
import { h, ref, computed, watch, capitalize } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { router, usePage } from '@inertiajs/vue3';
import {
  MergeIcon,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import type { IndexTablePageInstance,
  IndexTablePageProps } from '@/Types/TableConfigTypes';
import { Button } from '@/Components/ui/button';
import { TruncatedBadge, TruncatedText } from '@/Components/ui/data-table/cells';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import { TagIcon } from '@/Components/icons';
import {
  createTitleColumn, resolveTranslatable,
} from '@/Composables/useDataTableColumns';
import MergeRecordsDialog, { type MergeRecord } from '@/Components/Merge/MergeRecordsDialog.vue';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';

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
  showDeleted?: boolean;
  deletedCount?: number;
}>();

// Component constants
const modelName = 'tags';
const entityName = 'tag';

// Component refs
const indexTablePageRef = ref<IndexTablePageInstance | null>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.tag || false);
const canForceDelete = computed(() => usePage().props.auth?.can?.forceDelete?.tag ?? false);
const canExport = computed(() => false); // Export functionality disabled for tags

// Row selection
const selectedRows = ref<MergeRecord[]>([]);
const mergeRecords = ref<MergeRecord[]>([]);
const { hasCollectionAction } = useAdminNavigation();
const canMerge = computed(() => hasCollectionAction('tags.index', 'merge'));
const isMergeMode = computed(() => new URLSearchParams(usePage().url.split('?')[1] ?? '').get('merge') === '1');

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
    accessorKey: 'is_topic',
    header: () => $t('forms.fields.is_topic'),
    cell: ({ row }) => {
      if (!row.original.is_topic) return null;

      return h(TruncatedBadge, { text: $t('forms.fields.is_topic'), variant: 'secondary' });
    },
    size: 120,
    enableSorting: true,
  },
  {
    accessorKey: 'created_at',
    header: () => $t('forms.fields.created_at'),
    cell: ({ row }) => {
      return h(TruncatedText, { text: new Date(row.original.created_at).toLocaleDateString('lt-LT') });
    },
    size: 150,
    enableSorting: true,
  },
  createStandardActionsColumn<App.Entities.Tag>('tags', {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: true,
    canForceDelete: canForceDelete.value,
    customActions: canMerge.value
      ? [{
          key: 'merge',
          label: $t('Sujungti su…'),
          icon: MergeIcon,
          onSelect: (row) => { mergeRecords.value = [{ id: row.id, label: resolveTranslatable(row.name), context: row.alias }]; },
        }]
      : [],
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
    initialSorting: props.sorting?.length ? props.sorting : [{ id: 'created_at', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: true,
    enableRowSelection: isMergeMode.value,
    enableRowSelectionColumn: isMergeMode.value,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,
    deletedCount: props.deletedCount,

    // Page layout
    headerTitle: 'Žymos',
    icon: TagIcon,
    createRoute: canCreate.value ? route('tags.create') : undefined,
    canCreate: canCreate.value,
  };
});

// Row selection handler
const handleRowSelectionChange = (selection: any) => {
  selectedRows.value = (indexTablePageRef.value?.getSelectedRows() ?? []).map(row => ({
    id: row.id,
    label: resolveTranslatable(row.name),
    context: row.alias,
  }));
};

const enterMergeMode = () => router.get(route('tags.index'), { merge: 1 }, { preserveState: true, preserveScroll: true });
const leaveMergeMode = () => router.get(route('tags.index'), {}, { preserveState: true, preserveScroll: true });
const merged = () => {
  mergeRecords.value = [];
  indexTablePageRef.value?.clearRowSelection();
  router.reload({ only: ['tags'] });
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
