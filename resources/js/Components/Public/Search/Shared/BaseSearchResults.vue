<template>
  <div class="search-results-container transition-all duration-300 ease-out">
    <!-- Loading State -->
    <div v-if="isLoading" class="min-h-[60vh] sm:min-h-[600px]" :class="loadingContainerClass">
      <slot name="skeleton" :count="skeletonCount">
        <!-- Default skeleton fallback -->
        <div v-for="i in skeletonCount" :key="i" class="h-20 bg-muted/50 rounded-lg animate-pulse" />
      </slot>
    </div>

    <!-- Results -->
    <div v-else-if="results.length > 0" class="min-h-[60vh] sm:min-h-[600px]" :class="resultsContainerClass">
      <TransitionGroup :name="transitionName" :tag="transitionTag" :class="transitionClass" appear>
        <slot name="item" v-for="item in results" :key="getItemKey(item)" :item="item" />
      </TransitionGroup>

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

    <!-- Empty State for No Results (when search was attempted) -->
    <div v-else-if="showNoResultsState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <SearchX class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t(noResultsTitleKey) }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t(noResultsDescriptionKey) }}
        </p>
        <Button variant="outline" @click="emit('clearFilters')">
          <RotateCcw class="w-4 h-4 mr-2" />
          {{ $t('search.clear_filters_action') }}
        </Button>
      </div>
    </div>

    <!-- Empty State (when no query and no results) -->
    <div v-else-if="showEmptyState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <component :is="emptyStateIcon" class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t(emptyStateTitleKey) }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t(emptyStateDescriptionKey) }}
        </p>
        <p class="text-sm text-muted-foreground">
          {{ $t('search.or_browse_all') }} 
          <button class="text-primary hover:underline font-medium" @click="emit('clearFilters')">
            {{ $t(browseAllKey) }}
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
          <Button variant="outline" @click="emit('retry')">
            <RefreshCw class="w-4 h-4 mr-2" />
            {{ $t('search.try_again') }}
          </Button>
          <Button variant="ghost" @click="emit('reportError')">
            <Flag class="w-4 h-4 mr-2" />
            {{ $t('search.report_error') }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" generic="T extends { id: string | number }">
import { computed, type Component } from 'vue'
import { Button } from '@/Components/ui/button'
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

interface Props {
  results: T[]
  isLoading?: boolean
  hasQuery?: boolean
  searchQuery?: string
  totalHits?: number
  hasMoreResults?: boolean
  isLoadingMore?: boolean
  hasError?: boolean
  hasActiveFilters?: boolean
  
  // Skeleton configuration
  skeletonCount?: number
  
  // CSS class customization
  loadingContainerClass?: string
  resultsContainerClass?: string
  
  // Transition customization
  transitionName?: string
  transitionTag?: string
  transitionClass?: string
  
  // Translation keys for empty/no-results states
  noResultsTitleKey?: string
  noResultsDescriptionKey?: string
  emptyStateTitleKey?: string
  emptyStateDescriptionKey?: string
  browseAllKey?: string
  
  // Empty state icon
  emptyStateIcon?: Component
  
  // Item key getter (defaults to item.id)
  itemKeyField?: string
}

const props = withDefaults(defineProps<Props>(), {
  results: () => [],
  isLoading: false,
  hasQuery: false,
  searchQuery: '',
  totalHits: 0,
  hasMoreResults: false,
  isLoadingMore: false,
  hasError: false,
  hasActiveFilters: false,
  skeletonCount: 6,
  loadingContainerClass: 'space-y-4',
  resultsContainerClass: 'space-y-4',
  transitionName: 'list',
  transitionTag: 'div',
  transitionClass: 'space-y-3',
  noResultsTitleKey: 'search.no_results_found',
  noResultsDescriptionKey: 'search.no_results_criteria',
  emptyStateTitleKey: 'search.start_searching',
  emptyStateDescriptionKey: 'search.search_description',
  browseAllKey: 'search.browse_all',
  emptyStateIcon: () => Search,
  itemKeyField: 'id'
})

const emit = defineEmits<{
  loadMore: []
  clearFilters: []
  retry: []
  reportError: []
}>()

// Computed properties for state management
const showNoResultsState = computed(() => {
  const hasRealQuery = props.searchQuery && props.searchQuery.trim() !== '' && props.searchQuery.trim() !== '*'
  const hasUserFilters = props.hasActiveFilters

  return !props.isLoading &&
    props.results.length === 0 &&
    (hasRealQuery || hasUserFilters)
})

const showEmptyState = computed(() => {
  return !props.isLoading &&
    props.results.length === 0 &&
    (!props.searchQuery || props.searchQuery.trim() === '' || props.searchQuery.trim() === '*') &&
    !props.hasActiveFilters
})

// Get item key for TransitionGroup
const getItemKey = (item: T): string | number => {
  return item[props.itemKeyField as keyof T] as string | number
}
</script>

<style scoped>
/* Default list transition animations */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.list-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.list-move {
  transition: transform 0.3s ease;
}

/* Document list transitions */
.document-list-enter-active,
.document-list-leave-active {
  transition: all 0.3s ease;
}

.document-list-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.document-list-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Document compact transitions */
.document-compact-enter-active,
.document-compact-leave-active {
  transition: all 0.2s ease;
}

.document-compact-enter-from {
  opacity: 0;
  transform: translateX(-10px);
}

.document-compact-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

/* Institution list transitions */
.institution-list-enter-active,
.institution-list-leave-active {
  transition: all 0.3s ease;
}

.institution-list-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.institution-list-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.institution-list-move {
  transition: transform 0.3s ease;
}

/* Meeting compact transitions */
.meeting-compact-enter-active,
.meeting-compact-leave-active {
  transition: all 0.2s ease;
}

.meeting-compact-enter-from {
  opacity: 0;
  transform: translateX(-10px);
}

.meeting-compact-leave-to {
  opacity: 0;
  transform: translateX(10px);
}
</style>
