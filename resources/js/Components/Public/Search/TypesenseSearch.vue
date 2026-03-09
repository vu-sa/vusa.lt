<template>
  <div data-slot="typesense-search" :class="cn('typesense-search', props.class)">
    <SearchDialog :is-open :search-client :total-result-count="searchController.totalResultCount.value" :show-filters
      :show-keyboard-help @update:is-open="updateDialogState" @close="emit('close')" @open="emit('open')"
      @toggle-filters="showFilters = !showFilters" @toggle-keyboard-help="showKeyboardHelp = !showKeyboardHelp">
      <AisInstantSearch ref="instantSearchRef" :search-client index-name="documents"
        :future="{ preserveSharedStateOnUnmount: true }" class="flex flex-col h-full overflow-hidden">
        <!-- Search Input Area -->
        <SearchInput ref="searchInputRef" :search-query="searchController.searchQuery.value"
          @update:search-query="handleSearchQueryUpdate" @clear="handleClearSearch" />

        <!-- Content Type Indicators (when filters are closed and some types are disabled OR no types are enabled) -->
        <div v-if="!showFilters && shouldShowIndicators" 
          class="flex-shrink-0 px-6 py-1 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/25">
<!-- When types are enabled - show indicators -->
          <div v-if="hasEnabledTypes" class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-muted-foreground font-medium">
              {{ $t('search.searching_in') }}:
            </span>
            <div
              v-for="contentType in enabledContentTypes"
              :key="contentType.id"
              :class="getIndicatorClasses(contentType)"
              class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium transition-all duration-200"
            >
              <span class="text-xs">{{ contentType.icon }}</span>
              <span>{{ $t(contentType.name) }}</span>
              <Badge
                v-if="getContentTypeResultCount(contentType.id) !== undefined"
                :class="getIndicatorBadgeClasses()"
                class="text-xs px-1 py-0 h-4 min-h-0"
              >
                {{ getContentTypeResultCount(contentType.id) }}
              </Badge>
              <!-- Close button -->
              <button
                class="ml-0.5 p-0.5 rounded-sm hover:bg-red-200 dark:hover:bg-red-800/50 transition-colors focus:outline-none focus:ring-1 focus:ring-vusa-red focus:ring-opacity-50"
                :title="$t('search.click_to_disable_type', { type: $t(contentType.name) })"
                @click="searchController.toggleContentType(contentType.id)"
              >
                <svg class="w-2.5 h-2.5 opacity-60 hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <!-- Clear all button when multiple types selected -->
            <button
              v-if="enabledContentTypes.length > 1"
              class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium transition-all duration-200 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-400 focus:ring-opacity-50"
              :title="$t('search.clear_all_types')"
              @click="clearAllTypes"
            >
              <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              {{ $t('search.clear_all') }}
            </button>
          </div>
          
          <!-- When no types enabled - show warning and enable all button -->
          <div v-else class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <span class="text-xs font-medium">{{ $t('search.no_content_types_selected') }}</span>
            </div>
            <Button
              variant="outline"
              size="sm"
              class="text-xs h-6 px-2"
              @click="enableAllTypes"
            >
              {{ $t('search.enable_all_types') }}
            </Button>
          </div>
        </div>

        <AisConfigure :hits-per-page.camel="20" />

        <!-- Hidden collectors for each content type to feed the search service -->
        <SearchContentTypeCollectors :enabled-content-types="searchController.selectedTypes.value"
          @update-results="handleUpdateContentTypeResults" />

        <!-- Filters Panel (collapsible) -->
        <div v-if="showFilters"
          class="flex-shrink-0 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 max-h-[40vh] overflow-y-auto">
          <div class="px-6 py-4">
            <SearchFilters :content-types="searchController.contentTypes.value"
              :result-order="searchController.preferences.value.resultOrder"
              :group-results="searchController.preferences.value.groupResults"
              :result-counts="searchController.resultCounts.value"
              :recent-searches="searchController.preferences.value.recentSearches"
              @toggle-content-type="searchController.toggleContentType"
              @set-result-order="searchController.setResultOrder"
              @toggle-group-results="searchController.toggleGroupResults"
              @clear-recent-searches="searchController.clearRecentSearches"
              @reset-to-defaults="searchController.resetToDefaults" @select-recent-search="handleSelectSearch" />
          </div>
        </div>

        <!-- Keyboard Help Panel (collapsible) -->
        <div v-if="showKeyboardHelp" class="flex-shrink-0">
          <SearchKeyboardHelp />
        </div>

        <!-- Search Results or Empty State -->
        <div class="flex-1 min-h-0">
          <SearchResultsArea v-if="hasActiveResults" :group-results="searchController.preferences.value.groupResults"
            :ordered-types="searchController.orderedTypes.value" :selected-types="searchController.selectedTypes.value"
            :result-order="searchController.preferences.value.resultOrder"
            :total-result-count="searchController.totalResultCount.value" @navigate-to-item="handleNavigateToItem"
            @update-result-count="(typeId, count) => searchController.updateResultCount(typeId, count)"
            @update-total-hits="(typeId, totalHits) => searchController.updateTotalResultCount(typeId, totalHits)"
            @toggle-view="searchController.toggleGroupResults" />

          <SearchEmptyState v-else :search-query="searchController.searchQuery.value"
            :selected-types="searchController.selectedTypes.value"
            :recent-searches="searchController.preferences.value.recentSearches" @select-search="handleSelectSearch" />
        </div>
      </AisInstantSearch>
    </SearchDialog>

    <!-- Search Trigger Button (optional) -->
    <div v-if="showTrigger">
      <Button @click="openDialog">
        <IconSearch class="w-4 h-4 mr-1" />
        {{ $t('search.search') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, type HTMLAttributes, watch, nextTick, provide } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { cn } from '@/Utils/Shadcn/utils'

// Search controller and utilities
import { useSearchController } from '@/Composables/useSearchController'
import { useSearchUtils, type SearchItem } from '@/Composables/useSearchUtils'
import { useSearchService } from '@/Composables/useSearchService'

// Typesense search adapter
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter'
import { AisInstantSearch, AisConfigure } from 'vue-instantsearch/vue3/es'
import IconSearch from '~icons/fluent/search20-regular'

// UI components
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'

// Local components
import SearchDialog from './SearchDialog.vue'
import SearchInput from './SearchInput.vue'
import SearchKeyboardHelp from './SearchKeyboardHelp.vue'
import SearchResultsArea from './SearchResultsArea.vue'
import SearchEmptyState from './SearchEmptyState.vue'
import SearchFilters from './SearchFilters.vue'
import SearchContentTypeCollectors from './SearchContentTypeCollectors.vue'

// TypeScript interfaces
interface TypesenseConfig {
  apiKey: string
  nodes: Array<{
    host: string
    port: number
    protocol: string
    path?: string
  }>
  collections?: {
    news?: string
    pages?: string
    documents?: string
    calendar?: string
    public_institutions?: string
    public_meetings?: string
  }
  searchParams?: {
    query_by?: {
      documents?: string
      pages?: string
      news?: string
      calendar?: string
    }
    num_typos?: number
    typo_tokens_threshold?: number
    drop_tokens_threshold?: number
  }
}

interface SearchComponentProps {
  initialQuery?: string
  class?: HTMLAttributes['class']
  searchTerm?: string
  showTrigger?: boolean
  dialogOpen?: boolean
}

// Component setup
const props = withDefaults(defineProps<SearchComponentProps>(), {
  initialQuery: '',
  class: '',
  searchTerm: '',
  showTrigger: false,
  dialogOpen: false,
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'open'): void
  (e: 'update:searchTerm', value: string): void
  (e: 'update:dialogOpen', value: boolean): void
}>()

// Search controller and utilities
const searchController = useSearchController()
const { trackSearchInteraction } = useSearchUtils()
const searchService = useSearchService()

// Provide the search service to child components
provide('searchService', searchService)

// Reactive state
const page = usePage()
const isOpen = ref(props.dialogOpen)
const searchInputRef = ref<InstanceType<typeof SearchInput> | null>(null)
const instantSearchRef = ref<InstanceType<typeof AisInstantSearch> | null>(null)
const showFilters = ref(false)
const showKeyboardHelp = ref(false)

// Initialize search query from props or controller
searchController.searchQuery.value = props.searchTerm || props.initialQuery || ''

// Watch for prop changes
watch(() => props.searchTerm, (newVal) => {
  if (newVal !== searchController.searchQuery.value) {
    searchController.searchQuery.value = newVal
  }
}, { immediate: true })

watch(() => props.dialogOpen, (newVal) => {
  isOpen.value = newVal
}, { immediate: true })

// Reset when search changes
watch(() => searchController.searchQuery.value, () => {
  searchController.setSearching(false)
})

// Dialog state management
const updateDialogState = (value: boolean) => {
  isOpen.value = value
  emit('update:dialogOpen', value)
}

// Search input handlers
const handleSearchQueryUpdate = (value: string) => {
  searchController.searchQuery.value = value
  emit('update:searchTerm', value)
}

const handleClearSearch = () => {
  // Clear search handled by SearchInput component
}

const handleSelectSearch = (search: string) => {
  searchController.searchQuery.value = search
  if (searchInputRef.value?.refineFunction) {
    searchInputRef.value.refineFunction(search)
  }
  emit('update:searchTerm', search)
}

// Remove selection logic - no longer needed

// Navigation handler
const handleNavigateToItem = (item: SearchItem) => {
  trackSearchInteraction('result_navigate', {
    item_type: item.type,
    item_id: item.id,
    search_query: searchController.searchQuery.value
  })

  // Navigate using the utility function
  const { navigateToItem } = useSearchUtils()
  navigateToItem(item)

  // Close the dialog
  updateDialogState(false)
}

// Handler for content type results from collectors
const handleUpdateContentTypeResults = (
  contentTypeId: string,
  contentType: any,
  results: any[],
  totalHits: number,
  isLastPage: boolean,
  refineNext: () => void
) => {
  // Update the search service with results from this content type
  searchService.updateContentTypeResults(
    contentTypeId,
    contentType,
    results,
    totalHits,
    isLastPage,
    refineNext
  )
}

// Remove preview handler - no longer needed

// Typesense configuration - now uses global shared data
const getTypesenseConfig = computed((): TypesenseConfig | null => {
  const typesenseConfig = page.props.typesenseConfig as any
  
  if (typesenseConfig?.apiKey) {
    return typesenseConfig
  }
  return null
})

// Search client setup
const searchAdapter = computed(() => {
  if (!getTypesenseConfig.value) {
    return null
  }

  // Get collection names from config (with staging prefix if applicable)
  const collections = getTypesenseConfig.value.collections || {}
  const documentsCollection = collections.documents || 'documents'
  const newsCollection = collections.news || 'news'
  const pagesCollection = collections.pages || 'pages'
  const calendarCollection = collections.calendar || 'calendar'
  const publicInstitutionsCollection = collections.public_institutions || 'public_institutions'

  try {
    // Build collection-specific search parameters with dynamic collection names
    const collectionSpecificSearchParameters: Record<string, any> = {}
    
    collectionSpecificSearchParameters[documentsCollection] = {
      query_by: getTypesenseConfig.value.searchParams?.query_by?.documents || 'title,summary,content_type,document_year,document_date_formatted',
      query_by_weights: '10,3,2,6,4',
      sort_by: '_text_match:desc,document_date:desc',
      per_page: 15,
    }
    
    collectionSpecificSearchParameters[newsCollection] = {
      query_by: getTypesenseConfig.value.searchParams?.query_by?.news || 'title,short',
      query_by_weights: '10,4',
      sort_by: '_text_match:desc,publish_time:desc',
      per_page: 15,
    }
    
    collectionSpecificSearchParameters[pagesCollection] = {
      query_by: getTypesenseConfig.value.searchParams?.query_by?.pages || 'title',
      query_by_weights: '10',
      sort_by: '_text_match:desc,created_at:desc',
      per_page: 10,
    }
    
    collectionSpecificSearchParameters[calendarCollection] = {
      query_by: getTypesenseConfig.value.searchParams?.query_by?.calendar || 'title,title_lt,title_en',
      query_by_weights: '10,8,8',
      sort_by: '_text_match:desc,date:desc',
      per_page: 10,
    }
    
    collectionSpecificSearchParameters[publicInstitutionsCollection] = {
      query_by: 'title,name_lt,name_en,short_name_lt,short_name_en,alias',
      query_by_weights: '10,10,8,6,4,3',
      sort_by: '_text_match:desc,updated_at:desc',
      per_page: 10,
    }

    return new TypesenseInstantSearchAdapter({
      server: {
        apiKey: getTypesenseConfig.value.apiKey,
        nodes: getTypesenseConfig.value.nodes,
        connectionTimeoutSeconds: 20,
      },
      additionalSearchParameters: {
        query_by: 'title,summary,short',
        prefix: false,
        infix: 'fallback',
        sort_by: '_text_match:desc,created_at:desc',
        num_typos: getTypesenseConfig.value.searchParams?.num_typos || 1,
        typo_tokens_threshold: getTypesenseConfig.value.searchParams?.typo_tokens_threshold || 2,
        drop_tokens_threshold: getTypesenseConfig.value.searchParams?.drop_tokens_threshold || 10,
        min_len_1typo: 4,
        min_len_2typo: 7,
        prioritize_exact_match: true,
        prioritize_token_position: true,
        max_hits: 100,
        per_page: 20,
      },
      collectionSpecificSearchParameters
    })
  } catch (error) {
    console.warn('Failed to initialize Typesense search adapter:', error)
    return null
  }
})

const searchClient = computed(() => searchAdapter.value?.searchClient || null)

// Computed properties
const hasActiveResults = computed(() => searchController.hasActiveResults.value)

// Content type indicators
const enabledContentTypes = computed(() => 
  searchController.contentTypes.value.filter(type => type.enabled)
)

const hasEnabledTypes = computed(() => 
  enabledContentTypes.value.length > 0
)

// Show indicators whenever filters are closed
// This allows users to see what they're searching and quickly toggle types
const shouldShowIndicators = computed(() => {
  const contentTypes = searchController.contentTypes.value
  if (!contentTypes || contentTypes.length === 0) return false
  
  return true // Always show when filters are closed
})

// Helper functions for indicators
const getContentTypeResultCount = (contentTypeId: string) => {
  const totalKey = `${contentTypeId}_total`
  const displayedKey = contentTypeId
  
  return searchController.resultCounts.value[totalKey] !== undefined 
    ? searchController.resultCounts.value[totalKey] 
    : searchController.resultCounts.value[displayedKey]
}

const getIndicatorClasses = (contentType: any) => {
  return 'bg-red-50 dark:bg-red-950/30 border border-vusa-red/30 dark:border-vusa-red/50 text-vusa-red dark:text-red-300'
}

const getIndicatorBadgeClasses = () => {
  return 'bg-vusa-red/10 text-vusa-red border-vusa-red/20 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800/50'
}

// Helper actions for indicators
const clearAllTypes = () => {
  // Disable all content types
  searchController.contentTypes.value.forEach(type => {
    if (type.enabled) {
      searchController.toggleContentType(type.id)
    }
  })
}

const enableAllTypes = () => {
  // Enable all content types
  searchController.contentTypes.value.forEach(type => {
    if (!type.enabled) {
      searchController.toggleContentType(type.id)
    }
  })
}

// Keyboard handlers
const handleKeydown = (event: KeyboardEvent) => {
  // Handle Ctrl/Cmd+K - toggle open/close
  if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
    event.preventDefault()
    if (isOpen.value) {
      updateDialogState(false)
    } else {
      openDialog()
    }
    return
  }

  // Only handle these keys when dialog is open
  if (!isOpen.value) return

  switch (event.key) {
    case 'Escape':
      event.preventDefault()
      updateDialogState(false)
      break
    case 'Enter':
      event.preventDefault()
      // Enter key handling moved to individual result items
      break
    case 'F':
    case 'f':
      // Ctrl/Cmd+F to toggle filters
      if (event.ctrlKey || event.metaKey) {
        event.preventDefault()
        showFilters.value = !showFilters.value
      }
      break
    case 'G':
    case 'g':
      // Ctrl/Cmd+G to toggle grouped results
      if (event.ctrlKey || event.metaKey) {
        event.preventDefault()
        searchController.toggleGroupResults()
      }
      break
    case '1':
    case '2':
    case '3':
    case '4':
      // Alt+Number to toggle specific content types
      if (event.altKey) {
        event.preventDefault()
        const typeIndex = parseInt(event.key) - 1
        const contentTypes = searchController.contentTypes.value
        if (typeIndex < contentTypes.length) {
          searchController.toggleContentType(contentTypes[typeIndex].id)
        }
      }
      break
  }
}

