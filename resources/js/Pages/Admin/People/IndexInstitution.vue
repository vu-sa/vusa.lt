<template>
  <IndexTablePage
    ref="indexTablePageRef"
    model-name="institutions"
    entity-name="institution"
    :icon="Icons.INSTITUTION"
    :data="props.data"
    :columns="columns"
    :total-count="props.meta.total"
    :initial-page="props.meta.current_page"
    :page-size="props.meta.per_page"
    :create-route="route('institutions.create')"
    :initial-filters="props.filters"
    :initial-sorting="props.sorting"
    can-create
    enable-filtering
    enable-column-visibility
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <template #filters>
      <DataTableFilter
        v-if="types.length > 0"
        v-model:value="selectedTypeIds"
        :options="typeOptions"
        @update:value="handleTypeFilterChange"
        multiple
      >
        {{ $tChoice('forms.fields.type', 2) }}
      </DataTableFilter>
      
      <DataTableFilter
        v-if="tenantOptions.length > 0"
        v-model:value="selectedTenantIds"
        :options="tenantOptions"
        @update:value="handleTenantFilterChange"
        multiple
      >
        {{ capitalize($tChoice('entities.tenant.model', 1)) }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';
import { ref, computed, watch, capitalize } from "vue";
import { usePage } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { Badge } from "@/Components/ui/badge";
import { createStandardActionsColumn } from "@/Composables/useTableActions";

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
}>();

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Initialize filter states from props
const selectedTypeIds = ref<number[]>(props.filters?.['types.id'] || []);
const selectedTenantIds = ref<number[]>(props.filters?.['tenant.id'] || []);

// Filter options computed values
const typeOptions = computed(() => {
  return props.types.map((type) => ({
    label: $t(type.title),
    value: type.id,
  }));
});

const tenantOptions = computed(() => {
  const tenants = usePage().props.tenants || [];
  return tenants.map((tenant) => ({
    label: $t(tenant.shortname),
    value: tenant.id,
  }));
});

// Table columns
const columns = computed<ColumnDef<App.Entities.Institution, any>[]>(() => [
  {
    accessorKey: "name",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => {
      const name = row.getValue("name");
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <a href={route("institutions.edit", { id: row.original.id })} 
                 class="font-medium hover:underline max-w-[280px] truncate block">
                {name}
              </a>
            </TooltipTrigger>
            <TooltipContent side="top" align="start">
              <p>{name}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 300,
    enableSorting: true,
  },
  {
    accessorKey: "alias",
    header: () => '',
    cell: ({ row }) => {
      const alias = row.original.alias;
      if (!alias) return null;
      
      return (
        <PreviewModelButton
          publicRoute="contacts.alias"
          routeProps={{
            institution: alias,
            lang: "lt",
            subdomain: "www",
          }}
        />
      );
    },
    size: 60,
    enableSorting: false,
  },
  {
    accessorKey: "tenant.shortname",
    header: () => capitalize($tChoice("entities.tenant.model", 1)),
    cell: ({ row }) => {
      const tenant = row.original.tenant;
      return tenant ? $t(tenant.shortname) : "";
    },
    size: 150,
    enableSorting: false,
  },
  {
    accessorKey: "types",
    header: () => $tChoice("forms.fields.type", 2),
    cell: ({ row }) => {
      const types = row.original.types || [];
      return (
        <div class="flex flex-wrap gap-1">
          {types.map((type) => (
            <Badge variant="outline" key={type.id}>
              {$t(type.title)}
            </Badge>
          ))}
        </div>
      );
    },
    size: 200,
    enableSorting: true,
  },
  {
    id: "meetings",
    header: () => $t("Meetings"),
    cell: ({ row }) => {
      const meetings = row.original.meetings || [];
      if (meetings.length === 0) return null;
      
      return (
        <div class="flex flex-wrap items-center gap-2">
          {meetings.slice(0, 3).map((meeting) => (
            <a 
              key={meeting.id} 
              class="hover:underline" 
              href={route("meetings.show", meeting.id)}
            >
              {formatStaticTime(meeting.start_time)}
            </a>
          ))}
          {meetings.length > 3 && <span class="text-xs text-muted-foreground">+{meetings.length - 3} more</span>}
        </div>
      );
    },
    size: 250,
    enableSorting: false,
  },
  createStandardActionsColumn<App.Entities.Institution>("institutions", {
    canView: true,
    canEdit: true,
    canDelete: true,
    canRestore: true
  })
]);

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

// Event handler for data loaded
const onDataLoaded = (data) => {
  // Handle any additional logic after data is loaded
  console.log('Data loaded:', data);
};

// Event handler for sorting changes from IndexTablePage
const handleSortingChange = (sorting) => {
  // Additional handling for sorting changes if needed
  console.log('Sorting changed:', sorting);
};

// Event handler for page changes from IndexTablePage
const handlePageChange = (page) => {
  // Additional handling for page changes if needed
  console.log('Page changed:', page);
};

// Event handler for filter changes from IndexTablePage
const handleFilterChange = (filterKey, value) => {
  // Update local filter references if needed
  if (filterKey === 'types.id') {
    selectedTypeIds.value = value;
  } else if (filterKey === 'tenant.id') {
    selectedTenantIds.value = value;
  }
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
