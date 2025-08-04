<template>
  <div class="search-results-container transition-all duration-300 ease-out">
    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-4 min-h-[60vh] sm:min-h-[600px]">
      <div class="space-y-4" :class="viewMode === 'compact' ? 'space-y-2' : 'space-y-4'">
        <DocumentResultsSkeleton v-for="i in getSkeletonCount()" :key="i" :view-mode />
      </div>
    </div>

    <!-- Results -->
    <div v-else-if="results.length > 0" class="space-y-4 min-h-[60vh] sm:min-h-[600px]">
      <!-- List View -->
      <div v-if="viewMode === 'list'" class="space-y-3">
        <TransitionGroup name="document-list" tag="div" class="space-y-3" appear>
          <DocumentListItem v-for="document in results" :key="document.id" :document />
        </TransitionGroup>
      </div>

      <!-- Compact View -->
      <div v-else-if="viewMode === 'compact'" class="space-y-2">
        <TransitionGroup name="document-compact" tag="div" class="space-y-2" appear>
          <DocumentCompactListItem v-for="document in results" :key="document.id" :document />
        </TransitionGroup>
      </div>

      <!-- Load More Button (if needed for pagination) -->
      <div v-if="hasMoreResults" class="flex justify-center pt-6">
        <Button variant="outline" size="lg" :disabled="isLoadingMore"
          class="transition-all duration-200 hover:scale-105 focus:scale-105" @click="loadMore">
          <template v-if="isLoadingMore">
            <Loader2 class="w-4 h-4 mr-2 animate-spin" />
            {{ $t('search.loading_more') }}
          </template>
          <template v-else>
            <ChevronDown class="w-4 h-4 mr-2" />
            {{ $t('search.show_more_results') }}
          </template>
        </Button>
      </div>
    </div>


    <!-- Empty State for No Results (when search was attempted) -->
    <div v-else-if="showNoResultsState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <SearchX class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t('search.no_documents_found') }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t('search.no_results_criteria') }}
        </p>

        <Button variant="outline" @click="$emit('clearFilters')">
          <RotateCcw class="w-4 h-4 mr-2" />
          {{ $t('search.clear_filters_action') }}
        </Button>
      </div>
    </div>

    <!-- Short Query State (when query is too short) -->
    <div v-else-if="showShortQueryState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <Search class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t('search.min_chars_required') }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t('search.min_chars_description') }}
        </p>
        <p class="text-sm text-muted-foreground">
          {{ $t('search.or_browse_all') }} <button class="text-primary hover:underline font-medium" @click="$emit('clearFilters')">
            {{ $t('search.browse_all_documents') }}
          </button>
        </p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <AlertTriangle class="w-16 h-16 mx-auto text-destructive mb-6" />
        <h3 class="text-xl font-semibold text-foreground mb-3">
          {{ $t('search.search_error') }}
        </h3>
        <p class="text-muted-foreground mb-6">
          {{ $t('search.technical_error') }}
        </p>

        <div class="flex gap-2 justify-center">
          <Button variant="outline" @click="$emit('retry')">
            <RefreshCw class="w-4 h-4 mr-2" />
            {{ $t('search.try_again') }}
          </Button>
          <Button variant="ghost" @click="$emit('reportError')">
            <Flag class="w-4 h-4 mr-2" />
            {{ $t('search.report_error') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// ShadcnVue components
import { Button } from '@/Components/ui/button'

// Icons
import {
  Search,
  SearchX,
  RotateCcw,
  ChevronDown,
  Loader2,
  AlertTriangle,
  RefreshCw,
  Flag
} from 'lucide-vue-next'

// Local components
import DocumentListItem from './DocumentListItem.vue'
import DocumentCompactListItem from './DocumentCompactListItem.vue'
import DocumentResultsSkeleton from './DocumentResultsSkeleton.vue'

// Props
interface Document {
  id: string | number
  title: string
  summary?: string
  content_type?: string
  language?: string
  document_date?: string
  is_in_effect?: boolean | null
  anonymous_url: string
  tenant_shortname?: string
  [key: string]: any
}

interface Props {
  results: Document[]
  viewMode: 'list' | 'compact'
  isLoading?: boolean
  hasQuery?: boolean
  totalHits?: number
  hasMoreResults?: boolean
  isLoadingMore?: boolean
  hasError?: boolean
  hasActiveFilters?: boolean
  searchQuery?: string
}

interface Emits {
  (e: 'loadMore'): void
  (e: 'clearFilters'): void
  (e: 'retry'): void
  (e: 'reportError'): void
}

const props = withDefaults(defineProps<Props>(), {
  results: () => [],
  viewMode: 'list',
  isLoading: false,
  hasQuery: false,
  totalHits: 0,
  hasMoreResults: false,
  isLoadingMore: false,
  hasError: false,
  hasActiveFilters: false,
  searchQuery: ''
})

const emit = defineEmits<Emits>()


// Computed properties for state management
const showNoResultsState = computed(() => {
  // Show no results when:
  // 1. Not loading AND
  // 2. No results AND
  // 3. User has performed a real search (not empty/wildcard) or applied filters
  const hasRealQuery = props.searchQuery && props.searchQuery.trim() !== '' && props.searchQuery.trim() !== '*' && props.searchQuery.trim().length >= 3
  const hasUserFilters = props.hasActiveFilters

  return !props.isLoading &&
    props.results.length === 0 &&
    (hasRealQuery || hasUserFilters)
})

const showShortQueryState = computed(() => {
  // Show short query state when:
  // 1. Not loading AND
  // 2. No results AND  
  // 3. Query exists but is too short (1-2 characters) AND
  // 4. No active filters
  const hasShortQuery = props.searchQuery && props.searchQuery.trim() !== '' && props.searchQuery.trim() !== '*' && props.searchQuery.trim().length < 3

  return !props.isLoading &&
    props.results.length === 0 &&
    hasShortQuery &&
    !props.hasActiveFilters
})

const getSkeletonCount = (): number => {
  if (props.viewMode === 'compact') {
    // For compact view: ~48px per item + spacing = ~52px per item
    // 600px / 52px ≈ 12 items
    return 12
  }
  // For list view: ~112px per item + spacing = ~128px per item  
  // 600px / 128px ≈ 5 items
  return 5
}

// Event handlers
const loadMore = () => {
  emit('loadMore')
}
</script>
