<template>
  <SearchErrorBoundary :error="searchController.searchError.value" :is-online="searchController.isOnline.value"
    :is-retrying="searchController.isSearching.value" :retry-count="searchController.retryCount.value"
    :max-retries="searchController.maxRetries" @retry="searchController.retrySearch"
    @clear-error="searchController.clearError">
    <div class="w-full px-4 lg:px-6">
      <!-- Search Header -->
      <div class="my-6">
        <div class="text-center max-w-2xl mx-auto">
          <div class="flex items-center justify-center gap-3 mb-4">
            <div class="p-3 bg-gradient-to-br from-primary/10 to-secondary/10 rounded-xl">
              <Search class="w-8 h-8 text-primary" />
            </div>
          </div>
          <h1 class="text-4xl font-bold text-foreground mb-3">
            {{ $t('search.document_search_title') }}
          </h1>
          <p class="text-lg text-muted-foreground">
            {{ $t('search.document_search_description') }}
          </p>
        </div>
      </div>

      <!-- Search Input -->
      <div class="mb-2">
        <DocumentSearchInput :query="searchController.searchState.value.query"
          :is-searching="searchController.isSearching.value" :recent-searches="searchController.recentSearches.value"
          :type-to-search @update:query="handleQueryUpdate" @update:type-to-search="handleTypeToSearchUpdate"
          @search="handleSearch" @select-recent="handleSelectRecent" @clear="handleClear" 
          @remove-recent="handleRemoveRecent" @clear-all-history="handleClearAllHistory" />
      </div>

      <!-- Offline indicator -->
      <div v-if="!searchController.isOnline.value"
        class="mb-4 p-3 bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800 rounded-lg">
        <div class="flex items-center gap-2 text-orange-800 dark:text-orange-200">
          <WifiOff class="w-4 h-4" />
          <span class="text-sm font-medium">{{ $t('search.offline_message') }}</span>
        </div>
      </div>

      <!-- Main Content Layout -->
      <div class="grid grid-cols-1 xl:grid-cols-[320px_1fr] gap-6">
        <!-- Filter Sidebar -->
        <div class="xl:sticky xl:top-6 xl:self-start xl:flex-shrink-0 xl:w-[320px] xl:px-4">
          <DocumentFacetSidebar :facets="searchController.facets.value" :filters="searchController.filters.value"
            :is-loading="searchController.isLoadingFacets.value" :active-filter-count
            @update:tenant="searchController.toggleTenant" @update:content-type="searchController.toggleContentType"
            @update:language="searchController.toggleLanguage" @update:date-range="searchController.setDateRange"
            @clear-filters="searchController.clearFilters" />
        </div>

        <!-- Results Area -->
        <div class="min-w-0">
          <!-- Results Header -->
          <div class="flex items-center justify-between mb-6 bg-muted/30 rounded-lg px-4 py-2">
            <!-- Results Count -->
            <div class="text-sm text-muted-foreground">
              <template v-if="searchController.totalHits.value > 0">
                <template v-if="searchController.searchState.value.query === '*' && !hasActiveFilters">
                  {{ $t('search.showing_results') }} <strong class="text-foreground">{{ searchController.totalHits.value.toLocaleString()
                    }}</strong>{{ ' ' }}
                  {{ searchController.totalHits.value === 1 ? $t('search.document_singular') : $t('search.document_plural') }}{{ ' ' }}
                  {{ $t('search.newest_first') }}
                </template>
                <template v-else>
                  {{ $t('search.found_results') }} <strong class="text-foreground">{{ searchController.totalHits.value.toLocaleString()
                    }}</strong>{{ ' ' }}
                  {{ searchController.totalHits.value === 1 ? $t('search.document_singular') : $t('search.document_plural') }}
                  <template
                    v-if="searchController.searchState.value.query && searchController.searchState.value.query !== '*'">
                    {{ ' ' }}{{ $t('search.by_query') }} <strong class="text-foreground">"{{ searchController.searchState.value.query
                      }}"</strong>
                  </template>
                </template>
              </template>
              <template
                v-else-if="searchController.searchState.value.query && searchController.searchState.value.query.length >= 3 && !searchController.isSearching.value">
                {{ $t('search.no_documents_found') }}
              </template>
              <template
                v-else-if="searchController.searchState.value.query && searchController.searchState.value.query.length > 0 && searchController.searchState.value.query.length < 3">
                {{ $t('search.min_chars_search') }}
              </template>
              <template v-else-if="!searchController.searchState.value.query && !searchController.isSearching.value">
                {{ $t('search.enter_search_or_browse') }}
              </template>
            </div>

            <!-- View Controls -->
            <div class="flex items-center gap-2">
              <!-- View Mode Toggle -->
              <div class="flex bg-muted/50 rounded-lg p-1">
                <Button variant="ghost" size="sm" :class="[
                  'rounded-md px-3 py-2 transition-all duration-200',
                  searchController.viewMode.value === 'list'
                    ? 'bg-background text-foreground shadow-sm'
                    : 'hover:bg-background/50 text-muted-foreground'
                ]" @click="searchController.setViewMode('list')">
                  <List class="w-4 h-4 mr-2" />
                  <span class="text-sm font-medium">{{ $t('search.view_mode_list') }}</span>
                </Button>
                <Button variant="ghost" size="sm" :class="[
                  'rounded-md px-3 py-2 transition-all duration-200',
                  searchController.viewMode.value === 'compact'
                    ? 'bg-background text-foreground shadow-sm'
                    : 'hover:bg-background/50 text-muted-foreground'
                ]" @click="searchController.setViewMode('compact')">
                  <Minus class="w-4 h-4 mr-2" />
                  <span class="text-sm font-medium">{{ $t('search.view_mode_compact') }}</span>
                </Button>
              </div>
            </div>
          </div>

          <!-- Filter Tags (Mobile) -->
          <div v-if="hasActiveFilters" class="md:hidden mb-4">
            <div class="flex flex-wrap gap-2">
              <!-- Tenant filters -->
              <Badge v-for="tenant in searchController.filters.value.tenants" :key="`tenant-${tenant}`"
                variant="secondary" class="gap-1">
                <Building2 class="w-3 h-3" />
                {{ tenant }}
                <Button variant="ghost" size="sm"
                  class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
                  @click="searchController.toggleTenant(tenant)">
                  <X class="w-3 h-3" />
                </Button>
              </Badge>

              <!-- Content type filters -->
              <Badge v-for="contentType in searchController.filters.value.contentTypes" :key="`content-${contentType}`"
                variant="secondary" class="gap-1">
                <FileText class="w-3 h-3" />
                {{ contentType }}
                <Button variant="ghost" size="sm"
                  class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
                  @click="searchController.toggleContentType(contentType)">
                  <X class="w-3 h-3" />
                </Button>
              </Badge>

              <!-- Language filters -->
              <Badge v-for="language in searchController.filters.value.languages" :key="`lang-${language}`"
                variant="secondary" class="gap-1">
                <img 
                  v-if="getLanguageFlag(language)"
                  :src="getLanguageFlag(language)" 
                  :alt="`${getLanguageDisplay(language)} flag`"
                  width="12" 
                  class="rounded-full flex-shrink-0"
                />
                <Globe v-else class="w-3 h-3" />
                {{ getLanguageDisplay(language) }}
                <Button variant="ghost" size="sm"
                  class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
                  @click="searchController.toggleLanguage(language)">
                  <X class="w-3 h-3" />
                </Button>
              </Badge>

              <!-- Clear all -->
              <Button variant="outline" size="sm" class="h-6" @click="searchController.clearFilters">
                <X class="w-3 h-3 mr-1" />
                {{ $t('search.clear_all') }}
              </Button>
            </div>
          </div>

          <!-- Search Results -->
          <DocumentResults :results="searchController.results.value" :view-mode="searchController.viewMode.value"
            :is-loading="searchController.isSearching.value" :has-query="!!searchController.searchState.value.query"
            :search-query="searchController.searchState.value.query" :total-hits="searchController.totalHits.value"
            :has-more-results="searchController.hasMoreResults.value"
            :is-loading-more="searchController.isLoadingMore.value" :has-active-filters
            @load-more="searchController.loadMore" @clear-filters="handleClear" />
        </div>
      </div>
    </div>
  </SearchErrorBoundary>
