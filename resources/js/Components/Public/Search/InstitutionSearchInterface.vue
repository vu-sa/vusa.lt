<template>
  <SearchErrorBoundary 
    :error="searchController.searchError.value" 
    :is-online="searchController.isOnline.value"
    :is-retrying="searchController.isSearching.value" 
    :retry-count="searchController.retryCount.value"
    :max-retries="searchController.maxRetries" 
    @retry="searchController.retrySearch"
    @clear-error="searchController.clearError"
  >
    <div class="w-full">
      <!-- Search Header -->
      <div class="my-3 sm:my-4 lg:my-6 px-2 sm:px-3 lg:px-4">
        <div class="text-center max-w-2xl mx-auto">
          <div class="flex items-center justify-center gap-3 mb-3 sm:mb-4">
            <SearchPageSwitcher page="contacts" />
          </div>
          <h1 class="text-2xl sm:text-4xl font-bold text-foreground mb-2 sm:mb-3">
            {{ $t('search.institution_search_title') }}
          </h1>
          <p class="text-base sm:text-lg text-muted-foreground px-2">
            {{ $t('search.institution_search_description') }}
          </p>
        </div>
      </div>

      <!-- Search Input -->
      <div class="mb-2">
        <InstitutionSearchInput 
          :query="inputQuery" 
          :is-searching="searchController.isSearching.value"
          :recent-searches="searchController.recentSearches.value" 
          :type-to-search="typeToSearch"
          @update:query="handleQueryUpdate"
          @update:type-to-search="handleTypeToSearchUpdate" 
          @search="handleSearch" 
          @select-recent="handleSelectRecent"
          @clear="handleClear" 
          @remove-recent="handleRemoveRecent" 
          @clear-all-history="handleClearAllHistory" 
        />
      </div>

      <!-- Offline indicator -->
      <div 
        v-if="!searchController.isOnline.value" 
        class="mb-3 sm:mb-4 mx-2 sm:mx-3 lg:mx-4 p-3 bg-orange-50 
          dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800 rounded-lg"
      >
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
            <InstitutionFacetSidebar 
              :facets="searchController.facets.value" 
              :filters="searchController.filters.value"
              :is-loading="searchController.isLoadingFacets.value" 
              :active-filter-count="activeFilterCount"
              :type-labels="props.typeLabels"
              @update:tenant="searchController.toggleTenant" 
              @update:type="searchController.toggleType"
              @clear-filters="searchController.clearFilters" 
            />
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
                    <strong class="text-foreground">{{ searchController.totalHits.value.toLocaleString() }}</strong>
                    <span class="hidden sm:inline"> {{ searchController.totalHits.value === 1 ? 
                      $t('search.institution_singular') : $t('search.institution_plural') }}</span>
                  </template>
                  <template v-else>
                    <span class="hidden sm:inline">{{ $t('search.found_results') }} </span>
                    <strong class="text-foreground">{{ searchController.totalHits.value.toLocaleString() }}</strong>
                    <span class="hidden sm:inline"> {{ searchController.totalHits.value === 1 ? 
                      $t('search.institution_singular') : $t('search.institution_plural') }}</span>
                    <template v-if="searchController.searchState.value.query && 
                      searchController.searchState.value.query !== '*'">
                      <span class="hidden sm:inline"> {{ $t('search.by_query') }} </span>
                      <span class="hidden sm:inline"><strong class="text-foreground">"{{ 
                        searchController.searchState.value.query }}"</strong></span>
                    </template>
                  </template>
                </template>
                <template v-else-if="searchController.searchState.value.query && 
                  searchController.searchState.value.query.length > 0 && !searchController.isSearching.value">
                  {{ $t('search.no_institutions_found') }}
                </template>
                <template v-else-if="!searchController.searchState.value.query && !searchController.isSearching.value">
                  <span class="hidden sm:inline">{{ $t('search.enter_search_or_browse_institutions') }}</span>
                  <span class="sm:hidden">{{ $t('search.browse_institutions') }}</span>
                </template>
              </div>

              <!-- View Controls -->
              <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                <div class="flex bg-muted/50 rounded-lg p-0.5 sm:p-1">
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    :class="[
                      'rounded-md px-2 sm:px-3 py-1.5 sm:py-2 transition-all duration-200',
                      searchController.viewMode.value === 'grid'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'hover:bg-background/50 text-muted-foreground'
                    ]" 
                    @click="searchController.setViewMode('grid')"
                  >
                    <LayoutGrid class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" />
                    <span class="text-xs sm:text-sm font-medium hidden sm:inline">{{ $t('search.view_mode_grid') }}</span>
                  </Button>
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    :class="[
                      'rounded-md px-2 sm:px-3 py-1.5 sm:py-2 transition-all duration-200',
                      searchController.viewMode.value === 'list'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'hover:bg-background/50 text-muted-foreground'
                    ]" 
                    @click="searchController.setViewMode('list')"
                  >
                    <List class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" />
                    <span class="text-xs sm:text-sm font-medium hidden sm:inline">{{ $t('search.view_mode_list') }}</span>
                  </Button>
                </div>
              </div>
            </div>

            <!-- Filter Tags (Mobile) -->
            <div v-if="hasActiveFilters" class="md:hidden mb-3 sm:mb-4">
              <div class="flex flex-wrap gap-1">
                <!-- Tenant filters -->
                <Badge 
                  v-for="tenant in searchController.filters.value.tenants" 
                  :key="`tenant-${tenant}`"
                  variant="secondary" 
                  class="gap-1 text-xs px-1.5 py-1 max-w-36"
                >
                  <Building2 class="w-3 h-3 flex-shrink-0" />
                  <span class="truncate">{{ tenant }}</span>
                  <Button 
                    variant="ghost" 
                    size="sm"
                    class="h-3 w-3 p-0 hover:bg-destructive hover:text-destructive-foreground ml-0.5"
                    @click="searchController.toggleTenant(tenant)"
                  >
                    <X class="w-2.5 h-2.5" />
                  </Button>
                </Badge>

                <!-- Type filters -->
                <Badge 
                  v-for="type in searchController.filters.value.types"
                  :key="`type-${type}`" 
                  variant="secondary" 
                  class="gap-1 text-xs px-1.5 py-1 max-w-32"
                >
                  <Tag class="w-3 h-3 flex-shrink-0" />
                  <span class="truncate">{{ type }}</span>
                  <Button 
                    variant="ghost" 
                    size="sm"
                    class="h-3 w-3 p-0 hover:bg-destructive hover:text-destructive-foreground ml-0.5"
                    @click="searchController.toggleType(type)"
                  >
                    <X class="w-2.5 h-2.5" />
                  </Button>
                </Badge>

                <!-- Clear all -->
                <Button 
                  variant="outline" 
                  size="sm" 
                  class="h-6 px-2 text-xs flex-shrink-0"
                  @click="searchController.clearFilters"
                >
                  <X class="w-3 h-3 mr-1" />
                  {{ $t('search.clear_all') }}
                </Button>
              </div>
            </div>

            <!-- Search Results -->
            <InstitutionResults 
              :results="searchController.results.value" 
              :view-mode="searchController.viewMode.value"
              :is-loading="searchController.isSearching.value" 
              :has-query="!!searchController.searchState.value.query"
              :search-query="searchController.searchState.value.query" 
              :total-hits="searchController.totalHits.value"
              :has-more-results="searchController.hasMoreResults.value"
              :is-loading-more="searchController.isLoadingMore.value" 
              :has-active-filters="hasActiveFilters"
              @load-more="searchController.loadMore" 
              @clear-filters="handleClear" 
            />
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
  Building2,
  LayoutGrid,
  List,
  X,
  Tag,
  WifiOff
} from 'lucide-vue-next'

