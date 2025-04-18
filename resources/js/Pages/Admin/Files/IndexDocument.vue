<template>
  <IndexTablePage
    ref="indexTablePageRef"
    v-bind="tableConfig"
    @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange"
    @page-changed="handlePageChange"
    @filter-changed="handleFilterChange"
  >
    <!-- <template #pageActions>
      <FilePicker 
        v-if="$page.props.app.url.startsWith('https')" 
        :loading="loading" 
        round 
        size="sm"
        @pick="handleDocumentPick"
      >
        {{ $t("forms.add") }}
      </FilePicker>
    </template> -->
    
    <template #filters>
      <!-- TODO: needs to get all of the types from db, like the ShowDocuments.vue-->
      <!-- <DataTableFilter
        v-if="contentTypeOptions.length > 0"
        v-model:value="selectedContentType"
        :options="contentTypeOptions"
        @update:value="handleContentTypeFilterChange"
      >
        {{ $t("content_type") }}
      </DataTableFilter>

      <DataTableFilter
        v-if="languageOptions.length > 0"
        v-model:value="selectedLanguage"
        :options="languageOptions"
        @update:value="handleLanguageFilterChange"
      >
        {{ $t("language") }}
      </DataTableFilter>

      <DataTableFilter
        v-if="institutionOptions.length > 0"
        v-model:value="selectedInstitutionId"
        :options="institutionOptions"
        @update:value="handleInstitutionFilterChange"
      >
        {{ $t("institution") }}
      </DataTableFilter> -->
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { type ColumnDef } from '@tanstack/vue-table';
import { capitalize } from "vue";

import { Item } from "@/Features/Admin/SharepointFilePicker/picker";
import FilePicker from "@/Features/Admin/SharepointFilePicker/FilePicker.vue";
import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";
import { Button } from "@/Components/ui/button";
import { ExternalLinkIcon, RefreshCwIcon } from "lucide-vue-next";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { useBreadcrumbs, type BreadcrumbItem } from "@/Composables/useBreadcrumbs";
import { 
  type IndexTablePageProps,
  type TableConfig,
  type PaginationConfig,
  type UIConfig,
  type FilteringConfig
} from "@/Types/TableConfigTypes";
import { 
  createTimestampColumn, 
  createTextColumn,
  createTitleColumn
} from '@/Utils/DataTableColumns';