</template>

<script setup lang="ts">
import { computed, onMounted, watch, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

// ShadcnVue components
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'

// Icons
import {
  Search,
  List,
  Minus,
  Building2,
  FileText,
  Globe,
  X,
  Loader2,
  Filter as FilterIcon,
  WifiOff
} from 'lucide-vue-next'

// Local components
import SearchErrorBoundary from './SearchErrorBoundary.vue'
import DocumentSearchInput from './DocumentSearchInput.vue'
import DocumentFacetSidebar from './DocumentFacetSidebar.vue'
import DocumentResults from './DocumentResults.vue'

// Composables
import { useDocumentSearch } from '@/Composables/useDocumentSearch'

// Initialize search controller
const searchController = useDocumentSearch()

// Local state
const typeToSearch = ref(true) // Enable by default

// Props interface
interface Props {
  initialQuery?: string
  initialFilters?: Record<string, any>
}

const props = withDefaults(defineProps<Props>(), {
  initialQuery: '',
  initialFilters: () => ({})
})

// Computed properties for filter state
const hasActiveFilters = computed(() => Boolean(searchController.hasActiveFilters.value))
const activeFilterCount = computed(() => {
  const filters = searchController.filters.value
  let count = 0
  if (filters.tenants.length > 0) count++
  if (filters.contentTypes.length > 0) count++
  if (filters.languages.length > 0) count++
  if ((filters.dateRange.preset && filters.dateRange.preset !== 'recent') || filters.dateRange.from || filters.dateRange.to) count++
  return count
})

const filterSummary = computed(() => {
  const filters = searchController.filters.value
  const summary: string[] = []
  if (filters.tenants.length > 0) summary.push(`${filters.tenants.length} org.`)
  if (filters.contentTypes.length > 0) summary.push(`${filters.contentTypes.length} type`)
  if (filters.languages.length > 0) summary.push(`${filters.languages.length} lang`)
  if (filters.dateRange.preset !== 'recent' || filters.dateRange.from || filters.dateRange.to) {
    summary.push('date')
  }
  return summary
})

// Event handlers  
const handleQueryUpdate = (query: string) => {
  // Don't trigger search on empty query - let clear handle it
  if (query.trim() !== '') {
    searchController.search(query)
  }
}

const handleSearch = (query: string) => {
  searchController.search(query)
}

const handleSelectRecent = (search: string) => {
  searchController.search(search)
}

const handleClear = () => {
  // Reset to the same state as when page first loads - show all documents
  // This should match the initial load behavior
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

// Language display helpers
const getLanguageFlag = (languageValue: string): string => {
  if (languageValue === 'Lietuvių' || languageValue === 'Lithuanian') return 'https://hatscripts.github.io/circle-flags/flags/lt.svg'
  if (languageValue === 'Anglų' || languageValue === 'English') return 'https://hatscripts.github.io/circle-flags/flags/gb.svg'
  return '' // For Unknown or other languages - no flag
}

// Use computed for translations that need to be reactive
const languageTranslations = computed(() => ({
  lithuanian: 'LT',
  english: 'EN', 
  unknown: $t('search.language_unknown')
}))

const getLanguageDisplay = (languageValue: string): string => {
  if (languageValue === 'Lietuvių' || languageValue === 'Lithuanian') return languageTranslations.value.lithuanian
  if (languageValue === 'Anglų' || languageValue === 'English') return languageTranslations.value.english
  return languageTranslations.value.unknown // For Unknown or other languages
}

// Initialize from props and load all documents
onMounted(async () => {
  // Initialize search client first
  await searchController.initializeSearchClient()

  // Load initial facets for the merged facet system
  await searchController.loadInitialFacets()

  // Apply initial filters if provided
  if (Object.keys(props.initialFilters).length > 0) {
    if (props.initialFilters.tenants) {
      (props.initialFilters.tenants as string[]).forEach((tenant: string) => searchController.toggleTenant(tenant))
    }
    if (props.initialFilters.contentTypes) {
      (props.initialFilters.contentTypes as string[]).forEach((contentType: string) => searchController.toggleContentType(contentType))
    }
    if (props.initialFilters.language) {
      (props.initialFilters.language as string[]).forEach((language: string) => searchController.toggleLanguage(language))
    }
    if (props.initialFilters.dateRange) {
      searchController.setDateRange(props.initialFilters.dateRange)
    }
  }

  // Set initial query if provided, otherwise search all documents
  if (props.initialQuery) {
    searchController.search(props.initialQuery)
  } else {
    // Trigger initial "show all documents" search with newest first sorting  
    // Use wildcard search to show all documents on page load
    searchController.search('*', true) // immediate = true for initial load
  }
})
</script>
