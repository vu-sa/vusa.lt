<template>
  <div class="documents-page">
    <Head>
      <title>{{ $t('search.document_page_title') }}</title>
      <meta name="description" :content="$t('search.document_page_description')">
    </Head>

    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="$t('Dokumentai')"
      :lead="$t('search.document_page_description')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Search, Quick Types + Filter Controls -->
      <div class="space-y-4">
        <!-- Quick Important Document Types Row -->
        <div
          v-if="importantContentTypes.length > 0"
          class="flex flex-wrap items-center gap-1.5"
        >
          <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground mr-1 flex items-center gap-1 shrink-0">
            <IFluentStar16Filled class="size-3 text-brand" />
            {{ $t('search.most_important') }}:
          </span>
          <button
            v-for="type in importantContentTypes"
            :key="`quick-${type}`"
            type="button"
            :class="[
              'h-8 px-3 text-xs font-bold uppercase tracking-wide transition-colors inline-flex items-center gap-1.5 border shrink-0',
              isContentTypeSelected(type)
                ? 'border-brand bg-brand/10 text-brand'
                : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
            ]"
            @click="searchController.toggleContentType(type)"
          >
            <span>{{ type }}</span>
            <span
              v-if="isContentTypeSelected(type)"
              class="size-1.5 bg-brand-fill rounded-none shrink-0"
            />
          </button>
        </div>

        <!-- Search Bar, Filter Toggle + View Mode -->
        <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center">
          <!-- Search Input -->
          <div class="relative min-w-0 flex-1">
            <IFluentSearch16Regular class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <input
              v-model="searchInput"
              type="text"
              :placeholder="`${$t('search.enter_search_or_browse')}...`"
              :class="[
                'h-11 w-full border border-border bg-background pl-10 pr-9 text-sm text-foreground',
                'placeholder:text-muted-foreground/70 transition-colors focus:border-brand focus:outline-none',
              ]"
            >
            <button
              v-if="searchInput"
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              @click="searchInput = ''"
            >
              <IFluentDismiss16Regular class="size-3.5" />
              <span class="sr-only">{{ $t('Išvalyti') }}</span>
            </button>
          </div>

          <div class="grid grid-cols-[minmax(0,1fr)_auto_auto] gap-2.5 lg:flex lg:items-center">
            <!-- Filter Button (opens filter popovers row) -->
            <button
              type="button"
              :class="[
                'inline-flex h-11 items-center justify-center gap-2 border px-3 sm:px-5 text-xs font-bold uppercase tracking-wide transition-colors',
                showFilterBar || activeFilterCount > 0
                  ? 'border-brand text-brand bg-brand/5 hover:bg-brand/10'
                  : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
              ]"
              @click="showFilterBar = !showFilterBar"
            >
              <IFluentFilter20Regular class="size-4" />
              <span class="max-[359px]:hidden">{{ $t('Filtrai') }}</span>
              <span
                v-if="activeFilterCount > 0"
                class="flex size-4 items-center justify-center bg-brand-fill text-brand-foreground text-[0.625rem] font-mono leading-none"
              >
                {{ activeFilterCount }}
              </span>
              <IFluentChevronDown16Regular
                class="size-3.5 transition-transform duration-200"
                :class="{ 'rotate-180': showFilterBar }"
              />
            </button>

            <!-- Sort Popover -->
            <Popover v-model:open="isSortPopoverOpen">
              <PopoverTrigger as-child>
                <button
                  type="button"
                  :class="[
                    'inline-flex h-11 shrink-0 items-center justify-between gap-2 border px-3.5 text-xs font-bold uppercase tracking-wide transition-colors',
                    filters.sort !== 'relevance'
                      ? 'border-brand bg-brand/5 text-brand hover:bg-brand/10'
                      : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
                  ]"
                  :aria-label="$t('Rikiuoti')"
                >
                  <component :is="currentSortIcon" class="size-3.5" />
                  <span class="hidden lg:inline">{{ $t('Rikiuoti') }}</span>
                  <IFluentChevronDown16Regular
                    class="size-3.5 transition-transform duration-200"
                    :class="{ 'rotate-180': isSortPopoverOpen }"
                  />
                </button>
              </PopoverTrigger>

              <PopoverContent
                align="end"
                class="z-50 w-64 border border-border bg-popover p-0 text-popover-foreground shadow-lg"
              >
                <div class="border-b border-border px-3.5 py-2.5 text-xs font-bold uppercase tracking-wider text-foreground">
                  {{ $t('Rikiuoti pagal') }}
                </div>
                <div class="divide-y divide-border/40">
                  <button
                    v-for="option in sortOptions"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="filters.sort === option.value"
                    :class="[
                      'flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm font-medium',
                      'text-foreground transition-colors hover:bg-secondary/60 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
                    ]"
                    @click="selectSort(option.value)"
                  >
                    <div class="flex items-center gap-2">
                      <component
                        :is="getSortIcon(option.value)"
                        class="size-4 text-muted-foreground"
                      />
                      <span>{{ option.label }}</span>
                    </div>
                    <IFluentCheckmark16Filled
                      v-if="filters.sort === option.value"
                      class="size-4 text-brand"
                    />
                  </button>
                </div>
              </PopoverContent>
            </Popover>

            <!-- View Mode Toggle -->
            <div class="inline-flex h-11 border border-border bg-background p-0.5">
              <button
                type="button"
                :class="[
                  'h-full px-3 text-xs font-bold uppercase tracking-wide transition-colors flex items-center gap-1.5 justify-center',
                  viewMode === 'list'
                    ? 'bg-brand-fill text-brand-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                :title="$t('search.view_mode_list')"
                @click="searchController.setViewMode('list')"
              >
                <IFluentList20Regular class="size-4" />
                <span class="hidden lg:inline">{{ $t('search.view_mode_list') }}</span>
              </button>
              <button
                type="button"
                :class="[
                  'h-full px-3 text-xs font-bold uppercase tracking-wide transition-colors flex items-center gap-1.5 justify-center',
                  viewMode === 'compact'
                    ? 'bg-brand-fill text-brand-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                :title="$t('search.view_mode_compact')"
                @click="searchController.setViewMode('compact')"
              >
                <IFluentRowChild20Regular class="size-4" />
                <span class="hidden lg:inline">{{ $t('search.view_mode_compact') }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Filter Popovers Bar (toggled by the filter button) -->
        <div
          v-show="showFilterBar"
          class="flex flex-wrap items-center gap-2 border-t border-border/60 pt-3"
        >
          <!-- Effect Status Filter: defaults to Galioja + Nenustatyta, hiding "Negalioja" -->
          <PublicFilterPopover
            :label="$t('Galiojimas')"
            :options="effectStatusOptions"
            :selected="filters.effectStatuses || []"
            trigger-class="h-9 px-3"
            @toggle="toggleEffectStatusOption"
            @clear="searchController.clearEffectStatuses"
          />

          <!-- Content Type Filter Popover -->
          <PublicFilterPopover
            :label="$t('search.document_type')"
            :options="contentTypeOptions"
            :selected="filters.contentTypes || []"
            searchable
            :search-placeholder="`${$t('Ieškoti tipo')}...`"
            trigger-class="h-9 px-3"
            @toggle="searchController.toggleContentType"
            @clear="clearContentTypes"
          />

          <!-- Padalinys Filter Popover -->
          <PublicFilterPopover
            :label="$t('Padalinys')"
            :options="tenantOptions"
            :selected="filters.tenants || []"
            searchable
            :search-placeholder="`${$t('Ieškoti padalinio')}...`"
            trigger-class="h-9 px-3"
            @toggle="searchController.toggleTenant"
            @clear="clearTenants"
          />

          <!-- Language Filter Popover -->
          <PublicFilterPopover
            :label="$t('search.language')"
            :options="languageOptions"
            :selected="filters.languages || []"
            trigger-class="h-9 px-3"
            @toggle="searchController.toggleLanguage"
            @clear="clearLanguages"
          />
        </div>

        <!-- Active Filter Chips & Hit Count -->
        <div class="min-h-9 flex flex-wrap items-center justify-between gap-3 border-t border-border/40 pt-2.5">
          <!-- Active Filter Chips -->
          <div class="flex min-h-7 flex-wrap items-center gap-2">
            <template v-if="hasActiveFilters">
              <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground mr-1">
                {{ $t('Aktyvūs filtrai') }}:
              </span>

              <!-- Search query chip -->
              <TagChip
                v-if="hasActiveQuery"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="`&quot;${filters.query.trim()}&quot;`"
                @remove="searchInput = ''"
              />

              <!-- Content Type chips -->
              <TagChip
                v-for="ct in filters.contentTypes || []"
                :key="`ct-${ct}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="ct"
                @remove="searchController.toggleContentType(ct)"
              />

              <!-- Tenant chips -->
              <TagChip
                v-for="t in filters.tenants || []"
                :key="`t-${t}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="t"
                @remove="searchController.toggleTenant(t)"
              />

              <!-- Language chips -->
              <TagChip
                v-for="l in filters.languages || []"
                :key="`lang-${l}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="getLanguageLabel(l)"
                @remove="searchController.toggleLanguage(l)"
              />

              <!-- Clear all button -->
              <button
                type="button"
                class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-muted-foreground transition-colors hover:text-brand ml-1"
                @click="clearAllFilters"
              >
                <IFluentDelete20Regular class="size-3.5" />
                <span>{{ $t('Išvalyti visus') }}</span>
              </button>
            </template>
            <div v-else class="h-7" />
          </div>

          <!-- Total count indicator -->
          <div class="text-xs font-mono uppercase tracking-wider text-muted-foreground shrink-0">
            <template v-if="!isSearching">
              {{ $t('Rasta :count dokumentų', { count: totalHits || 0 }) }}
            </template>
          </div>
        </div>
      </div>

      <!-- Results Section -->
      <div class="mt-8">
        <!-- Initial Loading State -->
        <HairlineList v-if="isSearching && documents.length === 0" as="ul">
          <DocumentResultsSkeleton
            v-for="n in (viewMode === 'compact' ? 10 : 5)"
            :key="n"
            :view-mode
          />
        </HairlineList>

        <!-- Empty State -->
        <div
          v-else-if="!isSearching && documents.length === 0"
          class="border border-dashed border-border py-16 text-center"
        >
          <div class="mx-auto flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground">
            <IFluentDocumentMultiple24Regular class="size-6" />
          </div>
          <h3 class="mt-4 text-base font-bold text-foreground">
            {{ $t('search.no_documents_found') }}
          </h3>
          <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
            {{ $t('Pagal pasirinktus kriterijus dokumentų nerasta. Pabandykite pakeisti paieškos frazę arba išvalyti filtrus.') }}
          </p>
          <div v-if="hasActiveFilters" class="mt-6">
            <Button
              variant="brand-outline"
              size="public-sm"
              @click="clearAllFilters"
            >
              {{ $t('Išvalyti filtrus') }}
            </Button>
          </div>
        </div>

        <!-- Document Results List -->
        <div v-else>
          <HairlineList as="ul">
            <template v-if="viewMode === 'list'">
              <DocumentListItem
                v-for="item in documents"
                :key="item.id"
                :document="item"
              />
            </template>
            <template v-else>
              <DocumentCompactListItem
                v-for="item in documents"
                :key="item.id"
                :document="item"
              />
            </template>
          </HairlineList>

          <!-- Load More Button -->
          <div
            v-if="hasMoreResults"
            class="mt-12 flex justify-center"
          >
            <Button
              variant="brand-outline"
              size="public"
              :disabled="isLoadingMore"
              @click="searchController.loadMore"
            >
              <IFluentArrowSync20Regular
                v-if="isLoadingMore"
                class="size-4 animate-spin"
              />
              <span v-if="isLoadingMore">{{ $t('Kraunama...') }}</span>
              <span v-else>{{ $t('Rodyti daugiau dokumentų') }}</span>
            </Button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { useStorage, useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useDocumentSearch } from '@/Composables/useDocumentSearch';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import TagChip from '@/Components/Public/Base/TagChip.vue';
import HairlineList from '@/Components/Public/Base/HairlineList.vue';
import PublicFilterPopover, { type FilterOption } from '@/Components/Public/Base/PublicFilterPopover.vue';
import DocumentListItem from '@/Components/Public/Search/DocumentListItem.vue';
import DocumentCompactListItem from '@/Components/Public/Search/DocumentCompactListItem.vue';
import DocumentResultsSkeleton from '@/Components/Public/Search/DocumentResultsSkeleton.vue';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import type { DocumentFacet, DocumentSearchSort } from '@/Types/DocumentSearchTypes';
import { TenantType } from '@/Types/enums';
import IFluentArrowSort24Regular from '~icons/fluent/arrow-sort-24-regular';
import IFluentArrowSortDownLines24Regular from '~icons/fluent/arrow-sort-down-lines-24-regular';
import IFluentArrowSortUpLines24Regular from '~icons/fluent/arrow-sort-up-lines-24-regular';
import IFluentArrowSync20Regular from '~icons/fluent/arrow-sync-20-regular';
import IFluentCheckmark16Filled from '~icons/fluent/checkmark-16-filled';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentDelete20Regular from '~icons/fluent/delete-20-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentDocument16Regular from '~icons/fluent/document-16-regular';
import IFluentDocumentMultiple24Regular from '~icons/fluent/document-multiple-24-regular';
import IFluentFilter20Regular from '~icons/fluent/filter-20-regular';
import IFluentList20Regular from '~icons/fluent/text-bullet-list-square-20-regular';
import IFluentRowChild20Regular from '~icons/fluent/row-child-20-regular';
import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentStar16Filled from '~icons/fluent/star-16-filled';

interface Props {
  allContentTypes?: string[];
  importantContentTypes?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  allContentTypes: () => [],
  importantContentTypes: () => [],
});