const props = defineProps<{
  data: App.Entities.Document[];
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

// Component constants
const modelName = 'documents';
const entityName = 'document';

// Breadcrumbs setup
const { createBreadcrumbItem, homeItem } = useBreadcrumbs();

const breadcrumbs = computed((): BreadcrumbItem[] => [
  homeItem(),
  createBreadcrumbItem($t("administration.title"), route("administration")),
  createBreadcrumbItem($t("Documents"), undefined, Icons.DOCUMENT)
]);

const loading = ref(false);
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Initialize filter states
const selectedContentType = ref<string | null>(props.filters?.['content_type'] || null);
const selectedLanguage = ref<string | null>(props.filters?.['language'] || null);
const selectedInstitutionId = ref<number | null>(props.filters?.['institution.id'] || null);

// Extract unique content types and languages for filtering
const uniqueContentTypes = computed(() => {
  const types = new Set<string>();
  props.data.forEach(doc => {
    if (doc.content_type) {
      types.add(doc.content_type);
    }
  });
  return Array.from(types);
});

const uniqueLanguages = computed(() => {
  const languages = new Set<string>();
  props.data.forEach(doc => {
    if (doc.language) {
      languages.add(doc.language);
    }
  });
  return Array.from(languages);
});

// Filter options
const contentTypeOptions = computed(() => {
  return uniqueContentTypes.value.map(type => ({
    label: type,
    value: type,
  }));
});

const languageOptions = computed(() => {
  return uniqueLanguages.value.map(lang => ({
    label: lang,
    value: lang,
  }));
});

const institutionOptions = computed(() => {
  const institutions = usePage().props.institutions || [];
  return institutions.map(institution => ({
    label: institution.short_name || institution.name,
    value: institution.id,
  }));
});

// Column definitions using Tanstack Table format and standardized column helpers
const columns = computed<ColumnDef<App.Entities.Document, any>[]>(() => [
  createTitleColumn<App.Entities.Document>({
    accessorKey: "title",
    header: () => $t("forms.fields.title"),
    routeName: "documents.show",
    width: 350,
    cell: ({ row }) => {
      const title = row.getValue("title");
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <div class="max-w-[340px] truncate">
                <a href={row.original.anonymous_url} target="_blank" rel="noopener noreferrer" class="font-medium hover:underline">
                  {title}
                </a>
              </div>
            </TooltipTrigger>
            <TooltipContent side="top" align="start">
              <p>{title}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    }
  }),
  {
    id: "actions",
    header: () => $t("actions"),
    cell: ({ row }) => (
      <div class="flex justify-center">
        <Button 
          size="icon" 
          variant="ghost"
          onClick={() => handleDocumentRefresh(row.original)}
          title={$t("refresh")}
        >
          <RefreshCwIcon class="h-4 w-4" />
        </Button>
      </div>
    ),
    size: 80,
  },
  createTimestampColumn("document_date", {
    title: $t("date"),
    width: 130,
    enableSorting: true,
  }),
  createTextColumn("content_type", {
    cell: ({ row }) => (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <div class="truncate max-w-[140px]">
              {row.getValue("content_type")}
            </div>
          </TooltipTrigger>
          <TooltipContent side="top">
            <p>{row.getValue("content_type")}</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    ),
    width: 150
  }),
  createTextColumn("language", {
    width: 100
  }),
  {
    accessorKey: "institution.short_name",
    header: () => $t("institution"),
    cell: ({ row }) => {
      const institution = row.original.institution;
      return institution ? institution.short_name : '';
    },
    size: 150,
  },
  createTimestampColumn("checked_at", {
    cell: ({ row }) => (
      <div class="flex items-center gap-2">
        <span>{row.getValue("checked_at") ? 
          new Date(row.getValue("checked_at") as string).toLocaleString() : 
          ''}</span>
        <Button 
          size="icon" 
          variant="ghost"
          onClick={() => handleDocumentRefresh(row.original)}
        >
          <RefreshCwIcon class="h-4 w-4" />
        </Button>
      </div>
    ),
    width: 220
  })
]);

// Consolidated table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Document>>(() => {
  // Core table configuration
  const tableConfig: TableConfig<App.Entities.Document> = {
    modelName,
    entityName,
    data: props.data,
    columns: columns.value
  };
  
  // Pagination configuration
  const paginationConfig: PaginationConfig = {
    totalCount: props.meta.total,
    initialPage: props.meta.current_page,
    pageSize: props.meta.per_page
  };
  
  // UI configuration
  const uiConfig: UIConfig = {
    headerTitle: $t("Documents"),
    icon: Icons.DOCUMENT,
    canCreate: false,
    breadcrumbs: breadcrumbs.value
  };
  
  // Filtering configuration
  const filteringConfig: FilteringConfig = {
    initialFilters: props.filters,
    initialSorting: props.sorting,
    enableFiltering: true,
    enableColumnVisibility: true
  };
  
  // Return the combined configuration
  return {
    ...tableConfig,
    ...paginationConfig,
    ...uiConfig,
    ...filteringConfig
  };
});

// Filter handlers
const handleContentTypeFilterChange = (contentType: string | null) => {
  selectedContentType.value = contentType;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('content_type', contentType);
  }
};

const handleLanguageFilterChange = (language: string | null) => {
  selectedLanguage.value = language;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('language', language);
  }
};

const handleInstitutionFilterChange = (institutionId: number | null) => {
  selectedInstitutionId.value = institutionId;
  if (indexTablePageRef.value) {
    indexTablePageRef.value.updateFilter('institution.id', institutionId);
  }
};

// Event handlers
const onDataLoaded = (data) => {
  // Handle any additional logic after data is loaded
  console.log('Documents data loaded:', data);
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
  if (filterKey === 'content_type') {
    selectedContentType.value = value;
  } else if (filterKey === 'language') {
    selectedLanguage.value = value;
  } else if (filterKey === 'institution.id') {
    selectedInstitutionId.value = value;
  }
};

const handleDocumentPick = (items: Item[]) => {
  loading.value = true;

  const documents = items.map((item) => ({
    name: item.name,
    site_id: item.sharepointIds?.siteId,
    list_id: item.sharepointIds?.listId,
    list_item_unique_id: item.sharepointIds?.listItemUniqueId,
  }));

  router.post(route("documents.store"), { documents }, {
    onSuccess: () => {
      loading.value = false;
      // Reload the current page to show new documents
      if (indexTablePageRef.value) {
        indexTablePageRef.value.reloadData();
      }
    },
    onError() {
      loading.value = false;
    },
  });
};

const handleDocumentRefresh = (document: App.Entities.Document) => {
  loading.value = true;

  router.post(route("documents.refresh", document.id), {}, {
    onSuccess: () => {
      loading.value = false;
      // Reload the current page after refreshing document
      if (indexTablePageRef.value) {
        indexTablePageRef.value.reloadData();
      }
    },
    onError() {
      loading.value = false;
    },
  });
};

// Sync filter values when changed externally
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters['content_type'] !== undefined) {
      selectedContentType.value = newFilters['content_type'];
    }
    if (newFilters['language'] !== undefined) {
      selectedLanguage.value = newFilters['language'];
    }
    if (newFilters['institution.id'] !== undefined) {
      selectedInstitutionId.value = newFilters['institution.id'];
    }
  }
}, { deep: true });
</script>
