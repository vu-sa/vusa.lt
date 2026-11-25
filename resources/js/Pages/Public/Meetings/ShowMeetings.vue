<template>
  <div class="meetings-page">
    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="$t('search.meeting_search_title')"
      :lead="$t('search.meeting_page_description')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Search, Filter Controls & View Mode -->
      <div class="space-y-4" data-nosnippet>
        <!-- Search Bar, Filter Toggle + View Mode -->
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
          <PublicSearchInput v-model="searchInput" :placeholder="$t('search.search_meetings_placeholder')" />

          <!-- Filter Button (opens filter popovers row) -->
          <button
            type="button"
            :class="[
              'inline-flex h-11 items-center justify-center gap-2 border px-5 text-xs font-bold uppercase tracking-wide transition-colors shrink-0',
              showFilterBar || activeFilterCount > 0
                ? 'border-brand text-brand bg-brand/5 hover:bg-brand/10'
                : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
            ]"
            @click="showFilterBar = !showFilterBar"
          >
            <IFluentFilter20Regular class="size-4" />
            <span>{{ $t('Filtrai') }}</span>
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

          <PublicSortMenu v-model="meetingSort" :options="sortOptions" default-value="relevance" />
        </div>

        <!-- Filter Popovers Bar (toggled by the filter button) -->
        <div
          v-show="showFilterBar"
          class="flex flex-wrap items-center gap-2 border-t border-border/60 pt-3"
        >
          <!-- Years Filter Popover -->
          <PublicFilterPopover
            :label="$t('search.years')"
            :options="yearOptions" field="year"
            :selected="selectedYears"
            trigger-class="h-9 px-3"
            @toggle="toggleYear"
            @clear="clearYears"
          />

          <!-- Tenant / Padalinys Filter Popover -->
          <PublicFilterPopover
            :label="$t('Padalinys')"
            :options="tenantOptions" field="tenant_shortname"
            :selected="filters.tenants || []"
            searchable
            :search-placeholder="`${$t('Ieškoti padalinio')}...`"
            trigger-class="h-9 px-3"
            @toggle="searchController.toggleTenant"
            @clear="clearTenants"
          />

          <!-- Institution Type Filter Popover -->
          <PublicFilterPopover
            v-if="institutionTypeOptions.length > 0"
            :label="$t('search.institution_type')"
            :options="institutionTypeOptions" field="institution_type_title"
            :selected="filters.institutionTypes || []"
            searchable
            :search-placeholder="`${$t('Ieškoti tipo')}...`"
            trigger-class="h-9 px-3"
            @toggle="searchController.toggleInstitutionType"
            @clear="clearInstitutionTypes"
          />

          <!-- Clear all button within filter bar -->
          <button
            v-if="hasActiveFilters"
            type="button"
            class="h-9 px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground hover:text-brand transition-colors inline-flex items-center gap-1"
            @click="clearAllFilters"
          >
            <IFluentDismiss16Regular class="size-3.5" />
            <span>{{ $t('Išvalyti visus') }}</span>
          </button>
        </div>

        <!-- Active Filter Tags -->
        <div
          v-if="hasActiveFilters"
          class="flex flex-wrap items-center gap-1.5 pt-1"
        >
          <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground mr-1">
            {{ $t('search.active_filters') }}:
          </span>

          <!-- Search query tag -->
          <TagChip
            v-if="hasActiveQuery"
            variant="muted"
            removable
            class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
            :label="`&quot;${filters.query.trim()}&quot;`"
            @remove="searchInput = ''"
          />

          <!-- Year tags -->
          <TagChip
            v-for="year in filters.years"
            :key="`year-${year}`"
            variant="muted"
            removable
            class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
            :label="String(year)"
            @remove="toggleYear(String(year))"
          />

          <!-- Tenant tags -->
          <TagChip
            v-for="tenant in filters.tenants"
            :key="`tenant-${tenant}`"
            variant="muted"
            removable
            class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
            :label="tenant"
            @remove="searchController.toggleTenant(tenant)"
          />

          <!-- Institution type tags -->
          <TagChip
            v-for="type in filters.institutionTypes"
            :key="`inst-type-${type}`"
            variant="muted"
            removable
            class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
            :label="type"
            @remove="searchController.toggleInstitutionType(type)"
          />

          <!-- Clear all -->
          <button
            type="button"
            class="text-xs font-semibold text-brand hover:underline ml-1"
            @click="clearAllFilters"
          >
            {{ $t('search.clear_all') }}
          </button>
        </div>
      </div>

      <!-- Results Count Bar -->
      <div class="mt-8 flex items-center justify-between border-b border-border pb-3">
        <div class="text-xs font-mono uppercase tracking-wider text-muted-foreground">
          <span v-if="isSearching">
            {{ $t('search.searching') }}
          </span>
          <span v-else>
            {{ totalHits }} {{ $t('search.results') }}
          </span>
        </div>

        <div v-if="isSearching" class="flex items-center gap-1.5 text-xs text-muted-foreground">
          <span class="size-2 animate-pulse bg-brand-fill" />
          <span>{{ $t('search.searching') }}</span>
        </div>
      </div>

      <!-- Results List -->
      <div class="mt-6">
        <!-- Initial Loading Skeletons -->
        <HairlineList v-if="isSearching && !hasResults" as="ul">
          <MeetingResultsSkeleton
            v-for="i in 6"
            :key="`skeleton-${i}`"
          />
        </HairlineList>

        <!-- Empty State -->
        <div
          v-else-if="!hasResults && !isSearching"
          class="border border-border bg-card p-12 text-center"
        >
          <div class="mx-auto max-w-md space-y-3">
            <h3 class="text-base font-bold text-foreground">
              {{ $t('search.no_meetings_found') }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('search.no_results_criteria') }}
            </p>
            <div v-if="hasActiveFilters" class="pt-2">
              <button
                type="button"
                :class="[
                  'inline-flex h-9 items-center justify-center border border-border bg-background px-4',
                  'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
                ]"
                @click="clearAllFilters"
              >
                {{ $t('search.reset_filters') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Results List -->
        <HairlineList v-else as="ul">
          <MeetingCompactListItem
            v-for="meeting in meetings"
            :key="meeting.id"
            :meeting
          />
        </HairlineList>

        <!-- Loading More Spinner / Skeleton -->
        <HairlineList v-if="isLoadingMore" as="ul" class="mt-4">
          <MeetingResultsSkeleton
            v-for="i in 2"
            :key="`more-skeleton-${i}`"
          />
        </HairlineList>

        <!-- Load More Button -->
        <div
          v-if="hasMoreResults && !isLoadingMore"
          class="mt-8 text-center"
        >
          <button
            type="button"
            :class="[
              'inline-flex h-11 items-center justify-center border border-border bg-card px-8',
              'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
            ]"
            @click="searchController.loadMore"
          >
            <span>{{ $t('search.load_more_results') }}</span>
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useDebounceFn, useStorage } from '@vueuse/core';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { PageTitleBand, PublicSearchInput, PublicSortMenu, type PublicSortOption } from '@/Components/Public/Base';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import TagChip from '@/Components/Brand/TagChip.vue';
import HairlineList from '@/Components/Public/Base/HairlineList.vue';
import PublicFilterPopover, { type FilterOption } from '@/Components/Public/Base/PublicFilterPopover.vue';
import MeetingCompactListItem from '@/Components/Public/Search/MeetingCompactListItem.vue';
import MeetingResultsSkeleton from '@/Components/Public/Search/MeetingResultsSkeleton.vue';
import { useMeetingSearch } from '@/Composables/useMeetingSearch';
import type { MeetingSearchSort } from '@/Types/MeetingSearchTypes';
import { TenantType } from '@/Types/enums';