const page = usePage();

// Breadcrumbs in band placement
usePageBreadcrumbs(
  () => BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      'Dokumentai',
      undefined,
      IFluentDocument16Regular,
    ),
  ]),
  { placement: 'band' },
);

const showFilterBar = useStorage('vusa-show-filters-expanded', false);
const isSortPopoverOpen = ref(false);

const pageEyebrow = computed(() => {
  const tenant = page.props.tenant as { shortname?: string } | undefined;
  return tenant?.shortname ?? 'VU SA';
});

// Initialize document search controller
const searchController = useDocumentSearch();
const {
  results: documents,
  isSearching,
  isLoadingMore,
  hasMoreResults,
  totalHits,
  facets,
  filters,
  viewMode,
} = searchController;

const searchInput = ref(
  filters.value.query && filters.value.query !== '*' ? filters.value.query : '',
);

function applyCurrentTenantFilter(): void {
  const tenant = page.props.tenant;

  if (tenant?.type !== TenantType.Padalinys || !tenant.shortname) {
    return;
  }

  filters.value.tenants = [tenant.shortname];
}

const sortOptions: Array<{ value: DocumentSearchSort; label: string }> = [
  { value: 'relevance', label: $t('Pagal aktualumą') },
  { value: 'date_desc', label: $t('Naujausi pirmi') },
  { value: 'date_asc', label: $t('Seniausi pirmi') },
];

