<template>
  <div class="news-archive-page">
    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="$t('Naujienos')"
      :lead="$t('Kas vyksta Studentų atstovybėje ir universitete – rinkimai, renginiai, gidai ir sprendimai, liečiantys tavo studijas.')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Search, Categories + Filter Controls -->
      <div class="space-y-4">
        <!-- Search Bar and Filters Button in the same row on desktop -->
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
          <!-- Search Input -->
          <div class="relative min-w-0 flex-1">
            <IFluentSearch16Regular class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <input
              v-model="query"
              type="text"
              :placeholder="`${$t('Ieškoti naujienų')}...`"
              :class="[
                'h-11 w-full border border-border bg-background pl-10 pr-9 text-sm text-foreground',
                'placeholder:text-muted-foreground/70 transition-colors focus:border-brand focus:outline-none',
              ]"
            >
            <button
              v-if="query"
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              @click="query = ''"
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

          <!-- Sort Popover -->
          <Popover v-model:open="isSortPopoverOpen">
            <PopoverTrigger as-child>
              <button
                type="button"
                :class="[
                  'inline-flex h-11 shrink-0 items-center justify-between gap-2 border px-3.5 text-xs font-bold uppercase tracking-wide transition-colors',
                  sortBy !== 'relevance'
                    ? 'border-brand bg-brand/5 text-brand hover:bg-brand/10'
                    : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
                ]"
                :aria-label="$t('Rikiuoti')"
              >
                <component :is="currentSortIcon" class="size-3.5" />
                <span>{{ $t('Rikiuoti') }}</span>
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
                  :aria-checked="sortBy === option.value"
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
                    v-if="sortBy === option.value"
                    class="size-4 text-brand"
                  />
                </button>
              </div>
            </PopoverContent>
          </Popover>
        </div>

        <!-- Filter Popovers Bar (toggled by the filter button) -->
        <div
          v-show="showFilterBar"
          class="flex flex-wrap items-center gap-2 border-t border-border/60 pt-3"
        >
          <!-- Padalinys Filter Popover -->
          <PublicFilterPopover
            :label="$t('Padalinys')"
            :options="tenantOptions"
            :selected="selectedTenants"
            searchable
            :search-placeholder="`${$t('Ieškoti padalinio')}...`"
            trigger-class="h-9 px-3"
            @toggle="toggleTenant"
            @clear="selectedTenants = []"
          />

          <!-- Year Filter Popover -->
          <PublicFilterPopover
            :label="$t('Metai')"
            :options="yearOptions"
            :selected="selectedYears"
            searchable
            :search-placeholder="`${$t('Ieškoti metų')}...`"
            trigger-class="h-9 px-3"
            @toggle="toggleYear"
            @clear="selectedYears = []"
          />

          <!-- Category Filter Popover -->
          <PublicFilterPopover
            :label="$t('Kategorija')"
            :options="categoryOptions"
            :selected="selectedCategories"
            trigger-class="h-9 px-3"
            @toggle="toggleCategory"
            @clear="selectedCategories = []"
          />

          <!-- Tag Filter Popover (if tags exist) -->
          <PublicFilterPopover
            v-if="tagOptions.length > 0"
            :label="$t('Žymos')"
            :options="tagOptions"
            :selected="selectedTags"
            searchable
            :search-placeholder="`${$t('Ieškoti žymos')}...`"
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
                {{ $t('Aktyvūs filtrai') }}:
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

              <!-- Category chips -->
              <TagChip
                v-for="cat in selectedCategories"
                :key="`cat-${cat}`"
                variant="muted"
                removable
                class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
                :label="cat"
                @remove="toggleCategory(cat)"
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
            <div class="aspect-[16/10] bg-secondary border border-border" />
            <div class="mt-4 h-5 w-3/4 bg-secondary" />
            <div class="mt-2 h-4 w-1/2 bg-secondary" />
            <div class="mt-4 h-4 w-1/4 bg-secondary" />
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="!isLoading && news.length === 0"
          class="border border-dashed border-border py-16 text-center"
        >
          <div class="mx-auto flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground">
            <IFluentNews24Regular class="size-6" />
          </div>
          <h3 class="mt-4 text-base font-bold text-foreground">
            {{ $t('Naujienų nerasta') }}
          </h3>
          <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
            {{ $t('Pagal pasirinktus kriterijus naujienų nerasta. Pabandykite pakeisti paieškos frazę arba išvalyti filtrus.') }}
          </p>
          <div v-if="hasActiveFilters" class="mt-6">
            <Button
              variant="brand-outline"
              size="public-sm"
              @click="clearFilters"
            >
              {{ $t('Išvalyti filtrus') }}
            </Button>
          </div>
        </div>

        <!-- News Results -->
        <div v-else class="space-y-12">
          <!-- Featured Lead Article (always the first article) -->
          <div v-if="featuredArticle" class="border-b border-border pb-12">
            <NewsCard
              :news="featuredArticle"
              size="featured"
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
              show-excerpt
            />
          </div>

          <!-- Load More Button -->
          <div
            v-if="hasMore"
            class="mt-12 flex justify-center"
          >
            <Button
              variant="brand-outline"
              size="public"
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
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useNewsSearch, type NewsSearchSort, type FacetOption } from '@/Composables/useNewsSearch';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import TagChip from '@/Components/Public/Base/TagChip.vue';
import PublicFilterPopover, { type FilterOption } from '@/Components/Public/Base/PublicFilterPopover.vue';
import NewsCard from '@/Components/Public/News/NewsCard.vue';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import type { NewsItem } from '@/Types/contentParts';
import { TenantType } from '@/Types/enums';
import IFluentNews24Regular from '~icons/fluent/news-24-regular';
import IFluentArrowSync20Regular from '~icons/fluent/arrow-sync-20-regular';
import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentFilter20Regular from '~icons/fluent/filter-20-regular';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentDelete20Regular from '~icons/fluent/delete-20-regular';
import IFluentArrowSort24Regular from '~icons/fluent/arrow-sort-24-regular';
import IFluentArrowSortDownLines24Regular from '~icons/fluent/arrow-sort-down-lines-24-regular';
import IFluentArrowSortUpLines24Regular from '~icons/fluent/arrow-sort-up-lines-24-regular';
import IFluentCheckmark16Filled from '~icons/fluent/checkmark-16-filled';

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
  allCategories?: Array<{ id: number; name: string }>;
  allTenants?: Array<{ id: number; shortname: string }>;
}>();