import IFluentArrowSort24Regular from '~icons/fluent/arrow-sort-24-regular';
import IFluentArrowSortDownLines24Regular from '~icons/fluent/arrow-sort-down-lines-24-regular';
import IFluentArrowSortUpLines24Regular from '~icons/fluent/arrow-sort-up-lines-24-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentFilter20Regular from '~icons/fluent/filter-20-regular';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentCalendarLtr24Regular from '~icons/fluent/calendar-ltr-24-regular';

const page = usePage();

// Breadcrumbs in band placement per redesign
usePageBreadcrumbs(
  () => BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      $t('search.meeting_search_title'),
      undefined,
      IFluentCalendarLtr24Regular,
    ),
  ]),
  { placement: 'band' },
);

const searchController = useMeetingSearch();
const {
  results: meetings,
  isSearching,
  isLoadingMore,
  hasResults,
  hasMoreResults,
  totalHits,
  facets,
  filters,
} = searchController;

const showFilterBar = useStorage('vusa-show-filters-expanded', false);
const searchInput = ref('');

function applyCurrentTenantFilter(): void {
  const { tenant } = page.props;

  if (tenant?.type !== TenantType.Padalinys || !tenant.shortname) {
    return;
  }

  filters.value.tenants = [tenant.shortname];
}

// Computed eyebrow
const pageEyebrow = computed(() => {
  const tenantName = page.props.tenant?.name;
  return tenantName ? `${tenantName} · ${$t('search.meeting_search_title')}` : $t('search.meeting_search_title');
});

const sortOptions: PublicSortOption<MeetingSearchSort>[] = [
  { value: 'relevance', label: $t('Pagal aktualumą'), icon: IFluentArrowSort24Regular },
  { value: 'date_desc', label: $t('Naujausi pirmi'), icon: IFluentArrowSortDownLines24Regular },
  { value: 'date_asc', label: $t('Seniausi pirmi'), icon: IFluentArrowSortUpLines24Regular },
];