const selectSort = (newSortBy: DocumentSearchSort) => {
  searchController.setSortBy(newSortBy);
  isSortPopoverOpen.value = false;
};

const getSortIcon = (mode: DocumentSearchSort | undefined) => {
  switch (mode) {
    case 'date_desc':
      return IFluentArrowSortDownLines24Regular;
    case 'date_asc':
      return IFluentArrowSortUpLines24Regular;
    case 'relevance':
    default:
      return IFluentArrowSort24Regular;
  }
};

const currentSortIcon = computed(() => getSortIcon(filters.value.sort));

// Sync search input with debounce to controller
const debouncedSearch = useDebounceFn((query: string) => {
  const trimmed = query.trim();
  if (trimmed) {
    searchController.search(trimmed, false);
  }
  else {
    searchController.search('*', false);
  }
}, 250);

watch(searchInput, (newQuery) => {
  debouncedSearch(newQuery);
});

// Watch controller query (e.g. from URL or reset) to sync local input
watch(() => filters.value.query, (q) => {
  const displayQ = (q === '*' || !q) ? '' : q;
  if (displayQ !== searchInput.value) {
    searchInput.value = displayQ;
  }
});

const isContentTypeSelected = (type: string): boolean => {
  return filters.value.contentTypes?.includes(type) ?? false;
};

