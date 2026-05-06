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

    <div class="hidden items-center gap-2 rounded-md border border-border bg-muted/40 px-3 py-1.5 text-xs text-muted-foreground lg:flex">
      <span>{{ $t("Environment") }}:</span>
      <Badge :variant="appEnvironment === 'production' ? 'default' : 'secondary'">
        {{ appEnvironmentLabel }}
      </Badge>
      <span class="mx-1 text-muted-foreground/50">|</span>
      <span>{{ $t("Work mode") }}:</span>
      <Badge :variant="appEnvironment === 'production' ? 'default' : 'secondary'">
        {{ appWorkModeLabel }}
      </Badge>
    </div>
  </IndexTablePage>
</template>

<script setup lang="ts">
import { h, ref, computed, watch, capitalize } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ExternalLinkIcon, RefreshCwIcon } from 'lucide-vue-next';

import type { Item } from '@/Features/Admin/SharepointFilePicker/picker';
import FilePicker from '@/Features/Admin/SharepointFilePicker/FilePicker.vue';
import Icons from '@/Types/Icons/regular';
import IndexTablePage from '@/Components/Layouts/IndexTablePage.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import DataTableFilter from '@/Components/ui/data-table/DataTableFilter.vue';
import { DateCell, TruncatedBadge, TruncatedLink, TruncatedText } from '@/Components/ui/data-table/cells';
import { LocaleEnum } from '@/Types/enums';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import type {
  IndexTablePageProps,
} from '@/Types/TableConfigTypes';
import {
  createDateColumn,
} from '@/Composables/useDataTableColumns';
import { createStandardActionsColumn } from '@/Composables/useTableActions';

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
  BreadcrumbHelpers.createBreadcrumbItem($t('Administravimas'), route('administration'), Icons.TYPE),
  BreadcrumbHelpers.createBreadcrumbItem($t('Dokumentai'), undefined, Icons.DOCUMENT),
]);

const loading = ref(false);
const bulkSyncLoading = ref(false);
const indexTablePageRef = ref<InstanceType<typeof IndexTablePage> | null>(null);
const page = usePage();

const appEnvironment = computed(() => String(page.props.app?.env ?? 'unknown').toLowerCase());
const appEnvironmentLabel = computed(() => {
  if (appEnvironment.value === 'production') return $t('Production');
  if (appEnvironment.value === 'local') return $t('Local');

  return appEnvironment.value;
});
const appWorkModeLabel = computed(() => {
  return appEnvironment.value === 'production' ? $t('Production mode') : $t('Development mode');
});

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
  const institutions = page.props.institutions || [];
  return institutions.map(institution => ({
    label: institution.short_name || institution.name,
    value: institution.id,
  }));
});

