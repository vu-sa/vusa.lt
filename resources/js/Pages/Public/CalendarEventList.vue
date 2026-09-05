<template>
  <div class="calendar-list-page">
    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="$t('Renginių kalendorius')"
      :title="$t('Renginiai')"
      :lead="$t('Sek visus VU studentų renginius bei įvykius – nuo koncertų iki atstovavimo iniciatyvų.')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>

      <template #actions>
        <Button
          variant="brand-outline"
          size="public"
          @click="showModal = true"
        >
          <IFluentArrowSync20Regular class="size-4" />
          <span>{{ $t('Sinchronizuoti') }}</span>
        </Button>
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Tabs + Search + Filter Controls -->
      <div class="space-y-4">
        <!-- Top row: Tabs on left + Year selector in the same row, count on right -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <!-- Left side: Date tabs -->
          <div class="flex flex-wrap items-center gap-3">
            <div class="inline-flex h-10 border border-border bg-background p-0.5">
              <button
                type="button"
                :class="[
                  'h-full px-4 text-xs font-bold uppercase tracking-wide transition-colors flex items-center justify-center',
                  tab === 'upcoming'
                    ? 'bg-brand-fill text-brand-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="setTab('upcoming')"
              >
                {{ $t('Būsimi') }}
              </button>
              <button
                type="button"
                :class="[
                  'h-full px-4 text-xs font-bold uppercase tracking-wide transition-colors flex items-center justify-center',
                  tab === 'past'
                    ? 'bg-brand-fill text-brand-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="setTab('past')"
              >
                {{ $t('Praėję') }}
              </button>
              <button
                type="button"
                :class="[
                  'h-full px-4 text-xs font-bold uppercase tracking-wide transition-colors flex items-center justify-center',
                  tab === 'all'
                    ? 'bg-brand-fill text-brand-foreground'
                    : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="setTab('all')"
              >
                {{ $t('Visi') }}
              </button>
            </div>
          </div>

          <!-- Total count indicator on desktop -->
          <div class="hidden text-xs font-mono uppercase tracking-wider text-muted-foreground sm:block">
            <template v-if="!isLoading">
              {{ $t('Rasta :count renginių', { count: totalHits }) }}
            </template>
          </div>
        </div>

        <!-- Search Bar and Filters Button in the same row on desktop -->
        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
          <!-- Search Input -->
          <div class="relative min-w-0 flex-1">
            <IFluentSearch16Regular class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <input
              v-model="query"
              type="text"
              :placeholder="`${$t('Ieškoti pagal pavadinimą')}...`"
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
          <!-- Year Filter Popover -->
          <CalendarFilterPopover
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
          <CalendarFilterPopover
            :label="$t('Kategorija')"
            :options="categoryOptions"
            :selected="selectedCategories"
            trigger-class="h-9 px-3"
            @toggle="toggleCategory"
            @clear="selectedCategories = []"
          />

          <!-- Tenant/Padalinys Filter Popover -->
          <CalendarFilterPopover
            :label="$t('Padalinys')"
            :options="tenantOptions"
            :selected="selectedTenants"
            searchable
            :search-placeholder="`${$t('Ieškoti padalinio')}...`"
            trigger-class="h-9 px-3"
            @toggle="toggleTenant"
            @clear="selectedTenants = []"
          />

          <!-- Remote Events Toggle -->
          <button
            type="button"
            :class="[
              'inline-flex h-9 items-center gap-2 border px-3 text-xs font-bold uppercase tracking-wide transition-colors',
              isRemoteOnly
                ? 'border-brand text-brand bg-brand/5 hover:bg-brand/10'
                : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
            ]"
            @click="toggleRemote"
          >
            <IFluentGlobe20Regular class="size-3.5" />
            <span>{{ $t('Tik nuotoliniai') }}</span>
          </button>
        </div>

        <!-- Active Filter Chips -->
        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 pt-1">
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

          <!-- Remote only chip -->
          <TagChip
            v-if="isRemoteOnly"
            variant="muted"
            removable
            class="normal-case font-medium text-xs tracking-normal bg-background text-foreground"
            :label="$t('Tik nuotoliniai')"
            @remove="toggleRemote"
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
        </div>
      </div>

      <!-- Results Section -->
      <div class="mt-8">
        <!-- Initial Loading State -->
        <div
          v-if="isLoading && events.length === 0"
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
          v-else-if="!isLoading && events.length === 0"
          class="border border-dashed border-border py-16 text-center"
        >
          <div class="mx-auto flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground">
            <IFluentCalendarLtr24Regular class="size-6" />
          </div>
          <h3 class="mt-4 text-base font-bold text-foreground">
            {{ $t('Renginių nerasta') }}
          </h3>
          <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
            {{ $t('Pagal pasirinktus kriterijus renginių nerasta. Pabandykite pakeisti paieškos frazę arba išvalyti filtrus.') }}
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

        <!-- Event Grid -->
        <div
          v-else
          class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3"
        >
          <EventCard
            v-for="event in events"
            :key="event.id"
            :event
            :variant="tab === 'past' ? 'past' : 'upcoming'"
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
            <span v-else>{{ $t('Rodyti daugiau renginių') }}</span>
          </Button>
        </div>
      </div>
    </section>

    <!-- Calendar Sync Modal -->
    <CalendarSyncModal
      v-model:show-modal="showModal"
      @close="showModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ref, computed } from 'vue';
