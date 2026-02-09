<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange" @page-changed="handlePageChange" @filter-changed="handleFilterChange">
    <template #filters>
      <DataTableFilter v-model:value="selectedCompletionStatuses" :options="completionStatusOptions" multiple
        @update:value="handleCompletionStatusFilterChange">
        {{ $t('Completion Status') }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed, watch, capitalize } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CalendarIcon, CheckCircle2Icon, AlertCircleIcon, CircleSlashIcon } from 'lucide-vue-next';

import { formatStaticTime } from '@/Utils/IntlTime';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { Badge } from '@/Components/ui/badge';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';
import Icons from '@/Types/Icons/regular';

const props = defineProps<{
  data: App.Entities.Meeting[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
}>();

// Component constants
const modelName = 'meetings';
const entityName = 'meeting';

// Component refs
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.meeting || false);

// Filter states
const selectedCompletionStatuses = ref<string[]>(props.filters?.['completion_status'] || []);

// Completion status options
const completionStatusOptions = computed(() => [
  { label: $t('Užpildyta'), value: 'complete' },
  { label: $t('Neužpildyta'), value: 'incomplete' },
  { label: $t('Nėra darbotvarkės'), value: 'no_items' },
]);

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.Meeting) => {
  return `meeting-${row.id}`;
};

// Get completion status badge variant
const getCompletionVariant = (status: string) => {
  return {
    complete: 'success',
    incomplete: 'warning',
    no_items: 'secondary',
  }[status] || 'secondary';
};

// Get completion status label
const getCompletionLabel = (status: string) => {
  return {
    complete: $t('Užpildyta'),
    incomplete: $t('Neužpildyta'),
    no_items: $t('Nėra darbotvarkės'),
  }[status] || status;
};

// Get completion status icon
const getCompletionIcon = (status: string) => {
  const icons = {
    complete: CheckCircle2Icon,
    incomplete: AlertCircleIcon,
    no_items: CircleSlashIcon,
  };
  return icons[status] || CircleSlashIcon;
};

// Table columns
const columns = computed<ColumnDef<App.Entities.Meeting, any>[]>(() => [
  {
    accessorKey: 'start_time',
    header: () => $t('Start Time'),
    cell: ({ row }) => {
      const startTime = row.getValue('start_time');
      return (
        <div class="flex items-center gap-2">
          <CalendarIcon class="h-4 w-4 text-muted-foreground" />
          <span class="font-medium">
            {formatStaticTime(new Date(startTime as string), {
              year: 'numeric',
              month: 'long',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit',
            })}
          </span>
        </div>
      );
    },
    size: 250,
    enableSorting: true,
  },
  {
    accessorKey: 'institutions',
    header: () => $t('Institution'),
    cell: ({ row }) => {
      const institutions = row.original.institutions || [];
      if (institutions.length === 0) {
        return <span class="text-muted-foreground italic">{$t('No institution')}</span>;
      }
      return (
        <div class="flex flex-wrap gap-1">
          {institutions.map((institution: App.Entities.Institution) => (
            <Badge key={institution.id} variant="outline">
              {institution.name}
            </Badge>
          ))}
        </div>
      );
    },
    size: 250,
    enableSorting: false,
  },
  {
    accessorKey: 'agenda_items',
    header: () => $t('Agenda Items'),
    cell: ({ row }) => {
      const agendaItems = row.original.agenda_items || [];
      if (agendaItems.length === 0) {
        return <span class="text-muted-foreground italic">—</span>;
      }
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="text-sm">
                {agendaItems.length}
                {' '}
                {agendaItems.length === 1 ? $t('item') : $t('items')}
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              <ul class="list-disc list-inside text-sm">
                {agendaItems.slice(0, 5).map((item: App.Entities.AgendaItem) => (
                  <li key={item.id} class="truncate">{item.title}</li>
                ))}
                {agendaItems.length > 5 && (
                  <li class="text-muted-foreground">
                    ...
                    {$t('and {count} more', { count: agendaItems.length - 5 })}
                  </li>
                )}
              </ul>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 150,
    enableSorting: false,
  },
  {
    id: 'completion_status',
    header: () => $t('Completion Status'),
    cell: ({ row }) => {
      const status = row.original.completion_status;
      const Icon = getCompletionIcon(status);
      return (
        <Badge variant={getCompletionVariant(status)} class="gap-1">
          <Icon class="h-3 w-3" />
          {getCompletionLabel(status)}
        </Badge>
      );
    },
    size: 180,
    enableSorting: false,
  },
  createStandardActionsColumn<App.Entities.Meeting>('meetings', {
    canView: true,
    canEdit: false,
    canDelete: true,
    canRestore: true,
  }),
]);

// Simplified table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Meeting>>(() => {
  return {
    // Essential table configuration
    modelName,
    entityName,
    data: props.data,
    columns: columns.value,
    getRowId,
    totalCount: props.meta.total,
    initialPage: props.meta.current_page,
    pageSize: props.meta.per_page,

    // Advanced features
    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: true,
    allowToggleDeleted: true,
    showDeleted: props.showDeleted,

    // Page layout
    headerTitle: capitalize($tChoice('entities.meeting.model', 2)),
    headerDescription: $t('View and manage meeting records and their completion status'),
    icon: Icons.MEETING,
    createRoute: undefined, // Meetings are created from institution pages
    canCreate: false,
  };
});

// Event handlers
const handleCompletionStatusFilterChange = (statuses: string[]) => {
  selectedCompletionStatuses.value = statuses;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('completion_status', statuses);
  }
};

// Event handlers for IndexTablePage
const handleFilterChange = (filterKey, value) => {
  if (filterKey === 'completion_status') {
    selectedCompletionStatuses.value = value;
  }
};

const handleSortingChange = (sorting) => {
  // Handle sorting changes - data will be reloaded automatically
};

const handlePageChange = (page) => {
  // Handle page changes - data will be reloaded automatically
};

const onDataLoaded = (data) => {
  // Handle data loaded event if needed
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters['completion_status'] !== undefined) {
      selectedCompletionStatuses.value = newFilters['completion_status'];
    }
  }
}, { deep: true });
</script>