const showFilterBar = useStorage('vusa-news-archive-show-filters', false);
const isSortPopoverOpen = ref(false);

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
  selectedCategories,
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
  categoryFacets,
  tenantFacets,
  yearFacets,
  tagFacets,
  toggleCategory,
  toggleTenant,
  toggleYear,
  toggleTag,
  setSortBy,
  clearFilters,
  loadMore,
} = useNewsSearch({
  initialNews: props.news?.data,
  initialTotal: props.news?.total,
  initialTag: initialTagName.value,
});

const currentTenant = page.props.tenant;

if (currentTenant?.type === TenantType.Padalinys && currentTenant.shortname) {
  selectedTenants.value = [currentTenant.shortname];
  showFilterBar.value = true;
}

const sortOptions: Array<{ value: NewsSearchSort; label: string }> = [
  { value: 'relevance', label: $t('Pagal aktualumą') },
  { value: 'date_desc', label: $t('Naujausi pirmi') },
  { value: 'date_asc', label: $t('Seniausi pirmi') },
];

const selectSort = (newSortBy: NewsSearchSort) => {
  setSortBy(newSortBy);
  isSortPopoverOpen.value = false;
};

const getSortIcon = (mode: NewsSearchSort) => {
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

const currentSortIcon = computed(() => getSortIcon(sortBy.value));

// Category options combining Typesense facets with backend props if available
const categoryOptions = computed<FilterOption[]>(() => {
  if (categoryFacets.value.length > 0) {
    return categoryFacets.value;
  }
  if (props.allCategories?.length) {
    return props.allCategories.map(c => ({
      label: c.name,
      value: c.name,
    }));
  }
  return [];
});

// Tenant options combining Typesense facets with backend props if available
const tenantOptions = computed<FilterOption[]>(() => {
  if (tenantFacets.value.length > 0) {
    return tenantFacets.value;
  }
  if (props.allTenants?.length) {
    return props.allTenants.map(t => ({
      label: t.shortname,
      value: t.shortname,
    }));
  }
  return [];
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
      count: 0,
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
