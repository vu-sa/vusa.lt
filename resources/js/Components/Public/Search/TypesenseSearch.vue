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
        <IconSearch class="w-4 h-4 mr-2" />
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
  typesenseConfig?: TypesenseConfig
}

// Component setup
const props = withDefaults(defineProps<SearchComponentProps>(), {
  initialQuery: '',
  class: '',
  searchTerm: '',
  showTrigger: false,
  dialogOpen: false,
  typesenseConfig: undefined
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

// Typesense configuration
const getTypesenseConfig = computed((): TypesenseConfig | null => {
  if (props.typesenseConfig && props.typesenseConfig.apiKey && props.typesenseConfig.apiKey !== 'xyz') {
    return props.typesenseConfig
  }
  return null
})

// Search client setup
const searchAdapter = computed(() => {
  if (!getTypesenseConfig.value) {
    return null
  }

  try {
    return new TypesenseInstantSearchAdapter({
      server: {
        apiKey: getTypesenseConfig.value.apiKey,
        nodes: getTypesenseConfig.value.nodes,
        connectionTimeoutSeconds: 10,
        timeoutSeconds: 20,
      },
      additionalSearchParameters: {
        query_by: 'title,summary,short',
        num_typos: getTypesenseConfig.value.searchParams?.num_typos || 1,
        typo_tokens_threshold: getTypesenseConfig.value.searchParams?.typo_tokens_threshold || 1,
        drop_tokens_threshold: getTypesenseConfig.value.searchParams?.drop_tokens_threshold || 1,
        max_hits: 100, // Limit total results to improve performance
        per_page: 20,
      },
      collectionSpecificSearchParameters: {
        documents: {
          query_by: getTypesenseConfig.value.searchParams?.query_by?.documents || 'title,summary',
          per_page: 15,
        },
        news: {
          query_by: getTypesenseConfig.value.searchParams?.query_by?.news || 'title,short',
          per_page: 15,
        },
        pages: {
          query_by: getTypesenseConfig.value.searchParams?.query_by?.pages || 'title',
          per_page: 10,
        },
        calendar: {
          query_by: getTypesenseConfig.value.searchParams?.query_by?.calendar || 'title,title_lt,title_en',
          per_page: 10,
        },
      }
    })
  } catch (error) {
    console.warn('Failed to initialize Typesense search adapter:', error)
    return null
  }
})

const searchClient = computed(() => searchAdapter.value?.searchClient || null)

// Computed properties
const hasActiveResults = computed(() => searchController.hasActiveResults.value)

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
    console.log('Use Cmd+K (Mac) or Ctrl+K (Windows/Linux) to quickly open search')
    localStorage.setItem('search-shortcut-seen', 'true')
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
