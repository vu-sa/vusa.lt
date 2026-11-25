<template>
  <div class="news-archive-page">
    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="$t('Naujienos')"
      :lead="$t('seo.news_archive_description')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Search + Filter Controls; nosnippet keeps "Filtrai · Rikiuoti" out of Google's snippet. -->
      <div class="space-y-4" data-nosnippet>
        <!-- Search Bar and Filters Button in the same row on desktop -->
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
          <PublicSearchInput
            v-model="query"
            :placeholder="$t('news.archive.search_placeholder')"
            :aria-label="$t('news.archive.search_placeholder')"
          />

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

          <PublicSortMenu v-model="sortBy" :options="sortOptions" default-value="relevance" />
        </div>

        <!-- Filter Popovers Bar (toggled by the filter button) -->
        <div
          v-show="showFilterBar"
          class="flex flex-wrap items-center gap-2 border-t border-border/60 pt-3"
        >
          <!-- Padalinys Filter Popover -->
          <PublicFilterPopover
            :label="$t('Padalinys')"
            :options="tenantOptions" field="tenant_shortname"
            :selected="selectedTenants"
            searchable
            :search-placeholder="`${$t('news.archive.search_unit')}...`"
            trigger-class="h-9 px-3"
            @toggle="toggleTenant"
            @clear="selectedTenants = []"
          />

          <!-- Year Filter Popover -->
          <PublicFilterPopover
            :label="$t('Metai')"
            :options="yearOptions" field="year"
            :selected="selectedYears"
            searchable
            :search-placeholder="`${$t('news.archive.search_year')}...`"
            trigger-class="h-9 px-3"
            @toggle="toggleYear"
            @clear="selectedYears = []"
          />

          <!-- Tag Filter Popover (if tags exist) -->
          <PublicFilterPopover
            v-if="tagOptions.length > 0"
            :label="$t('Žymos')"
            :options="tagOptions" field="tag_names"
            :selected="selectedTags"
            searchable
            :search-placeholder="`${$t('news.archive.search_tag')}...`"
            trigger-class="h-9 px-3"
            @toggle="toggleTag"
            @clear="selectedTags = []"
          />
        </div>

        <!-- Active Filter Chips & Hit Count (space reserved to prevent layout shift on clear) -->
        <div class="min-h-9 flex flex-wrap items-center justify-between gap-3 border-t border-border/40 pt-2.5">
          <!-- Active Filter Chips or reserved empty spacer -->
          <div class="flex min-h-7 flex-wrap items-center gap-2">
            <template v-if="hasActiveFilters">
              <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground mr-1">
                {{ $t('news.archive.active_filters') }}:
              </span>

              <!-- Search term chip -->
              <TagChip
                v-if="query.trim()"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="`&quot;${query.trim()}&quot;`"
                @remove="query = ''"
              />

              <!-- Tenant chips -->
              <TagChip
                v-for="t in selectedTenants"
                :key="`tenant-${t}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="t"
                @remove="toggleTenant(t)"
              />

              <!-- Year chips -->
              <TagChip
                v-for="yr in selectedYears"
                :key="`year-${yr}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="String(yr)"
                @remove="toggleYear(yr)"
              />

              <!-- Tag chips -->
              <TagChip
                v-for="tg in selectedTags"
                :key="`tag-${tg}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="tg"
                @remove="toggleTag(tg)"
              />

              <!-- Clear all button -->
              <button
                type="button"
                class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-muted-foreground transition-colors hover:text-brand ml-1"
                @click="clearFilters"
              >
                <IFluentDelete20Regular class="size-3.5" />
                <span>{{ $t('Išvalyti visus') }}</span>
              </button>
            </template>
            <div v-else class="h-7" />
          </div>

          <!-- Total count indicator -->
          <div class="text-xs font-mono uppercase tracking-wider text-muted-foreground shrink-0">
            <template v-if="!isLoading">
              {{ $t('Rasta :count naujienų', { count: totalHits }) }}
            </template>
          </div>
        </div>
      </div>

      <!-- Results Section -->
      <div class="mt-8">
        <!-- Initial Loading State -->
        <div
          v-if="isLoading && news.length === 0"
          class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3"
        >
          <div
            v-for="n in 6"
            :key="n"
            class="flex flex-col animate-pulse"
          >
            <div class="aspect-[16/9] bg-secondary border border-border" />
            <div class="mt-4 h-5 w-3/4 bg-secondary" />
            <div class="mt-2 h-4 w-1/2 bg-secondary" />
            <div class="mt-4 h-4 w-1/4 bg-secondary" />
          </div>
        </div>

        <!-- Empty State -->
        <PublicEmptyState
          v-else-if="!isLoading && news.length === 0"
          :title="$t('news.archive.no_news')"
          :description="$t('news.archive.no_news_description')"
        >
          <template #icon>
            <IFluentNews24Regular aria-hidden="true" />
          </template>
          <template v-if="hasActiveFilters" #default>
            <Button
              voice="brand"
              variant="outline"
              size="sm"
              @click="clearFilters"
            >
              {{ $t('news.archive.clear_filters') }}
            </Button>
          </template>
        </PublicEmptyState>

        <!-- News Results -->
        <div v-else class="space-y-12">
          <!-- Featured Lead Article (always the first article) -->
          <div v-if="featuredArticle" class="border-b border-border pb-12">
            <NewsCard
              :news="featuredArticle"
              size="featured"
              heading-tag="h2"
              eager
              show-excerpt
            />
          </div>

          <!-- News Grid for remaining articles -->
          <div v-if="gridArticles.length > 0" class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            <NewsCard
              v-for="item in gridArticles"
              :key="item.id"
              :news="item"
              size="sm"
              heading-tag="h2"
              show-excerpt
            />
          </div>

          <!-- Load More Button -->
          <div
            v-if="hasMore"
            class="mt-12 flex justify-center"
          >
            <Button
              voice="brand"
              variant="outline"
              size="lg"
              :disabled="isLoadingMore"
              @click="loadMore"
            >
              <IFluentArrowSync20Regular
                v-if="isLoadingMore"
                class="size-4 animate-spin"
              />
              <span v-if="isLoadingMore">{{ $t('Kraunama...') }}</span>
              <span v-else>{{ $t('Rodyti daugiau naujienų') }}</span>
            </Button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useNewsSearch, type NewsSearchSort, type FacetOption } from '@/Composables/useNewsSearch';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import { PageTitleBand, PublicEmptyState, PublicSearchInput, PublicSortMenu, type PublicSortOption } from '@/Components/Public/Base';
