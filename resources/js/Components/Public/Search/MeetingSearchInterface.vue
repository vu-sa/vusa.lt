<template>
  <SearchErrorBoundary :error="searchController.searchError.value" :is-online="searchController.isOnline.value"
    :is-retrying="searchController.isSearching.value" :retry-count="searchController.retryCount.value"
    :max-retries="searchController.maxRetries" @retry="searchController.retrySearch"
    @clear-error="searchController.clearError">
    <div class="w-full">
      <!-- Search Header -->
      <div class="my-3 sm:my-4 lg:my-6 px-2 sm:px-3 lg:px-4">
        <div class="text-center max-w-2xl mx-auto">
          <div class="flex items-center justify-center gap-3 mb-3 sm:mb-4">
            <SearchPageSwitcher page="meetings" />
          </div>
          <h1 class="text-2xl sm:text-4xl font-bold text-foreground mb-2 sm:mb-3">
            {{ $t('search.meeting_search_title') }}
          </h1>
          <p class="text-base sm:text-lg text-muted-foreground px-2">
            {{ $t('search.meeting_search_description') }}
          </p>
        </div>
      </div>

      <!-- Search Input -->
      <div class="mb-2">
        <MeetingSearchInput :query="inputQuery" :is-searching="searchController.isSearching.value"
          :recent-searches="searchController.recentSearches.value" :type-to-search @update:query="handleQueryUpdate"
          @update:type-to-search="handleTypeToSearchUpdate" @search="handleSearch" @select-recent="handleSelectRecent"
          @clear="handleClear" @remove-recent="handleRemoveRecent" @clear-all-history="handleClearAllHistory" />
      </div>

      <!-- Offline indicator -->
      <div v-if="!searchController.isOnline.value" class="mb-3 sm:mb-4 mx-2 sm:mx-3 lg:mx-4 p-3 bg-orange-50
        dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800 rounded-lg">
        <div class="flex items-center gap-2 text-orange-800 dark:text-orange-200">
          <WifiOff class="w-4 h-4" />
          <span class="text-sm font-medium">{{ $t('search.offline_message') }}</span>
        </div>
      </div>

      <!-- Main Content Layout -->
      <div class="px-2 sm:px-3 lg:px-4">
        <div class="grid grid-cols-1 xl:grid-cols-[300px_1fr] gap-4 lg:gap-6">
          <!-- Filter Sidebar -->
          <div class="xl:sticky xl:top-6 xl:self-start xl:flex-shrink-0 xl:w-[300px]">
            <MeetingFacetSidebar :facets="searchController.facets.value" :filters="searchController.filters.value"
              :is-loading="searchController.isLoadingFacets.value" :active-filter-count
              @update:tenant="searchController.toggleTenant" @update:institution-type="searchController.toggleInstitutionType"
              @update:year="searchController.toggleYear" @update:success-rate="searchController.toggleSuccessRate"
              @update:date-range="searchController.setDateRange" @clear-filters="searchController.clearFilters" />
          </div>

          <!-- Results Area -->
          <div class="min-w-0">
            <!-- Results Header -->
            <div class="flex items-center justify-between mb-4 sm:mb-6 bg-muted/30 rounded-lg px-3 sm:px-4 py-2">
              <!-- Results Count -->
              <div class="text-xs sm:text-sm text-muted-foreground min-w-0 flex-1 pr-2">
                <template v-if="searchController.totalHits.value > 0">
                  <template v-if="searchController.searchState.value.query === '*' && !hasActiveFilters">
                    <span class="hidden sm:inline">{{ $t('search.showing_results') }} </span>
                    <strong class="text-foreground">{{ searchController.totalHits.value.toLocaleString()
                    }}</strong>
                    <span class="hidden sm:inline">{{ searchController.totalHits.value === 1 ?
                      $t('search.meeting_singular') : $t('search.meeting_plural') }}</span>
                    <span class="sm:hidden">{{ searchController.totalHits.value === 1 ? 'pos.' : 'pos.' }}</span>
                    <span class="hidden sm:inline">{{ $t('search.newest_first') }}</span>
                  </template>
                  <template v-else>
                    <span class="hidden sm:inline">{{ `${$t('search.found_results')} ` }}</span>
                    <strong class="text-foreground">{{ searchController.totalHits.value.toLocaleString() }}</strong>
                    <span class="hidden sm:inline">{{ searchController.totalHits.value === 1 ?
                      `${$t('search.meeting_singular')} ` : ` ${$t('search.meeting_plural')} ` }}</span>
                    <span class="sm:hidden">{{ searchController.totalHits.value === 1 ? 'pos.' : 'pos.' }}</span>
                    <template v-if="searchController.searchState.value.query &&
                      searchController.searchState.value.query !== '*'">
                      <span class="hidden sm:inline">{{ `${$t('search.by_query')} ` }}</span>
                      <span class="hidden sm:inline"><strong class="text-foreground">"{{
                        searchController.searchState.value.query }}"</strong></span>
                    </template>
                  </template>
                </template>
                <template v-else-if="searchController.searchState.value.query &&
                  searchController.searchState.value.query.length > 0 && !searchController.isSearching.value">
                  {{ $t('search.no_meetings_found') }}
                </template>
                <template v-else-if="!searchController.searchState.value.query && !searchController.isSearching.value">
                  <span class="hidden sm:inline">{{ $t('search.enter_search_or_browse_meetings') }}</span>
                  <span class="sm:hidden">Ieškokite arba naršykite</span>
                </template>
              </div>
            </div>

            <!-- Filter Tags (Mobile) -->
            <div v-if="hasActiveFilters" class="md:hidden mb-3 sm:mb-4">
              <div class="flex flex-wrap gap-1">
                <!-- Tenant filters -->
                <Badge v-for="tenant in searchController.filters.value.tenants" :key="`tenant-${tenant}`"
                  variant="secondary" class="gap-1 text-xs px-1.5 py-1 max-w-36">
                  <Building2 class="w-3 h-3 flex-shrink-0" />
                  <span class="truncate">{{ tenant }}</span>
                  <Button variant="ghost" size="sm"
                    class="h-3 w-3 p-0 hover:bg-destructive hover:text-destructive-foreground ml-0.5"
                    @click="searchController.toggleTenant(tenant)">
                    <X class="w-2.5 h-2.5" />
                  </Button>
                </Badge>

                <!-- Year filters -->
                <Badge v-for="year in searchController.filters.value.years" :key="`year-${year}`"
                  variant="secondary" class="gap-1 text-xs px-1.5 py-1 flex-shrink-0">
                  <CalendarDays class="w-3 h-3 flex-shrink-0" />
                  {{ year }}
                  <Button variant="ghost" size="sm"
                    class="h-3 w-3 p-0 hover:bg-destructive hover:text-destructive-foreground ml-0.5"
                    @click="searchController.toggleYear(year)">
                    <X class="w-2.5 h-2.5" />
                  </Button>
                </Badge>

                <!-- Success rate filters -->
                <Badge v-for="range in searchController.filters.value.successRateRanges" :key="`rate-${range}`"
                  variant="secondary" class="gap-1 text-xs px-1.5 py-1 flex-shrink-0">
                  <TrendingUp class="w-3 h-3 flex-shrink-0" />
                  {{ range }}
                  <Button variant="ghost" size="sm"
                    class="h-3 w-3 p-0 hover:bg-destructive hover:text-destructive-foreground ml-0.5"
                    @click="searchController.toggleSuccessRate(range)">
                    <X class="w-2.5 h-2.5" />
                  </Button>
                </Badge>

                <!-- Clear all -->
                <Button variant="outline" size="sm" class="h-6 px-2 text-xs flex-shrink-0"
                  @click="searchController.clearFilters">
                  <X class="w-3 h-3 mr-1" />
                  {{ $t('search.clear_all') }}
                </Button>
              </div>
            </div>

            <!-- Search Results -->
            <MeetingResults :results="searchController.results.value"
              :is-loading="searchController.isSearching.value" :has-query="!!searchController.searchState.value.query"
              :search-query="searchController.searchState.value.query" :total-hits="searchController.totalHits.value"
              :has-more-results="searchController.hasMoreResults.value"
              :is-loading-more="searchController.isLoadingMore.value" :has-active-filters
              @load-more="searchController.loadMore" @clear-filters="handleClear" />
          </div>
        </div>
      </div>
    </div>
  </SearchErrorBoundary>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { debounce } from 'lodash-es'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Search,
  Building2,
  CalendarDays,
  TrendingUp,
  X,
  WifiOff
} from 'lucide-vue-next'