// The three states calculateIsInEffect() can produce (see DocumentSearchTypes.ts). Counts
// for true/false come straight from the same is_in_effect facet the main search already
// requests — same as every other filter here, its counts are post-filter (whichever
// statuses are currently hidden read as absent, not 0). "unknown" has no facet value of its
// own (the field is simply absent for those documents), so it's left uncounted rather than
// guessed at.
const effectStatusOptions = computed<FilterOption[]>(() => {
  const facetList = facets.value as DocumentFacet[] | undefined;
  const facet = facetList?.find(f => f.field === 'is_in_effect');
  const trueCount = facet?.values.find(v => v.value === 'true')?.count;
  const falseCount = facet?.values.find(v => v.value === 'false')?.count;

  return [
    { value: 'true', label: $t('Galioja'), count: trueCount },
    { value: 'false', label: $t('Negalioja'), count: falseCount },
    { value: 'unknown', label: $t('Nenustatyta') },
  ];
});

// PublicFilterPopover's `toggle` emits a plain string; narrow it back to the three
// known values before handing it to the controller.
const toggleEffectStatusOption = (value: string) => {
  if (value === 'true' || value === 'false' || value === 'unknown') {
    searchController.toggleEffectStatus(value);
  }
};

// Content type options: starred when important, ordered most-abundant first once counts
// are known (falls back to important-first + alphabetical before facets load).
const contentTypeOptions = computed<FilterOption[]>(() => {
  const facetList = facets.value as DocumentFacet[] | undefined;
  const facet = facetList?.find(f => f.field === 'content_type');
  const importantSet = new Set(props.importantContentTypes);

  let options: FilterOption[] = [];
  if (facet?.values?.length) {
    options = facet.values.map(v => ({
      label: v.label || v.value,
      value: v.value,
      count: v.count,
      starred: importantSet.has(v.value),
    }));
  }
  else if (props.allContentTypes.length) {
    options = props.allContentTypes.map(type => ({
      label: type,
      value: type,
      starred: importantSet.has(type),
    }));
  }

  return options.sort((a, b) => {
    // Starred (important) types lead regardless of count; each group is then
    // ordered most-abundant first.
    if (!!a.starred !== !!b.starred) return a.starred ? -1 : 1;
    if (a.count !== undefined && b.count !== undefined) {
      return b.count - a.count;
    }
    return a.label.localeCompare(b.label);
  });
});

