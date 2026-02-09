<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange" @page-changed="handlePageChange" @filter-changed="handleFilterChange">
    <template #filters>
      <DataTableFilter v-if="types.length > 0" v-model:value="selectedTypeIds" :options="typeOptions" multiple
        @update:value="handleTypeFilterChange">
        {{ $tChoice('forms.fields.type', 2) }}
      </DataTableFilter>

      <DataTableFilter v-if="tenantOptions.length > 0" v-model:value="selectedTenantIds" :options="tenantOptions"
        multiple @update:value="handleTenantFilterChange">
        {{ capitalize($tChoice('entities.tenant.model', 1)) }}
      </DataTableFilter>
    </template>

    <!-- <template #emptyDescription>
      {{ $t('No institutions found. You can add a new institution using the button above.') }}
    </template> -->
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import type { ColumnDef } from '@tanstack/vue-table';
import { ref, computed, watch, capitalize } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
  BuildingIcon,
} from 'lucide-vue-next';

import { formatStaticTime } from '@/Utils/IntlTime';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { Badge } from '@/Components/ui/badge';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { createStandardActionsColumn } from '@/Composables/useTableActions';
import {
  createTitleColumn,
  createTenantColumn,
  createTagsColumn,
} from '@/Utils/DataTableColumns';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';

const props = defineProps<{
  data: App.Entities.Institution[];
  meta: {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  types: App.Entities.Type[];
  filters?: Record<string, any>;
  sorting?: { id: string; desc: boolean }[];
  showDeleted?: boolean;
}>();

// Component constants
const modelName = 'institutions';
const entityName = 'institution';

// Component refs
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Permission checks
const canCreate = computed(() => usePage().props.auth?.can?.create?.institution || false);

// Filter states
const selectedTypeIds = ref<number[]>(props.filters?.['types.id'] || []);
const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);

// Filter options computed values
const typeOptions = computed(() => {
  return props.types.map(type => ({
    label: $t(type.title),
    value: type.id,
  }));
});

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map(tenant => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

// Custom row ID function to ensure stable IDs across pagination/sorting
const getRowId = (row: App.Entities.Institution) => {
  return `institution-${row.id}`;
};

// Table columns
const columns = computed<ColumnDef<App.Entities.Institution, any>[]>(() => [
  createTitleColumn<App.Entities.Institution>({
    accessorKey: 'name',
    routeName: 'institutions.edit',
    width: 300,
    enableSorting: true,
    cell: ({ row }) => {
      const name = row.getValue('name');
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <div class="max-w-[290px] truncate">
                <a
                  href={route('institutions.edit', { id: row.original.id })}
                  class="font-medium hover:underline"
                >
                  {name}
                </a>
              </div>
            </TooltipTrigger>
            <TooltipContent side="top" align="start">
              <p>{name}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
  }),
  createTenantColumn({
    enableSorting: false,
    cell: ({ row }) => {
      const { tenant } = row.original;
      return tenant
        ? (
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <span class="flex items-center gap-1">
                    {tenant.logo_url && <img src={tenant.logo_url} alt={tenant.shortname} class="h-4 w-4 rounded-full" />}
                    <span class="max-w-[150px] truncate">{$t(tenant.shortname)}</span>
                  </span>
                </TooltipTrigger>
                <TooltipContent side="top" align="start">
                  <p>{$t(tenant.shortname)}</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          )
        : '';
    },
  }),
  createTagsColumn('types', {
    title: $tChoice('forms.fields.type', 2),
    labelKey: 'title',
    enableSorting: false,
    cell: ({ row }) => {
      const types = row.original.types || [];
      return (
        <div class="flex flex-wrap gap-1">
          {types.map(type => (
            <Badge variant="outline" key={type.id}>
              {$t(type.title)}
            </Badge>
          ))}
        </div>
      );
    },
    width: 200,
  }),
  {
    id: 'meetings',
    header: () => $t('Meetings'),
    cell: ({ row }) => {
      const meetings = row.original.meetings || [];
      if (meetings.length === 0) return null;

      return (
        <div class="flex flex-wrap items-center gap-2">
          {meetings.slice(0, 3).map(meeting => (
            <a
              key={meeting.id}
              class="hover:underline text-xs px-2 py-1 rounded-md bg-muted"
              href={route('meetings.show', meeting.id)}
            >
              {formatStaticTime(meeting.start_time)}
            </a>
          ))}
          {meetings.length > 3 && (
            <span class="text-xs text-muted-foreground">
              +
              {meetings.length - 3}
              {' '}
              more
            </span>
          )}
        </div>
      );
    },
    size: 250,
    enableSorting: false,
  },
  createStandardActionsColumn<App.Entities.Institution>('institutions', {
    canView: true,
    canEdit: true,
    canDelete: true,
    canRestore: true,
  }),
]);

// Simplified table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Institution>>(() => {
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
    headerTitle: 'Institutions',
    headerDescription: $t('Manage institution records and their types'),
    icon: BuildingIcon,
    createRoute: canCreate.value ? route('institutions.create') : undefined,
    canCreate: canCreate.value,
  };
});

// Event handlers
const handleTypeFilterChange = (typeIds: number[]) => {
  selectedTypeIds.value = typeIds;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('types.id', typeIds);
  }
};

const handleTenantFilterChange = (tenantIds: number[]) => {
  selectedTenantIds.value = tenantIds;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('tenant.id', tenantIds);
  }
};

// Event handlers for IndexTablePage
const handleFilterChange = (filterKey, value) => {
  // Update local filter references if needed
  if (filterKey === 'types.id') {
    selectedTypeIds.value = value;
  }
  else if (filterKey === 'tenant.id') {
    selectedTenantIds.value = value;
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
    if (newFilters['types.id'] !== undefined) {
      selectedTypeIds.value = newFilters['types.id'];
    }
    if (newFilters['tenant.id'] !== undefined) {
      selectedTenantIds.value = newFilters['tenant.id'];
    }
  }
}, { deep: true });
</script>
