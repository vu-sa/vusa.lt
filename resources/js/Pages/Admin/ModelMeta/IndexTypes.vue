<template>
  <IndexTablePage
    ref="indexTablePageRef"
    model-name="types"
    entity-name="type"
    title="Turinio tipai"
    :icon="Icons.TYPE"
    :data="props.data"
    :columns="columns"
    :total-count="props.meta.total"
    :initial-page="props.meta.current_page"
    :page-size="props.meta.per_page"
    :create-route="route('types.create')"
    :initial-filters="props.filters"
    :initial-sorting="props.sorting"
    can-create
    enable-filtering
    enable-column-visibility
    allow-toggle-deleted
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <template #filters>
      <DataTableFilter
        v-if="modelTypeOptions.length > 0"
        v-model:value="selectedModelType"
        :options="modelTypeOptions"
        @update:value="handleModelTypeFilterChange"
      >
        {{ $t("Model Type") }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { computed, ref, watch } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { type ColumnDef } from '@tanstack/vue-table';

import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Badge } from "@/Components/ui/badge";
import { 
  createIdColumn, 
  createTimestampColumn,
  createTextColumn
} from '@/Utils/DataTableColumns';
import { createStandardActionsColumn } from "@/Composables/useTableActions";

const props = defineProps<{
  data: App.Entities.Type[];
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
}>();

const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Extract unique model types for filtering
const modelTypes = computed(() => {
  const types = new Set<string>();
  props.data.forEach(type => {
    if (type.model_type) {
      types.add(type.model_type);
    }
  });
  return Array.from(types);
});

// Initialize filter states
const selectedModelType = ref<string | null>(props.filters?.['model_type'] || null);

// Filter options
const modelTypeOptions = computed(() => {
  return modelTypes.value.map(type => ({
    label: type,
    value: type,
  }));
});

// Table columns
const columns = computed<ColumnDef<App.Entities.Type, any>[]>(() => [
  createIdColumn(),
  {
    accessorKey: "title",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => {
      const title = row.getValue("title");
      return (
        <a href={route("types.edit", { id: row.original.id })} 
           class="font-medium hover:underline">
          {title}
        </a>
      );
    },
    size: 200,
    enableSorting: true,
  },
  createTextColumn("slug", { 
    title: $t("forms.fields.slug"),
    cell: ({ row }) => (
      <Badge variant="outline">{row.getValue("slug")}</Badge>
    )
  }),
  createTextColumn("model_type", {
    cell: ({ row }) => (
      <Badge variant="secondary">{row.getValue("model_type")}</Badge>
    )
  }),
  createTimestampColumn("created_at"),
  createTimestampColumn("updated_at"),
  createStandardActionsColumn<App.Entities.Type>("types", {
    canView: false,
    canEdit: true,
    canDelete: true,
    canRestore: true
  })
]);

// Event handlers
const handleModelTypeFilterChange = (modelType: string | null) => {
  selectedModelType.value = modelType;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('model_type', modelType);
  }
};

const onDataLoaded = (data) => {
  // Handle any additional logic after data is loaded
  console.log('Types data loaded:', data);
};

const handleSortingChange = (sorting) => {
  // Additional handling for sorting changes if needed
  console.log('Sorting changed:', sorting);
};

const handlePageChange = (page) => {
  // Additional handling for page changes if needed
  console.log('Page changed:', page);
};

const handleFilterChange = (filterKey, value) => {
  // Update local filter references if needed
  if (filterKey === 'model_type') {
    selectedModelType.value = value;
  }
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters['model_type'] !== undefined) {
      selectedModelType.value = newFilters['model_type'];
    }
  }
}, { deep: true });
</script>