// Tenant options from facets
const tenantOptions = computed<FilterOption[]>(() => {
  const facetList = facets.value as DocumentFacet[] | undefined;
  const facet = facetList?.find(f => f.field === 'tenant_shortname');
  if (!facet?.values?.length) return [];
  return facet.values.map(v => ({
    label: v.label || v.value,
    value: v.value,
    count: v.count,
  }));
});

// Language options from facets
const languageOptions = computed<FilterOption[]>(() => {
  const facetList = facets.value as DocumentFacet[] | undefined;
  const facet = facetList?.find(f => f.field === 'language');
  if (facet?.values?.length) {
    return facet.values.map(v => ({
      label: getLanguageLabel(v.value),
      value: v.value,
      count: v.count,
    }));
  }
  return [
    { label: 'Lietuvių', value: 'lt' },
    { label: 'English', value: 'en' },
  ];
});

const getLanguageLabel = (code: string): string => {
  const map: Record<string, string> = {
    lt: 'Lietuvių',
    en: 'English',
  };
  return map[code] || code.toUpperCase();
};

const activeFilterCount = computed(() => {
  const f = filters.value;
  return (f.contentTypes?.length || 0)
    + (f.tenants?.length || 0)
    + (f.languages?.length || 0);
});

const hasActiveQuery = computed(() => {
  const q = filters.value.query?.trim();
  return Boolean(q && q !== '*');
});

const hasActiveFilters = computed(() => {
  return hasActiveQuery.value || activeFilterCount.value > 0;
});

const clearContentTypes = () => {
  searchController.setFilter('contentTypes', []);
};

const clearTenants = () => {
  searchController.setFilter('tenants', []);
};

const clearLanguages = () => {
  searchController.setFilter('languages', []);
};

const clearAllFilters = () => {
  searchInput.value = '';
  searchController.clearFilters();
  searchController.cancelPendingSearch?.();
  searchController.search('*', true);
};

onMounted(async () => {
  await searchController.initializeSearchClient();
  applyCurrentTenantFilter();
  await searchController.loadInitialFacets();

  const initialQuery = filters.value.query?.trim();
  if (initialQuery && initialQuery !== '*') {
    searchController.search(initialQuery, true);
  }
  else {
    searchController.search('*', true);
  }
});
</script>