const focusSearchInput = async () => {
  await nextTick()
  searchInputRef.value?.focusSearchInput()
}

const openDialog = async () => {
  isOpen.value = true
  emit('update:dialogOpen', true)
  emit('open')
  await focusSearchInput()

  trackSearchInteraction('search_opened', {
    trigger: 'keyboard_shortcut'
  })
}

// Watch for search client initialization and set up search service
watch(searchClient, (newClient) => {
  if (newClient) {
    searchService.initializeSearchClient(newClient)
  }
}, { immediate: true })

// Watch for instant search instance initialization
watch(instantSearchRef, (newInstance) => {
  if (newInstance) {
    searchService.initializeInstantSearch(newInstance)
  }
}, { immediate: true })

// Coordinate search queries between controller and service
watch(() => searchController.searchQuery.value, (newQuery) => {
  if (newQuery !== searchService.searchQuery.value) {
    searchService.search(newQuery, searchController.selectedTypes.value)
  }
})

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)

  const urlParams = new URLSearchParams(window.location.search)
  const q = urlParams.get('q')
  if (q) {
    searchController.searchQuery.value = q
    emit('update:searchTerm', q)
  }

  if (!localStorage.getItem('search-shortcut-seen')) {
    localStorage.setItem('search-shortcut-seen', 'true')
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
