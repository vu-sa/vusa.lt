<template>
  <IndexTablePage ref="indexTablePageRef" v-bind="tableConfig" @data-loaded="onDataLoaded"
    @sorting-changed="handleSortingChange" @page-changed="handlePageChange" @filter-changed="handleFilterChange">
    <template #headerActions>
      <div class="flex items-center gap-2">
        <FilePicker v-if="$page.props.app.url.startsWith('https')" :loading round size="sm" @pick="handleDocumentPick">
          <div class="flex items-center gap-2">
            <ExternalLinkIcon class="h-4 w-4" />
            {{ $t("Upload from SharePoint") }}
          </div>
        </FilePicker>

        <Button variant="outline" size="sm" :loading="bulkSyncLoading" @click="handleBulkSync">
          <div class="flex items-center gap-2">
            <RefreshCwIcon class="h-4 w-4" />
            {{ $t("Sync All") }}
          </div>
        </Button>
      </div>
    </template>

    <template #filters>
      <DataTableFilter v-if="contentTypeOptions.length > 0" v-model:value="selectedContentType"
        :options="contentTypeOptions" @update:value="handleContentTypeFilterChange">
        {{ $t("content_type") }}
      </DataTableFilter>

      <DataTableFilter v-if="languageOptions.length > 0" v-model:value="selectedLanguage" :options="languageOptions"
        @update:value="handleLanguageFilterChange">
        {{ $t("language") }}
      </DataTableFilter>

      <DataTableFilter v-if="institutionOptions.length > 0" v-model:value="selectedInstitutionId"
        :options="institutionOptions" @update:value="handleInstitutionFilterChange">
        {{ $t("institution") }}
      </DataTableFilter>
    </template>
  </IndexTablePage>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { ref, computed, watch, capitalize } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import type { ColumnDef } from '@tanstack/vue-table';
import { ExternalLinkIcon, RefreshCwIcon } from "lucide-vue-next";

import type { Item } from "@/Features/Admin/SharepointFilePicker/picker";
import FilePicker from "@/Features/Admin/SharepointFilePicker/FilePicker.vue";
import Icons from "@/Types/Icons/regular";
import IndexTablePage from "@/Components/Layouts/IndexTablePage.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";
import { Button } from "@/Components/ui/button";
import DataTableFilter from "@/Components/ui/data-table/DataTableFilter.vue";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import { LocaleEnum } from "@/Types/enums";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import type {
  IndexTablePageProps
} from "@/Types/TableConfigTypes";
import {
  createTimestampColumn,
  createTextColumn,
  createTitleColumn
} from '@/Utils/DataTableColumns';
import { createStandardActionsColumn } from "@/Composables/useTableActions";

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
  filterOptions?: {
    contentTypes: string[];
    languages: string[];
  };
}>();

// Component constants
const modelName = 'documents';
const entityName = 'document';

// Breadcrumbs setup
usePageBreadcrumbs(() => [
  BreadcrumbHelpers.homeItem(),
  BreadcrumbHelpers.createBreadcrumbItem($t("Administravimas"), route("administration"), Icons.TYPE),
  BreadcrumbHelpers.createBreadcrumbItem($t("Dokumentai"), undefined, Icons.DOCUMENT)
]);

const loading = ref(false);
const bulkSyncLoading = ref(false);
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);

// Initialize filter states
const selectedContentType = ref<string | null>(props.filters?.['content_type'] || null);
const selectedLanguage = ref<string | null>(props.filters?.['language'] || null);
const selectedInstitutionId = ref<number | null>(props.filters?.['institution.id'] || null);

// Filter options using complete data from backend instead of current page data
const contentTypeOptions = computed(() => {
  const types = props.filterOptions?.contentTypes || [];
  return types.map(type => ({
    label: type,
    value: type,
  }));
});

