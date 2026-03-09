<template>
  <div class="search-results-container transition-all duration-300 ease-out">
    <!-- Loading State -->
    <div v-if="isLoading" class="min-h-[60vh] sm:min-h-[600px]" :class="loadingContainerClass">
      <slot name="skeleton" :count="skeletonCount">
        <!-- Default skeleton fallback -->
        <div
          v-for="i in skeletonCount"
          :key="i"
          class="h-20 bg-muted/50 rounded-lg animate-pulse mb-4"
        />
      </slot>
    </div>

    <!-- Results -->
    <div v-else-if="hasResults" class="min-h-[60vh] sm:min-h-[600px]" :class="resultsContainerClass">
      <!-- Results grid/list container -->
      <div :class="gridClasses">
        <slot />
      </div>

      <!-- Load More Button -->
      <div v-if="hasMoreResults" class="flex justify-center pt-6">
        <Button
          variant="outline"
          size="lg"
          :disabled="isLoadingMore"
          class="transition-all duration-200 hover:scale-105 focus:scale-105"
          @click="emit('loadMore')"
        >
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

    <!-- No Results State (when search was attempted) -->
    <div v-else-if="hasSearched && !hasResults" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <div
          class="inline-flex items-center justify-center size-16 rounded-full bg-muted mb-4"
        >
          <SearchX class="size-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t(noResultsTitleKey) }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ emptyMessage || $t(noResultsDescriptionKey) }}
        </p>
        <div v-if="hasActiveFilters" class="flex justify-center gap-2">
          <Button variant="outline" @click="emit('clearFilters')">
            <RotateCcw class="w-4 h-4 mr-2" />
            {{ $t('search.clear_filters_action') }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Initial State (no search yet) -->
    <div v-else-if="!hasSearched && !hasResults" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <div
          class="inline-flex items-center justify-center size-16 rounded-full bg-muted mb-4"
        >
          <component :is="emptyStateIcon" class="size-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t(emptyStateTitleKey) }}
        </h3>
        <p class="text-muted-foreground leading-relaxed">
          {{ $t(emptyStateDescriptionKey) }}
        </p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <div
          class="inline-flex items-center justify-center size-16 rounded-full bg-destructive/10 mb-4"
        >
          <AlertTriangle class="size-8 text-destructive" />
        </div>
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t('search.search_error') }}
        </h3>
        <p class="text-muted-foreground mb-6">
          {{ error.userMessage || $t('search.technical_error') }}
        </p>
        <div class="flex gap-2 justify-center">
          <Button v-if="error.retryable" variant="outline" @click="emit('retry')">
            <RefreshCw class="w-4 h-4 mr-2" />
            {{ $t('search.try_again') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Search,
  SearchX,
  RotateCcw,
  ChevronDown,
  Loader2,
  AlertTriangle,
  RefreshCw,
} from 'lucide-vue-next'

import { Button } from '@/Components/ui/button'

import type { SearchError } from './types'

interface Props {
  /** Whether search is in progress */
  isLoading?: boolean
  /** Whether there are results to display */
  hasResults: boolean
  /** Whether a search has been performed */
  hasSearched: boolean
  /** Whether there are active filters */
  hasActiveFilters?: boolean
  /** Whether more results can be loaded */
  hasMoreResults?: boolean
  /** Whether load more is in progress */
  isLoadingMore?: boolean
  /** Error state */
  error?: SearchError | null
  /** Custom empty message */
  emptyMessage?: string

  // Layout options
  /** Layout mode: 'grid' or 'list' */
  layout?: 'grid' | 'list'
  /** Number of grid columns (1, 2, or 3) */
  gridCols?: 1 | 2 | 3

  // Skeleton configuration
  skeletonCount?: number

  // CSS class customization
  loadingContainerClass?: string
  resultsContainerClass?: string

  // Translation keys
  noResultsTitleKey?: string
  noResultsDescriptionKey?: string
  emptyStateTitleKey?: string
  emptyStateDescriptionKey?: string

  // Empty state icon
  emptyStateIcon?: Component
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  hasActiveFilters: false,
  hasMoreResults: false,
  isLoadingMore: false,
  error: null,
  emptyMessage: '',
  layout: 'grid',
  gridCols: 2,
  skeletonCount: 6,
  loadingContainerClass: '',
  resultsContainerClass: '',
  noResultsTitleKey: 'search.no_results_title',
  noResultsDescriptionKey: 'search.no_results_description',
  emptyStateTitleKey: 'search.start_search_title',
  emptyStateDescriptionKey: 'search.start_search_description',
  emptyStateIcon: () => Search,
})

const emit = defineEmits<{
  loadMore: []
  clearFilters: []
  retry: []
}>()

// Compute grid classes based on layout
const gridClasses = computed(() => {
  if (props.layout === 'list') {
    return 'space-y-4'
  }

  const colsMap: Record<number, string> = {
    1: 'grid grid-cols-1 gap-4',
    2: 'grid grid-cols-1 md:grid-cols-2 gap-4',
    3: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4',
  }

  return colsMap[props.gridCols] || colsMap[2]
})
</script>