import { useStorage } from '@vueuse/core';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useCalendarSearch, type CalendarSearchSort } from '@/Composables/useCalendarSearch';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import TagChip from '@/Components/Public/Base/TagChip.vue';
import EventCard from '@/Components/Calendar/EventCard.vue';
import CalendarFilterPopover, { type FilterOption } from '@/Components/Calendar/CalendarFilterPopover.vue';
import CalendarSyncModal from '@/Components/Dialogs/CalendarSyncModal.vue';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import IFluentCalendarLtr24Regular from '~icons/fluent/calendar-ltr-24-regular';
import IFluentArrowSync20Regular from '~icons/fluent/arrow-sync-20-regular';
import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentFilter20Regular from '~icons/fluent/filter-20-regular';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentGlobe20Regular from '~icons/fluent/globe-20-regular';
import IFluentDelete20Regular from '~icons/fluent/delete-20-regular';
import IFluentArrowSort24Regular from '~icons/fluent/arrow-sort-24-regular';
import IFluentArrowSortDownLines24Regular from '~icons/fluent/arrow-sort-down-lines-24-regular';
import IFluentArrowSortUpLines24Regular from '~icons/fluent/arrow-sort-up-lines-24-regular';
import IFluentCheckmark16Filled from '~icons/fluent/checkmark-16-filled';

// Breadcrumbs in band placement per redesign
usePageBreadcrumbs(
  () => BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      'Kalendorius',
      undefined,
      IFluentCalendarLtr24Regular,
    ),
  ]),
  { placement: 'band' },
);

const props = defineProps<{
  events?: {
    data: App.Entities.Calendar[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    path: string;
    links: unknown[];
  };
  activeTab?: string;
  allCategories?: Array<{ id: number; name: string }>;
  allTenants?: Array<{ id: number; shortname: string }>;
}>();

const showModal = ref(false);
const showFilterBar = useStorage('vusa-calendar-show-filters', false);
const isSortPopoverOpen = ref(false);

const {
  query,
  tab,
  selectedCategories,
  selectedTenants,
  selectedYears,
  isRemoteOnly,
  sortBy,
  events,
  totalHits,
  isLoading,
  isLoadingMore,
  hasMore,
  hasActiveFilters,
  activeFilterCount,
  categoryFacets,
  tenantFacets,
  yearFacets,
  setTab,
  toggleCategory,
  toggleTenant,
  toggleYear,
  toggleRemote,
  setSortBy,
  clearFilters,
  loadMore,
} = useCalendarSearch({
  initialTab: (props.activeTab as 'upcoming' | 'past' | 'all') || 'upcoming',
});

const sortOptions: Array<{ value: CalendarSearchSort; label: string }> = [
  { value: 'relevance', label: $t('Pagal aktualumą') },
  { value: 'date_desc', label: $t('Tolimiausi ateityje pirmi') },
  { value: 'date_asc', label: $t('Seniausi pirmi') },
];

const selectSort = (newSortBy: CalendarSearchSort) => {
  setSortBy(newSortBy);
  isSortPopoverOpen.value = false;
};

const getSortIcon = (mode: CalendarSearchSort) => {
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
  for (let y = currentYear + 1; y >= 2011; y--) {
    fallback.push({
      label: String(y),
      value: String(y),
      count: 0,
    });
  }
  return fallback;
});
</script>