// Local components
import SearchPageSwitcher from './SearchPageSwitcher.vue'
import SearchErrorBoundary from './SearchErrorBoundary.vue'
import MeetingSearchInput from './MeetingSearchInput.vue'
import MeetingFacetSidebar from './MeetingFacetSidebar.vue'
import MeetingResults from './MeetingResults.vue'

import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { useMeetingSearch } from '@/Composables/useMeetingSearch'
import type { MeetingSearchFilters } from '@/Types/MeetingSearchTypes'

// Initialize search controller
const searchController = useMeetingSearch()

// Local state
const typeToSearch = ref(true) // Enable by default
const inputQuery = ref('')

// Props interface
interface Props {
  initialQuery?: string
  initialFilters?: Partial<MeetingSearchFilters>
}

const {
  initialQuery = '',
  initialFilters = {}
} = defineProps<Props>()

// Computed properties for filter state
const hasActiveFilters = computed(() => Boolean(searchController.hasActiveFilters.value))
const activeFilterCount = computed(() => {
  const filters = searchController.filters.value
  let count = 0
  if (filters.tenants.length > 0) count++
  if (filters.years.length > 0) count++
  if (filters.successRateRanges.length > 0) count++
  if ((filters.dateRange.preset && filters.dateRange.preset !== 'recent') || filters.dateRange.from || filters.dateRange.to) count++
  return count
})

