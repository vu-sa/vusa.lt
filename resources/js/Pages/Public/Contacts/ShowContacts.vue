<template>
  <div class="contacts-page">
    <Head>
      <title>{{ $t('search.institution_page_title') }}</title>
      <meta name="description" :content="$t('search.institution_page_description')">
    </Head>

    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="$t('Kontaktai')"
      :lead="$t('search.institution_page_description')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>

      <template #actions>
        <SmartLink
          :href="route('contacts.studentRepresentatives', { subdomain: 'www', lang: $page.props.app?.locale })"
          :class="[
            'inline-flex h-10 items-center gap-1.5 border border-border bg-background px-4',
            'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
          ]"
        >
          <span>{{ $t('VU SA studentų atstovai (-ės)') }}</span>
          <IFluentArrowRight16Regular class="size-3.5" />
        </SmartLink>
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Search, Filter Controls & View Mode -->
      <div class="space-y-4">
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
          <!-- Search Input -->
          <div class="relative min-w-0 flex-1">
            <IFluentSearch16Regular class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <input
              v-model="searchInput"
              type="text"
              :placeholder="`${$t('search.search_institutions_placeholder')}`"
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
        </div>

        <!-- Filter Popovers Bar (toggled by the filter button) -->
        <div
          v-show="showFilterBar"
          class="flex flex-wrap items-center gap-2 border-t border-border/60 pt-3"
        >
          <!-- Tenant / Padalinys Filter Popover -->
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

          <!-- Institution Type Filter Popover -->
          <PublicFilterPopover
            v-if="typeOptions.length > 0"
            :label="$t('search.institution_type')"
            :options="typeOptions"
            :selected="filters.types || []"
            searchable
            :search-placeholder="`${$t('Ieškoti tipo')}...`"
            trigger-class="h-9 px-3"
            @toggle="searchController.toggleType"
            @clear="clearTypes"
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
            v-for="type in filters.types"
            :key="`type-${type}`"
            variant="muted"
            removable
            class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
            :label="getTypeLabel(type)"
            @remove="searchController.toggleType(type)"
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

      <!-- Results -->
      <div class="mt-6">
        <!-- Initial Loading Skeletons -->
        <div v-if="isSearching && !hasResults" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
          <InstitutionResultsSkeleton
            v-for="i in 6"
            :key="`skeleton-${i}`"
            view-mode="grid"
          />
        </div>

        <!-- Empty State -->
        <div
          v-else-if="!hasResults && !isSearching"
          class="border border-border bg-card p-12 text-center"
        >
          <div class="mx-auto max-w-md space-y-3">
            <h3 class="text-base font-bold text-foreground">
              {{ $t('search.no_institutions_found') }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ $t('search.no_institutions_criteria') }}
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

        <!-- Results Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
          <template v-for="institution in institutions" :key="institution.id">
            <StudentRepInstitutionCard
              v-if="isStudentRepInstitution(institution)"
              :institution
            />
            <NewInstitutionCard
              v-else
              :institution
              show-metadata
            />
          </template>
        </div>

        <!-- Loading More Skeletons -->
        <div v-if="isLoadingMore" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
          <InstitutionResultsSkeleton
            v-for="i in 3"
            :key="`more-skeleton-${i}`"
            view-mode="grid"
          />
        </div>

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
import { usePage, Head } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useDebounceFn, useStorage } from '@vueuse/core';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useInstitutionSearch } from '@/Composables/useInstitutionSearch';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import TagChip from '@/Components/Public/Base/TagChip.vue';
import PublicFilterPopover, { type FilterOption } from '@/Components/Public/Base/PublicFilterPopover.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import NewInstitutionCard from '@/Components/Cards/NewInstitutionCard.vue';
import StudentRepInstitutionCard from '@/Components/Cards/StudentRepInstitutionCard.vue';
import InstitutionResultsSkeleton from '@/Components/Public/Search/InstitutionResultsSkeleton.vue';
import { TenantType } from '@/Types/enums';
import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentFilter20Regular from '~icons/fluent/filter-20-regular';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';
import IFluentPeople16Regular from '~icons/fluent/people-16-regular';

interface Props {
  institutionTypes?: Record<string, string>;
  studentRepTypeSlugs?: string[];
}

interface SearchInstitutionItem {
  id: string;
  is_student_representation?: boolean;
  type_slugs?: string[];
  types?: Array<{ slug?: string }>;
}

const props = withDefaults(defineProps<Props>(), {
  institutionTypes: () => ({}),
  studentRepTypeSlugs: () => ['studentu-atstovu-organas'],
});

const isStudentRepInstitution = (inst: SearchInstitutionItem): boolean => {
  if (inst.is_student_representation === true) {
    return true;
  }
  const slugs: string[] = inst.type_slugs || inst.types?.map(t => t.slug).filter((s): s is string => Boolean(s)) || [];
  return slugs.some(slug => props.studentRepTypeSlugs.includes(slug));
};

const page = usePage();
const searchController = useInstitutionSearch();
const {
  results: institutions,
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
  return tenantName ? `${tenantName} · ${$t('Kontaktai')}` : $t('Kontaktai');
});

// Breadcrumbs in band placement per redesign
usePageBreadcrumbs(
  () => BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      $t('Kontaktai'),
      undefined,
      IFluentPeople16Regular,
    ),
  ]),
  { placement: 'band' },
);

// Type label resolver
const getTypeLabel = (typeSlug: string) => {
  return props.institutionTypes[typeSlug] || typeSlug;
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
const typeFacet = computed(() => {
  return facets.value.find(f => f.field === 'type_slugs');
});

const typeOptions = computed<FilterOption[]>(() => {
  const facet = typeFacet.value;
  if (!facet?.values) return [];
  return facet.values.map(v => ({
    value: String(v.value),
    label: props.institutionTypes[String(v.value)] || String(v.value),
    count: v.count,
  }));
});

const clearTypes = () => {
  searchController.setFilter('types', []);
};

// Active filter count
const activeFilterCount = computed(() => {
  const f = filters.value;
  let count = 0;
  if (f.tenants?.length) count += f.tenants.length;
  if (f.types?.length) count += f.types.length;
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
}, 200);

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

  // Parse types
  const types: string[] = [];
  for (let i = 0; i < 29; i++) {
    const val = params.get(`types[${i}]`);
    if (val) {
      types.push(val);
    }
    else {
      break;
    }
  }
  if (types.length > 0) {
    filters.value.types = types;
  }
};

onMounted(async () => {
  parseInitialUrlParams();
  applyCurrentTenantFilter();
  await searchController.initializeSearchClient();

  const initialQuery = filters.value.query?.trim();
  if (initialQuery && initialQuery !== '*') {
    searchController.search(initialQuery, true);
  }
  else {
    searchController.search('*', true);
  }
});
</script>