// Column definitions using Tanstack Table format and standardized column helpers
const columns = computed(() => [
  {
    accessorKey: 'title',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => {
      const title = row.getValue('title') as string;
      const hasUrl = row.original.anonymous_url;

      if (hasUrl) {
        return h('div', { class: 'flex items-start gap-1' }, [
          h(TruncatedLink, {
            href: row.original.anonymous_url,
            text: title,
            lines: 2,
            external: true,
            class: 'text-blue-600 hover:text-blue-800 text-sm leading-tight',
          }),
          h(ExternalLinkIcon, { class: 'h-3 w-3 mt-0.5 opacity-60 flex-shrink-0' }),
        ]);
      }

      return h(TruncatedText, {
        text: title,
        lines: 2,
        class: 'font-medium text-gray-600 text-sm leading-tight',
      });
    },
    size: 300,
    enableSorting: true,
  },
  {
    accessorKey: 'language',
    header: () => $t('lang'),
    cell: ({ row }) => {
      const language = row.getValue('language') as string;
      if (!language) return h(TruncatedText, { text: null });
      const display = language === 'Lietuvių' ? 'LT' : language === 'Anglų' ? 'EN' : language;
      return h(TruncatedBadge, { text: display, variant: 'secondary', class: 'text-xs' });
    },
    size: 70,
    enableSorting: true,
  },
  createDateColumn<App.Entities.Document>('document_date', {
    title: $t('date'),
    width: 100,
    enableSorting: true,
    format: { year: 'numeric', month: '2-digit', day: '2-digit' },
  }),
  {
    accessorKey: 'content_type',
    header: () => $t('content_type'),
    cell: ({ row }) => {
      const contentType = row.getValue('content_type') as string;
      if (!contentType) return h(TruncatedText, { text: null });

      const shortType = contentType
        .replace('Parlamento ', 'Parl. ')
        .replace(' sprendimas', ' spr.')
        .replace(' protokolas', ' prot.')
        .replace(' darbotvarkė', ' d.t.');

      return h(TruncatedBadge, {
        text: shortType,
        variant: 'secondary',
        class: 'text-xs',
      }, {
        default: () => h(TruncatedText, { text: contentType }),
      });
    },
    size: 150,
    enableSorting: true,
  },
  {
    accessorKey: 'institution_name_lt',
    header: () => $t('institution'),
    cell: ({ row }) => {
      const { institution } = row.original;
      if (!institution) return h(TruncatedText, { text: null });

      const shortName = institution.short_name || institution.name;
      return h(TruncatedBadge, {
        text: shortName,
        variant: 'secondary',
        class: 'text-xs font-medium text-indigo-600 bg-indigo-50',
      });
    },
    size: 120,
    enableSorting: true,
  },
  {
    accessorKey: 'sync_status',
    header: () => $t('Sync Status'),
    cell: ({ row }) => {
      const status = row.original.sync_status || 'pending';
      const attempts = row.original.sync_attempts || 0;
      const statusConfig = {
        pending: {
          color: 'text-gray-600',
          bgColor: 'bg-gray-100',
          dotColor: 'bg-gray-400',
          label: 'Pending',
          icon: '⏳',
        },
        imported: {
          color: 'text-indigo-600',
          bgColor: 'bg-indigo-50',
          dotColor: 'bg-indigo-500',
          label: 'Imported',
          icon: '📥',
        },
        syncing: {
          color: 'text-blue-600',
          bgColor: 'bg-blue-50',
          dotColor: 'bg-blue-500 animate-pulse',
          label: 'Syncing...',
          icon: '🔄',
        },
        success: {
          color: 'text-green-600',
          bgColor: 'bg-green-50',
          dotColor: 'bg-green-500',
          label: 'Success',
          icon: '✅',
        },
        failed: {
          color: 'text-red-600',
          bgColor: 'bg-red-50',
          dotColor: 'bg-red-500',
          label: 'Failed',
          icon: '❌',
        },
      };

      const config = statusConfig[status] || statusConfig['pending'];

      return h('div', {
        class: `inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium ${config.bgColor} ${config.color}`,
      }, [
        h('div', { class: `w-2 h-2 rounded-full ${config.dotColor}` }),
        h('span', {}, config.label),
        attempts > 1 ? h('span', { class: 'bg-white/50 px-1 rounded text-xs' }, String(attempts)) : null,
      ]);
    },
    size: 140,
  },
  {
    accessorKey: 'checked_at',
    header: () => $t('Last Check'),
    cell: ({ row }) => {
      const checkedAt = row.getValue('checked_at');

      return h('div', { class: 'flex items-center gap-2' }, [
        checkedAt
          ? h(DateCell, { date: checkedAt as string | Date, mode: 'relative' })
          : h(TruncatedText, { text: $t('Never') }),
        h(Button, {
          size: 'icon',
          variant: 'ghost',
          onClick: () => handleDocumentRefresh(row.original),
          title: $t('refresh'),
          class: 'h-6 w-6',
        }, () => h(RefreshCwIcon, { class: 'h-3 w-3' })),
      ]);
    },
    size: 130,
    enableSorting: true,
  },
  createStandardActionsColumn<App.Entities.Document>('documents', {
    canDelete: true,
  }),
]) as Array<ColumnDef<App.Entities.Document, any>>;

// Simplified table configuration using the new interfaces
const tableConfig = computed<IndexTablePageProps<App.Entities.Document>>(() => {
  return {
    modelName,
    entityName,
    data: props.data,
    columns: columns.value,
    totalCount: props.meta.total,
    initialPage: props.meta.current_page,
    pageSize: props.meta.per_page,

    initialFilters: props.filters,
    initialSorting: props.sorting && props.sorting.length > 0 ? props.sorting : [{ id: 'created_at', desc: true }],
    enableFiltering: true,
    enableColumnVisibility: true,

    headerTitle: $t('Documents'),
    headerDescription: $t('Documents are automatically synchronized from SharePoint. Manual refresh is rarely needed - the system updates content intelligently in the background.'),
    icon: Icons.DOCUMENT,
    canCreate: false,
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
  console.log('Documents data loaded:', data);
};

const handleSortingChange = (sorting) => {
  console.log('Sorting changed:', sorting);
};

const handlePageChange = (page) => {
  console.log('Page changed:', page);
};

const handleFilterChange = (filterKey, value) => {
  if (filterKey === 'content_type') {
    selectedContentType.value = value;
  }
  else if (filterKey === 'language') {
    selectedLanguage.value = value;
  }
  else if (filterKey === 'institution.id') {
    selectedInstitutionId.value = value;
  }
};

const handleDocumentPick = (items: Item[]) => {
  loading.value = true;

  const documents = items.map(item => ({
    name: item.name,
    site_id: item.sharepointIds?.siteId,
    list_id: item.sharepointIds?.listId,
    list_item_unique_id: item.sharepointIds?.listItemUniqueId,
  }));

  router.post(route('documents.store'), { documents }, {
    onSuccess: () => {
      loading.value = false;
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

  router.post(route('documents.refresh', document.id), {}, {
    onSuccess: () => {
      loading.value = false;
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

  router.post(route('documents.bulk-sync'), {}, {
    onSuccess: () => {
      bulkSyncLoading.value = false;
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
