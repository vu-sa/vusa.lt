<template>
  <div 
    data-slot="typesense-search" 
    :class="cn('typesense-search', props.class)"
  >
    <Dialog :open="isOpen" @update:open="updateDialogState">
      <DialogContent class="sm:max-w-4xl h-[60vh] max-h-[calc(100vh-6rem)] p-0 overflow-hidden grid grid-rows-[auto_1fr] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
        <DialogHeader class="px-6 pt-4 pb-0">
          <div class="flex items-center justify-between">
            <DialogTitle>{{ $t('Search') }}</DialogTitle>
            <div class="flex items-center gap-2">
              <!-- Filter Toggle Button -->
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="showFilters = !showFilters"
                :class="{ 'bg-muted': showFilters }"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                <span class="sr-only">{{ $t('Toggle filters') }}</span>
              </Button>
              <!-- Total Results Count -->
              <Badge v-if="searchController.totalResultCount.value > 0" variant="secondary" class="text-xs">
                {{ searchController.totalResultCount.value }}
              </Badge>
              
              <!-- Help/Shortcuts Button -->
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                @click="showKeyboardHelp = !showKeyboardHelp"
                :class="{ 'bg-muted': showKeyboardHelp }"
                :title="$t('Keyboard shortcuts')"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                  <path d="M12 17h.01"/>
                </svg>
                <span class="sr-only">{{ $t('Show keyboard shortcuts') }}</span>
              </Button>
            </div>
          </div>
          <DialogDescription class="sr-only">
            {{ $t('Search across news, pages, documents, and events. Use arrow keys to navigate results.') }}
          </DialogDescription>
        </DialogHeader>

        <!-- Show message if Typesense is not configured -->
        <div v-if="!searchClient" class="p-6 text-center">
          <p class="text-muted-foreground">{{ $t('Search is currently unavailable. Please try again later.') }}</p>
        </div>

        <AisInstantSearch 
          v-else
          :search-client="searchClient"
          index-name="documents" 
          :future="{ preserveSharedStateOnUnmount: true }"
          class="grid grid-rows-[auto_auto_1fr] h-full overflow-hidden"
        >
          <!-- Search Input Area -->
          <div class="px-6 pb-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="mt-4">
              <AisSearchBox>
                <template #="{ refine, currentRefinement, isSearchStalled }">
                  <span style="display: none">{{ refineFunction = refine }}</span>
                  <div class="relative w-full">
                    <Input 
                      ref="searchInputRef"
                      type="search"
                      :model-value="currentRefinement"
                      @input="(e) => { refine(e.target.value); handleUpdateValue(e.target.value); }"
                      :placeholder="$t('Search for news, pages, documents, events...')"
                      class="w-full h-12 text-lg pl-4 pr-24 rounded-md border border-zinc-300 dark:border-zinc-700 focus-visible:ring-2 focus-visible:ring-primary dark:bg-zinc-900"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                      <kbd v-if="!currentRefinement" class="hidden sm:inline-flex h-6 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-xs font-medium text-muted-foreground opacity-70">
                        <span class="text-xs">{{ isMac ? '⌘' : 'Ctrl' }}</span>K
                      </kbd>
                      <Button v-if="currentRefinement" type="button" variant="ghost" size="icon" class="h-6 w-6 hover:bg-zinc-100 dark:hover:bg-zinc-800" @click="clearSearch">
                        <span class="sr-only">{{ $t('Clear search') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>
                      </Button>
                    </div>
                  </div>
                  
                  <!-- Loading indicator -->
                  <div v-if="isSearchStalled" class="flex items-center justify-center mt-2">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                      <div class="animate-spin rounded-full h-4 w-4 border-2 border-primary border-t-transparent"></div>
                      {{ $t('Searching...') }}
                    </div>
                  </div>
                </template>
              </AisSearchBox>
            </div>
          </div>

          <AisConfigure :hits-per-page.camel="20" />

          <!-- Filters Panel (collapsible) -->
          <div v-if="showFilters" class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
            <SearchFilters
              :content-types="searchController.contentTypes.value"
              :result-order="searchController.preferences.value.resultOrder"
              :group-results="searchController.preferences.value.groupResults"
              :result-counts="searchController.resultCounts.value"
              :recent-searches="searchController.preferences.value.recentSearches"
              @toggle-content-type="searchController.toggleContentType"
              @set-result-order="searchController.setResultOrder"
              @toggle-group-results="searchController.toggleGroupResults"
              @clear-recent-searches="searchController.clearRecentSearches"
              @reset-to-defaults="searchController.resetToDefaults"
              @select-recent-search="(search) => { searchController.searchQuery.value = search; refineFunction && refineFunction(search); handleUpdateValue(search); }"
            />
          </div>

          <!-- Keyboard Help Panel (collapsible) -->
          <div v-if="showKeyboardHelp" class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-blue-50 dark:bg-blue-950/30">
            <div class="space-y-3">
              <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ $t('Keyboard Shortcuts') }}</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Open/Close Search') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">{{ isMac ? '⌘K' : 'Ctrl+K' }}</kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Toggle Filters') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">{{ isMac ? '⌘F' : 'Ctrl+F' }}</kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Toggle View Mode') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">{{ isMac ? '⌘G' : 'Ctrl+G' }}</kbd>
                  </div>
                </div>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Toggle News') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+1</kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Toggle Pages') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+2</kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Toggle Documents') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+3</kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">{{ $t('Toggle Events') }}</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-zinc-800 border rounded text-xs">Alt+4</kbd>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Search Results Area -->
          <div v-if="hasActiveResults" class="grid grid-cols-1 lg:grid-cols-[1fr_1.5fr] overflow-hidden">
            <!-- Results List -->
            <div class="flex flex-col h-full lg:border-r border-zinc-200 dark:border-zinc-700 overflow-hidden">
              <!-- Stats Header -->
              <div class="px-6 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                  <!-- Show AisStats for grouped view -->
                  <template v-if="searchController.preferences.value.groupResults">
                    <AisStats>
                      <template #="{ nbHits, processingTimeMS }">
                        {{ $t('Found :count results', { count: nbHits.toLocaleString() }) }}
                        <span v-if="processingTimeMS !== undefined" class="ml-1">{{ $t('in :time ms', { time: processingTimeMS }) }}</span>
                      </template>
                    </AisStats>
                  </template>
                  
                  <!-- Show unified stats for unified view -->
                  <template v-else>
                    {{ $t('Found :count results', { count: searchController.totalResultCount.value.toLocaleString() }) }}
                  </template>
                </div>
              </div>

              <!-- Grouped Results (default) -->
              <ScrollArea v-if="searchController.preferences.value.groupResults" class="h-full">
                <div class="space-y-4 p-4" role="listbox" aria-label="Search results">
                  <!-- Render sections for enabled content types in configured order -->
                  <SearchResultSection 
                    v-for="contentType in searchController.orderedTypes.value"
                    :key="contentType.id"
                    :index-name="contentType.indexName"
                    :title="$t(contentType.name)"
                    :icon="contentType.icon"
                    :type="contentType.id"
                    :color="contentType.color"
                    :selected-item="selectedItem"
                    :result-order="searchController.preferences.value.resultOrder"
                    @select-item="selectItem"
                    @update-result-count="(count) => searchController.updateResultCount(contentType.id, count)"
                    @update-total-hits="(totalHits) => searchController.updateTotalResultCount(contentType.id, totalHits)"
                  />
                </div>
              </ScrollArea>
              
              <!-- Unified Results (alternative view) -->
              <ScrollArea v-else class="h-full">
                <UnifiedResults
                  :enabled-content-types="searchController.selectedTypes.value"
                  :selected-item="selectedItem"
                  :result-order="searchController.preferences.value.resultOrder"
                  @select-item="selectItem"
                  @update-result-count="(count) => searchController.updateTotalResultCount('unified', count)"
                  @update-total-hits="(contentTypeId, totalHits) => searchController.updateTotalResultCount(contentTypeId, totalHits)"
                  @toggle-view="() => searchController.toggleGroupResults()"
                />
              </ScrollArea>
            </div>

            <!-- Preview Panel -->
            <div class="hidden lg:block h-full overflow-y-auto bg-zinc-50 dark:bg-zinc-900/50">
              <div v-if="selectedItem" class="p-4">
                <div class="mb-4 flex items-start justify-between">
                  <div class="flex items-center gap-2 flex-wrap">
                    <Badge :class="getBadgeClasses(selectedItem.type)" class="text-xs">{{ getTypeBadgeText(selectedItem.type) }}</Badge>
                    <time class="text-xs text-muted-foreground">{{ formatDate(getItemDate(selectedItem)) }}</time>
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    @click="navigateToItem(selectedItem)"
                    class="inline-flex items-center gap-1 flex-shrink-0 text-xs"
                  >
                    {{ $t('Open') }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                  </Button>
                </div>
                
                <div class="space-y-4">
                  <div>
                    <h2 class="text-lg font-semibold leading-tight mb-2">
                      <AisHighlight attribute="title" :hit="selectedItem" />
                      <AisHighlight v-if="!selectedItem.title && selectedItem.name" attribute="name" :hit="selectedItem" />
                      <span v-if="!selectedItem.title && !selectedItem.name">{{ selectedItem.name || selectedItem.title || $t('Untitled') }}</span>
                    </h2>
                  </div>
                  
                  <div v-if="selectedItem.image_url || selectedItem.image" class="w-full h-32 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <img 
                      :src="selectedItem.image_url || selectedItem.image" 
                      :alt="selectedItem.title || selectedItem.name"
                      class="h-full w-full object-cover"
                      loading="lazy"
                    >
                  </div>
                  
                  <div v-if="getPreviewContent(selectedItem)" class="text-sm leading-relaxed">
                    <div class="text-muted-foreground">{{ getPreviewContent(selectedItem) }}</div>
                  </div>
                  
                  <div v-if="selectedItem.tenant_name" class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                      <span>{{ selectedItem.tenant_name }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-else class="h-full flex items-center justify-center">
                <div class="text-center p-6 max-w-sm">
                  <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                  </div>
                  <h3 class="font-medium text-sm mb-2">{{ $t('Select an item to preview') }}</h3>
                  <p class="text-xs text-muted-foreground">{{ $t('Click on any search result to see a detailed preview here') }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="flex-1 flex items-center justify-center p-8">
            <div class="text-center max-w-md space-y-4">
              <div class="mx-auto w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              </div>
              <div>
                <h3 class="font-medium text-lg mb-2">
                  {{ searchController.searchQuery.value ? $t('Keep typing to see results') : $t('Start your search') }}
                </h3>
                <p class="text-muted-foreground text-sm">
                  {{ searchController.searchQuery.value ? $t('Please enter at least 3 characters to search') : $t('Search across your selected content types') }}
                </p>
                <!-- Show selected content types -->
                <div v-if="searchController.selectedTypes.value.length > 0" class="mt-3">
                  <p class="text-xs text-muted-foreground mb-2">{{ $t('Searching in') }}:</p>
                  <div class="flex flex-wrap gap-1">
                    <Badge
                      v-for="type in searchController.selectedTypes.value"
                      :key="type.id"
                      variant="outline"
                      class="text-xs"
                    >
                      {{ type.icon }} {{ $t(type.name) }}
                    </Badge>
                  </div>
                </div>
              </div>
              <div v-if="!searchController.searchQuery.value" class="space-y-2">
                <!-- Recent searches if available -->
                <div v-if="searchController.preferences.value.recentSearches.length > 0">
                  <p class="text-xs text-muted-foreground">{{ $t('Recent searches') }}:</p>
                  <div class="flex flex-wrap gap-2 justify-center">
                    <Button 
                      v-for="search in searchController.preferences.value.recentSearches.slice(0, 3)" 
                      :key="search"
                      variant="ghost" 
                      size="sm" 
                      class="text-xs"
                      @click="searchController.searchQuery.value = search; refineFunction && refineFunction(search); handleUpdateValue(search)"
                    >
                      {{ search }}
                    </Button>
                  </div>
                </div>
                <!-- Fallback suggestions -->
                <div v-else>
                  <p class="text-xs text-muted-foreground">{{ $t('Popular searches') }}:</p>
                  <div class="flex flex-wrap gap-2 justify-center">
                    <Button 
                      v-for="suggestion in ['Student events', 'Academic calendar', 'News updates']" 
                      :key="suggestion"
                      variant="ghost" 
                      size="sm" 
                      class="text-xs"
                      @click="searchController.searchQuery.value = suggestion; refineFunction && refineFunction(suggestion); handleUpdateValue(suggestion)"
                    >
                      {{ $t(suggestion) }}
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </AisInstantSearch>
      </DialogContent>
    </Dialog>

    <!-- Search Trigger Button (optional) -->
    <div v-if="showTrigger">
      <Button @click="openDialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        {{ $t('Search') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, type HTMLAttributes, watch, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { format } from 'date-fns'
import { cn } from '@/Utils/Shadcn/utils'
import { debounce } from 'lodash-es'

// Search controller
import { useSearchController } from '@/Composables/useSearchController'

// Typesense search adapter
import TypesenseInstantSearchAdapter from 'typesense-instantsearch-adapter'
import {
  AisInstantSearch,
  AisSearchBox,
  AisConfigure,
  AisHighlight,
  AisStats
} from 'vue-instantsearch/vue3/es'

// UI components
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { Separator } from '@/Components/ui/separator'

// Local components
import SearchResultSection from './SearchResultSection.vue'
import SearchFilters from './SearchFilters.vue'
import UnifiedResults from './UnifiedResults.vue'

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

interface SearchItem {
  id: string | number
  title: string
  type: 'documents' | 'pages' | 'news' | 'calendar'
  description?: string
  content?: string
  summary?: string
  image_url?: string
  created_at?: string
  permalink?: string
  anonymous_url?: string
  [key: string]: any
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

// Search controller
const searchController = useSearchController()

// Reactive state
const page = usePage()
const isOpen = ref(props.dialogOpen)
const selectedItem = ref<SearchItem | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
const showFilters = ref(false)
const showKeyboardHelp = ref(false)
const refineFunction = ref<((query: string) => void) | null>(null)

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
  selectedItem.value = null
  searchController.setSearching(false)
})

// Dialog state management
const updateDialogState = (value: boolean) => {
  isOpen.value = value
  emit('update:dialogOpen', value)
  if (!value) {
    emit('close')
  } else {
    emit('open')
  }
}

// Search input handling with debouncing and minimum length check
const debouncedSearchUpdate = debounce((value: string) => {
  // Only update search if query is long enough or empty (for clearing)
  if (value.length >= 3 || value.length === 0) {
    searchController.searchQuery.value = value
    emit('update:searchTerm', value)
    selectedItem.value = null
  }
}, 300) // 300ms debounce

const handleUpdateValue = (value: string) => {
  // Update UI immediately for responsiveness, but debounce the actual search
  debouncedSearchUpdate(value)
}

// Item selection
const selectItem = (item: SearchItem) => {
  if (selectedItem.value?.id === item.id && selectedItem.value?.type === item.type) {
    return
  }
  
  selectedItem.value = item
  
  // Analytics tracking
  trackSearchInteraction('result_click', {
    item_type: item.type,
    item_id: item.id,
    search_query: searchController.searchQuery.value
  })
}

// Navigation
const navigateToItem = (item: SearchItem) => {
  trackSearchInteraction('result_navigate', {
    item_type: item.type,
    item_id: item.id,
    search_query: searchController.searchQuery.value
  })
  
  router.visit(getItemUrl(item))
  updateDialogState(false)
}

// Analytics tracking
const trackSearchInteraction = (action: string, data: Record<string, any>) => {
  if (typeof window !== 'undefined' && (window as any).posthog) {
    (window as any).posthog.capture(`search_${action}`, {
      ...data,
      timestamp: new Date().toISOString()
    })
  }
  
  try {
    const searches = JSON.parse(localStorage.getItem('search_analytics') || '[]')
    searches.push({ action, ...data, timestamp: Date.now() })
    if (searches.length > 100) {
      searches.splice(0, searches.length - 100)
    }
    localStorage.setItem('search_analytics', JSON.stringify(searches))
  } catch (e) {
    // Ignore localStorage errors
  }
}

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
          query_by: getTypesenseConfig.value.searchParams?.query_by?.calendar || 'title_lt,title_en',
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

const isMac = computed(() => {
  if (typeof navigator === 'undefined') return false
  return navigator.platform?.includes('Mac') || navigator.userAgent?.includes('Mac') || false
})

// Helper functions
const formatDate = (dateValue: string | number | undefined) => {
  if (!dateValue) return ''
  
  try {
    let date: Date
    
    if (typeof dateValue === 'number') {
      const timestamp = dateValue < 10000000000 ? dateValue * 1000 : dateValue
      date = new Date(timestamp)
    } else {
      date = new Date(dateValue)
    }
    
    if (isNaN(date.getTime())) {
      return String(dateValue)
    }
    
    return format(date, 'MMM dd, yyyy')
  } catch {
    return String(dateValue)
  }
}

const getPreviewContent = (item: SearchItem) => {
  let content = ''
  if (item.type === 'news' && item.short) {
    content = item.short
  } else if (item.summary) {
    content = item.summary
  } else if (item.description) {
    content = item.description
  } else if (item.content) {
    content = item.content.slice(0, 500) + (item.content.length > 500 ? '...' : '')
  }
  
  return content.replace(/<script[^>]*>.*?<\/script>/gi, '')
                .replace(/<style[^>]*>.*?<\/style>/gi, '')
                .replace(/<[^>]+>/g, '')
                .trim()
}

const getTypeBadgeText = (type: string) => {
  switch (type) {
    case 'documents': return $t('Document')
    case 'pages': return $t('Page')
    case 'news': return $t('News')
    case 'calendar': return $t('Event')
    default: return type
  }
}

const getBadgeClasses = (type: string) => {
  switch (type) {
    case 'news': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200'
    case 'pages': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 border-green-200'
    case 'documents': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border-purple-200'
    case 'calendar': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200'
    default: return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
  }
}

const getItemDate = (item: SearchItem) => {
  switch (item.type) {
    case 'news': return item.publish_time
    case 'documents': return item.document_date
    case 'calendar': return item.date
    default: return item.created_at
  }
}

const getItemUrl = (item: SearchItem): string => {
  const baseParams = {
    lang: item.lang || page.props.app.locale,
    subdomain: page.props.tenant?.subdomain ?? 'www'
  }

  try {
    switch (item.type) {
      case 'news':
        return route('news', { ...baseParams, news: item.permalink, newsString: 'naujiena' })
      case 'pages':
        return route('page', { ...baseParams, permalink: item.permalink })
      case 'calendar':
        return route('calendar.event', { ...baseParams, calendar: item.id })
      case 'documents':
        return item.anonymous_url || '#'
      default:
        return '#'
    }
  } catch (e) {
    console.error('Error generating URL:', e)
    return '#'
  }
}

const clearSearch = () => {
  const previousQuery = searchController.searchQuery.value
  searchController.searchQuery.value = ''
  selectedItem.value = null
  emit('update:searchTerm', '')
  
  if (previousQuery) {
    trackSearchInteraction('search_cleared', {
      previous_query: previousQuery
    })
  }
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
      if (selectedItem.value) {
        navigateToItem(selectedItem.value)
      }
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
  searchInputRef.value?.focus()
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