const languageOptions = computed(() => {
  const languages = props.filterOptions?.languages || [];
  return languages.map(lang => ({
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
  {
    accessorKey: "title",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => {
      const title = row.getValue("title");
      const hasUrl = row.original.anonymous_url;

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <div class="min-w-0 flex-1">
                {hasUrl ? (
                  <a
                    href={row.original.anonymous_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium hover:underline text-blue-600 hover:text-blue-800 line-clamp-2 text-sm leading-tight"
                    title={title}
                  >
                    {title}
                    <ExternalLinkIcon class="inline h-3 w-3 ml-1 opacity-60" />
                  </a>
                ) : (
                  <span class="font-medium text-gray-600 line-clamp-2 text-sm leading-tight" title={title}>
                    {title}
                  </span>
                )}
              </div>
            </TooltipTrigger>
            <TooltipContent side="top" align="start" class="max-w-md">
              <div class="space-y-1">
                <p class="font-medium">{title}</p>
                {hasUrl && (
                  <p class="text-xs text-gray-400">Click to open document</p>
                )}
              </div>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 300,
    enableSorting: true,
  },
  {
    accessorKey: "language",
    header: () => $t("lang"),
    cell: ({ row }) => {
      const language = row.getValue("language");
      if (!language) return '—';
      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="text-xs px-2 py-1 bg-gray-100 rounded-md truncate" title={language}>
                {language === 'Lietuvių' ? 'LT' : language === 'Anglų' ? 'EN' : language}
              </span>
            </TooltipTrigger>
            <TooltipContent side="top">
              <p>{language}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 70,
    enableSorting: true,
  },
  {
    accessorKey: "document_date",
    header: () => $t("date"),
    cell: ({ row }) => {
      const date = row.getValue("document_date");
      if (!date) return '—';

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="text-sm cursor-help font-mono">
                {formatStaticTime(date, {
                  year: "numeric",
                  month: "2-digit",
                  day: "2-digit"
                }, LocaleEnum.LT)}
              </span>
            </TooltipTrigger>
            <TooltipContent>
              <p>{formatStaticTime(date, {
                year: "numeric",
                month: "long",
                day: "numeric",
                weekday: "long"
              }, LocaleEnum.LT)}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 100,
    enableSorting: true,
  },
  {
    accessorKey: "content_type",
    header: () => $t("content_type"),
    cell: ({ row }) => {
      const contentType = row.getValue("content_type");
      if (!contentType) return '—';

      // Shorten common content types for display
      const shortType = contentType
        .replace('Parlamento ', 'Parl. ')
        .replace(' sprendimas', ' spr.')
        .replace(' protokolas', ' prot.')
        .replace(' darbotvarkė', ' d.t.');

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="text-xs px-2 py-1 bg-gray-100 rounded-md truncate max-w-[140px] block" title={contentType}>
                {shortType}
              </span>
            </TooltipTrigger>
            <TooltipContent side="top">
              <p>{contentType}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 150,
    enableSorting: true,
  },
  // createTextColumn("language", {
  //   width: 100
  // }),
  {
    accessorKey: "institution_name_lt",
    header: () => $t("institution"),
    cell: ({ row }) => {
      // Using paginateRaw() workaround returns database models with nested institution
      const { institution } = row.original;
      if (!institution) return '—';

      const shortName = institution.short_name || institution.name;
      const fullName = institution.name;

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md">
                {shortName}
              </span>
            </TooltipTrigger>
            <TooltipContent>
              <p>{fullName || shortName}</p>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
    size: 120,
    enableSorting: true,
  },
  {
    accessorKey: "sync_status",
    header: () => $t("Sync Status"),
    cell: ({ row }) => {
      const status = row.original.sync_status || 'pending';
      const attempts = row.original.sync_attempts || 0;
      const statusConfig = {
        'pending': {
          color: 'text-gray-600',
          bgColor: 'bg-gray-100',
          dotColor: 'bg-gray-400',
          label: 'Pending',
          icon: '⏳'
        },
        'syncing': {
          color: 'text-blue-600',
          bgColor: 'bg-blue-50',
          dotColor: 'bg-blue-500 animate-pulse',
          label: 'Syncing...',
          icon: '🔄'
        },
        'success': {
          color: 'text-green-600',
          bgColor: 'bg-green-50',
          dotColor: 'bg-green-500',
          label: 'Success',
          icon: '✅'
        },
        'failed': {
          color: 'text-red-600',
          bgColor: 'bg-red-50',
          dotColor: 'bg-red-500',
          label: 'Failed',
          icon: '❌'
        }
      };

      const config = statusConfig[status] || statusConfig['pending'];

      return (
        <div class={`inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium ${config.bgColor} ${config.color}`}>
          <div class={`w-2 h-2 rounded-full ${config.dotColor}`}></div>
          <span>{config.label}</span>
          {attempts > 1 && (
            <span class="bg-white/50 px-1 rounded text-xs">
              {attempts}
            </span>
          )}
        </div>
      );
    },
    size: 140,
  },
  {
    accessorKey: "checked_at",
    header: () => $t("Last Check"),
    cell: ({ row }) => {
      const checkedAt = row.getValue("checked_at");

      return (
        <div class="flex items-center gap-2">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <span class="text-sm cursor-help">
                  {checkedAt ?
                    formatRelativeTime(checkedAt, { numeric: "auto" }, LocaleEnum.LT) :
                    $t('Never')}
                </span>
              </TooltipTrigger>
              <TooltipContent>
                <p>
                  {checkedAt ?
                    formatStaticTime(checkedAt, {
                      year: "numeric",
                      month: "short",
                      day: "numeric",
                      hour: "2-digit",
                      minute: "2-digit"
                    }, LocaleEnum.LT) :
                    $t('Document has never been synced')
                  }
                </p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <Button
            size="icon"
            variant="ghost"
            onClick={() => handleDocumentRefresh(row.original)}
            title={$t("refresh")}
            class="h-6 w-6"
          >
            <RefreshCwIcon class="h-3 w-3" />
          </Button>
        </div>
      );
    },
    size: 130,
    enableSorting: true,
  },
  createStandardActionsColumn<App.Entities.Document>("documents", {
    // canView: true,
    // canEdit: true,
    canDelete: true,
    // canRestore: true
  })
]);

// Simplified table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Document>>(() => {
  return {
    // Essential table configuration
    modelName,
    entityName,
    data: props.data,
    columns: columns.value,
    totalCount: props.meta.total,
    initialPage: props.meta.current_page,
    pageSize: props.meta.per_page,

    // Advanced features
    initialFilters: props.filters,
    initialSorting: props.sorting && props.sorting.length > 0 ? props.sorting : [{ id: "created_at", desc: true }],
    enableFiltering: true,
    enableColumnVisibility: true,

    // Page layout
    headerTitle: $t("Documents"),
    headerDescription: $t("Documents are automatically synchronized from SharePoint. Manual refresh is rarely needed - the system updates content intelligently in the background."),
    icon: Icons.DOCUMENT,
    canCreate: false,
    // breadcrumbs handled via usePageBreadcrumbs
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

const handleBulkSync = () => {
  bulkSyncLoading.value = true;

  router.post(route("documents.bulk-sync"), {}, {
    onSuccess: () => {
      bulkSyncLoading.value = false;
      // Reload the current page after bulk sync
      if (indexTablePageRef.value) {
        indexTablePageRef.value.reloadData();
      }
    },
    onError() {
      bulkSyncLoading.value = false;
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