import TagChip from '@/Components/Brand/TagChip.vue';
import PublicFilterPopover, { type FilterOption } from '@/Components/Public/Base/PublicFilterPopover.vue';
import NewsCard from '@/Components/Public/News/NewsCard.vue';
import { Button } from '@/Components/ui/button';
import type { NewsItem } from '@/Types/contentParts';

import IFluentNews24Regular from '~icons/fluent/news-24-regular';
import IFluentArrowSync20Regular from '~icons/fluent/arrow-sync-20-regular';
import IFluentFilter20Regular from '~icons/fluent/filter-20-regular';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentDelete20Regular from '~icons/fluent/delete-20-regular';
import IFluentArrowSort24Regular from '~icons/fluent/arrow-sort-24-regular';
import IFluentArrowSortDownLines24Regular from '~icons/fluent/arrow-sort-down-lines-24-regular';
import IFluentArrowSortUpLines24Regular from '~icons/fluent/arrow-sort-up-lines-24-regular';

// Breadcrumbs in band placement per redesign
usePageBreadcrumbs(
  () => BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      'Naujienos',
      undefined,
      IFluentNews24Regular,
    ),
  ]),
  { placement: 'band' },
);

const page = usePage();

const props = defineProps<{
  news?: {
    data: NewsItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    path: string;
    links: unknown[];
  };
  currentTag?: App.Entities.Tag | null;
  allTenants?: Array<{ id: number; shortname: string }>;
}>();

const showFilterBar = useStorage('vusa-show-filters-expanded', false);

const initialTagName = computed<string | undefined>(() => {
  if (!props.currentTag) return undefined;
  const tagObj = props.currentTag as unknown as { name?: string | Record<string, string> };
  if (typeof tagObj.name === 'object' && tagObj.name !== null) {
    const locale = (page.props.app as { locale?: string })?.locale || 'lt';
    return tagObj.name[locale] || tagObj.name.lt || tagObj.name.en;
  }
  return typeof tagObj.name === 'string' ? tagObj.name : undefined;
});

const pageEyebrow = computed(() => {
  const tenant = page.props.tenant as { shortname?: string } | undefined;
  return tenant?.shortname ?? 'VU SA';
});

const {
  query,
  selectedTenants,
  selectedYears,
  selectedTags,
  sortBy,
  news,
  totalHits,
  isLoading,
  isLoadingMore,
  hasMore,
  hasActiveFilters,
  activeFilterCount,
  tenantFacets,
  yearFacets,
  tagFacets,
  toggleTenant,
  toggleYear,
  toggleTag,
  clearFilters,
  loadMore,
} = useNewsSearch({
  initialNews: props.news?.data,
  initialTotal: props.news?.total,
  initialTag: initialTagName.value,
});

// Unlike other search pages, the news archive defaults to *no* tenant filter once
// Typesense takes over client-side — the main tenant would otherwise show every
// tenant's news instead of its own. Seed the current tenant either way.
const currentTenant = page.props.tenant;

if (currentTenant?.shortname) {
  selectedTenants.value = [currentTenant.shortname];
}

const sortOptions: PublicSortOption<NewsSearchSort>[] = [
  { value: 'relevance', label: $t('Pagal aktualumą'), icon: IFluentArrowSort24Regular },
  { value: 'date_desc', label: $t('Naujausi pirmi'), icon: IFluentArrowSortDownLines24Regular },
  { value: 'date_asc', label: $t('Seniausi pirmi'), icon: IFluentArrowSortUpLines24Regular },
];

// The backend's `allTenants` is the full list regardless of filters; Typesense's facet
// of the list the moment one is picked). Use `allTenants` for which options exist, and
// overlay live counts from the facet response where available.
const tenantOptions = computed<FilterOption[]>(() => {
  if (props.allTenants?.length) {
    const counts = new Map(tenantFacets.value.map(f => [f.value, f.count]));
    return props.allTenants.map(t => ({
      label: t.shortname,
      value: t.shortname,
      count: counts.get(t.shortname),
    }));
  }
  return tenantFacets.value;
});

// Year options derived from Typesense facets with fallback range
const yearOptions = computed<FilterOption[]>(() => {
  if (yearFacets.value.length > 0) {
    return yearFacets.value;
  }
  const currentYear = new Date().getFullYear();
  const fallback: FilterOption[] = [];
  for (let y = currentYear; y >= 2011; y--) {
    fallback.push({
      label: String(y),
      value: String(y),
      count: null,
    });
  }
  return fallback;
});

// Tag options derived from Typesense facets
const tagOptions = computed<FilterOption[]>(() => {
  return tagFacets.value;
});

const featuredArticle = computed<NewsItem | undefined>(() => {
  return news.value[0];
});

const gridArticles = computed<NewsItem[]>(() => {
  return news.value.slice(1);
});
</script>