const meetingSort = computed<MeetingSearchSort>({
  get: () => filters.value.sort ?? 'relevance',
  set: searchController.setSortBy,
});

// Year options computed from facets
const yearFacet = computed(() => {
  return facets.value.find(f => f.field === 'year');
});

const yearOptions = computed<FilterOption[]>(() => {
  const facet = yearFacet.value;
  if (!facet?.values) return [];
  return [...facet.values]
    .sort((a, b) => Number(b.value) - Number(a.value))
    .map(v => ({
      value: String(v.value),
      label: String(v.value),
      count: v.count,
    }));
});

const selectedYears = computed<string[]>(() => {
  return (filters.value.years || []).map(String);
});

const toggleYear = (yearStr: string) => {
  const num = Number(yearStr);
  if (!isNaN(num)) {
    searchController.toggleYear(num);
  }
};

const clearYears = () => {
  searchController.setFilter('years', []);
};

// Tenant options computed from facets
const tenantFacet = computed(() => {
  return facets.value.find(f => f.field === 'tenant_shortname');
});

const tenantOptions = computed<FilterOption[]>(() => {
  const facet = tenantFacet.value;
  if (!facet?.values) return [];
  return [...facet.values]
    .sort((a, b) => b.count - a.count)
    .map(v => ({
      value: String(v.value),
      label: String(v.value),
      count: v.count,
    }));
});

const clearTenants = () => {
  searchController.setFilter('tenants', []);
};

// Institution type options computed from facets
const institutionTypeFacet = computed(() => {
  return facets.value.find(f => f.field === 'institution_type_title');
});

const institutionTypeOptions = computed<FilterOption[]>(() => {
  const facet = institutionTypeFacet.value;
  if (!facet?.values) return [];
  return facet.values.map(v => ({
    value: String(v.value),
    label: String(v.label || v.value),
    count: v.count,
  }));
});

const clearInstitutionTypes = () => {
  searchController.setFilter('institutionTypes', []);
};

// Active filter count
const activeFilterCount = computed(() => {
  const f = filters.value;
  let count = 0;
  if (f.years?.length) count += f.years.length;
  if (f.tenants?.length) count += f.tenants.length;
  if (f.institutionTypes?.length) count += f.institutionTypes.length;
  return count;
});

const hasActiveQuery = computed(() => {
  const q = filters.value.query?.trim();
  return Boolean(q && q !== '*');
});

const hasActiveFilters = computed(() => {
  return hasActiveQuery.value || activeFilterCount.value > 0;
});

// Clear all filters
const clearAllFilters = () => {
  searchInput.value = '';
  searchController.clearFilters();
  searchController.cancelPendingSearch?.();
  searchController.search('*', true);
};

// Debounced search on input change
const debouncedSearch = useDebounceFn((query: string) => {
  const trimmed = query.trim();
  if (trimmed) {
    searchController.search(trimmed, false);
  }
  else {
    searchController.search('*', false);
  }
}, 300);

watch(searchInput, (newQuery) => {
  debouncedSearch(newQuery);
});

watch(() => filters.value.query, (q) => {
  const displayQ = (q === '*' || !q) ? '' : q;
  if (displayQ !== searchInput.value) {
    searchInput.value = displayQ;
  }
});

// Extract initial params from URL on mount
const parseInitialUrlParams = () => {
  if (typeof window === 'undefined') return;
  const params = new URLSearchParams(window.location.search);

  const query = params.get('q');
  if (query) {
    searchInput.value = query;
    filters.value.query = query;
  }

  // Parse years
  const years: number[] = [];
  for (let i = 0; i < 10; i++) {
    const val = params.get(`years[${i}]`);
    if (val && !isNaN(Number(val))) {
      years.push(Number(val));
    }
    else {
      break;
    }
  }
  if (years.length > 0) {
    filters.value.years = years;
  }

  // Parse tenants
  const tenants: string[] = [];
  for (let i = 0; i < 29; i++) {
    const val = params.get(`tenants[${i}]`);
    if (val) {
      tenants.push(val);
    }
    else {
      break;
    }
  }
  if (tenants.length > 0) {
    filters.value.tenants = tenants;
  }

  // Parse institution types
  const types: string[] = [];
  for (let i = 0; i < 20; i++) {
    const val = params.get(`institutionTypes[${i}]`);
    if (val) {
      types.push(val);
    }
    else {
      break;
    }
  }
  if (types.length > 0) {
    filters.value.institutionTypes = types;
  }
};

onMounted(async () => {
  parseInitialUrlParams();
  applyCurrentTenantFilter();
  await searchController.initializeSearchClient();
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