// Debounced auto-search when typing in auto mode
const debouncedAutoSearch = debounce((q: string) => {
  searchController.search(q)
}, 200)

// Event handlers
const handleQueryUpdate = (query: string) => {
  // Update local input binding always so UI reflects typed text
  inputQuery.value = query
  // Only auto-search if typeToSearch is enabled and query is not empty
  if (typeToSearch.value && query.trim() !== '') {
    debouncedAutoSearch(query)
  } else {
    // Cancel any pending debounced calls when leaving auto conditions
    debouncedAutoSearch.cancel()
    // If query becomes empty and auto-search is enabled, reset to show all meetings
    if (query.trim() === '' && typeToSearch.value) {
      searchController.search('*', true)
    }
  }
}

const handleSearch = (query: string) => {
  // Keep input in sync and execute immediately, bypassing debounce
  inputQuery.value = query
  // Cancel pending auto-search to avoid double-trigger
  debouncedAutoSearch.cancel()
  // If query is empty, search for '*' to show all meetings (preserves filters)
  const searchQuery = query.trim() === '' ? '*' : query
  searchController.search(searchQuery, true)
}

const handleSelectRecent = (search: string) => {
  // Selecting a recent search should also search immediately
  inputQuery.value = search
  debouncedAutoSearch.cancel()
  searchController.search(search, true)
}

const handleClear = () => {
  // Reset to the same state as when page first loads - show all meetings
  // This should match the initial load behavior
  inputQuery.value = ''
  debouncedAutoSearch.cancel()
  searchController.search('*', true) // immediate = true to avoid debouncing
}

const handleTypeToSearchUpdate = (value: boolean) => {
  typeToSearch.value = value
}

const handleRemoveRecent = (search: string) => {
  searchController.removeRecentSearch(search)
}

const handleClearAllHistory = () => {
  searchController.clearRecentSearches()
}

// Initialize from props and load all meetings
onMounted(async () => {
  // Initialize search client first
  await searchController.initializeSearchClient()

  // Load initial facets for the merged facet system
  await searchController.loadInitialFacets()

  // Apply initial filters if provided
  if (Object.keys(initialFilters).length > 0) {
    if (initialFilters.tenants) {
      (initialFilters.tenants as string[]).forEach((tenant: string) => searchController.toggleTenant(tenant))
    }
    if (initialFilters.completionStatus) {
      (initialFilters.completionStatus as string[]).
        forEach((status: string) => searchController.toggleCompletionStatus(status))
    }
    if (initialFilters.years) {
      (initialFilters.years as number[]).
        forEach((year: number) => searchController.toggleYear(year))
    }
    if (initialFilters.successRateRanges) {
      (initialFilters.successRateRanges as string[]).
        forEach((range: string) => searchController.toggleSuccessRate(range))
    }
    if (initialFilters.dateRange) {
      searchController.setDateRange(initialFilters.dateRange)
    }
  }

  // Set initial query if provided, otherwise search all meetings
  if (initialQuery) {
    searchController.search(initialQuery)
  } else {
    // Trigger initial "show all meetings" search with newest first sorting
    // Use wildcard search to show all meetings on page load
    searchController.search('*', true) // immediate = true for initial load
  }
  // Sync input with controller's displayed query
  inputQuery.value = searchController.searchState.value.query
})

// Keep inputQuery in sync with controller query updates (e.g., after searches)
watch(
  () => searchController.searchState.value.query,
  (q) => {
    inputQuery.value = q || ''
  }
)
</script>