// Local components
import SearchPageSwitcher from './SearchPageSwitcher.vue'
import SearchErrorBoundary from './SearchErrorBoundary.vue'
import InstitutionSearchInput from './InstitutionSearchInput.vue'
import InstitutionFacetSidebar from './InstitutionFacetSidebar.vue'
import InstitutionResults from './InstitutionResults.vue'

import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { useInstitutionSearch } from '@/Composables/useInstitutionSearch'
import type { InstitutionSearchFilters } from '@/Types/InstitutionSearchTypes'

// Initialize search controller
const searchController = useInstitutionSearch()

// Local state
const typeToSearch = ref(true) // Enable by default
const inputQuery = ref('')

// Props interface
interface Props {
  initialQuery?: string
  initialFilters?: Partial<InstitutionSearchFilters>
  typeLabels?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  initialQuery: '',
  initialFilters: () => ({}),
  typeLabels: () => ({})
})

// Computed properties for filter state
const hasActiveFilters = computed(() => Boolean(searchController.hasActiveFilters.value))
const activeFilterCount = computed(() => {
  const filters = searchController.filters.value
  let count = 0
  if (filters.tenants.length > 0) count++
  if (filters.types.length > 0) count++
  if (filters.hasContacts !== null) count++
  return count
})

// Debounced auto-search when typing
const debouncedAutoSearch = debounce((q: string) => {
  searchController.search(q)
}, 200)

// Event handlers
const handleQueryUpdate = (value: string) => {
  inputQuery.value = value
  
  // Only auto-search if typeToSearch is enabled
  if (typeToSearch.value) {
    if (value.trim() !== '') {
      debouncedAutoSearch(value)
    } else {
      debouncedAutoSearch.cancel()
      searchController.search('*', true)
    }
  }
}

const handleTypeToSearchUpdate = (value: boolean) => {
  typeToSearch.value = value
}

const handleSearch = (query?: string) => {
  debouncedAutoSearch.cancel()
  const searchQuery = (query ?? inputQuery.value).trim() === '' ? '*' : (query ?? inputQuery.value)
  searchController.search(searchQuery, true)
}

const handleSelectRecent = (search: string) => {
  inputQuery.value = search
  debouncedAutoSearch.cancel()
  searchController.search(search, true)
}

const handleClear = () => {
  inputQuery.value = ''
  debouncedAutoSearch.cancel()
  searchController.search('*', true)
}

const handleRemoveRecent = (search: string) => {
  searchController.removeRecentSearch(search)
}

const handleClearAllHistory = () => {
  searchController.clearRecentSearches()
}

// Initialize from props and load all institutions
onMounted(async () => {
  // Initialize search client first
  await searchController.initializeSearchClient()

  // Load initial facets
  await searchController.loadInitialFacets()

  // Apply initial filters if provided
  if (Object.keys(props.initialFilters).length > 0) {
    if (props.initialFilters.tenants) {
      (props.initialFilters.tenants as string[]).forEach((tenant: string) => searchController.toggleTenant(tenant))
    }
    if (props.initialFilters.types) {
      (props.initialFilters.types as string[]).forEach((type: string) => searchController.toggleType(type))
    }
  }

  // Set initial query if provided, otherwise search all institutions
  if (props.initialQuery) {
    inputQuery.value = props.initialQuery
    searchController.search(props.initialQuery)
  } else {
    // Trigger initial "show all institutions" search
    searchController.search('*', true)
  }
})

// Keep inputQuery in sync with controller query updates
watch(
  () => searchController.searchState.value.query,
  (q) => {
    if (q !== '*') {
      inputQuery.value = q || ''
    }
  }
)
</script>
