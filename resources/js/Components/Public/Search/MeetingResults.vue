<template>
  <div class="search-results-container transition-all duration-300 ease-out">
    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-2 min-h-[60vh] sm:min-h-[600px]">
      <MeetingResultsSkeleton v-for="i in 6" :key="i" view-mode="compact" />
    </div>

    <!-- Results -->
    <div v-else-if="results.length > 0" class="space-y-4 min-h-[60vh] sm:min-h-[600px]">
      <!-- Compact View - Single column compact rows -->
      <div class="space-y-2">
        <TransitionGroup name="meeting-compact" tag="div" class="space-y-2" appear>
          <MeetingCompactListItem v-for="meeting in results" :key="meeting.id" :meeting />
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
          {{ $t('search.no_meetings_found') }}
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
        <!-- Empty State (when no query and no results) -->
    <div v-else-if="showEmptyState" class="text-center py-12">
      <div class="max-w-md mx-auto">
        <Search class="w-14 h-14 mx-auto text-muted-foreground mb-5" />
        <h3 class="text-lg font-semibold text-foreground mb-3">
          {{ $t('search.start_searching_meetings') }}
        </h3>
        <p class="text-muted-foreground mb-6 leading-relaxed">
          {{ $t('search.meeting_search_description') }}
        </p>
        <p class="text-sm text-muted-foreground">
          {{ $t('search.or_browse_all') }} <button class="text-primary hover:underline font-medium" @click="$emit('clearFilters')">
            {{ $t('search.browse_all_meetings') }}
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
import MeetingCompactListItem from './MeetingCompactListItem.vue'
import MeetingResultsSkeleton from './MeetingResultsSkeleton.vue'

// Props
interface Meeting {
  id: string | number
  title?: string
  start_time: number // Unix timestamp in seconds
  completion_status: string
  institution_id?: string | number
  institution_name_lt?: string
  institution_name_en?: string
  tenant_shortname?: string
  agenda_items_count?: number
  total_agenda_items?: number
  positive_outcomes?: number
  negative_outcomes?: number
  neutral_outcomes?: number
  student_success_rate?: number
  [key: string]: any
}

interface Props {
  results: Meeting[]
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
  const hasRealQuery = props.searchQuery && props.searchQuery.trim() !== '' && props.searchQuery.trim() !== '*'
  const hasUserFilters = props.hasActiveFilters

  return !props.isLoading &&
    props.results.length === 0 &&
    (hasRealQuery || hasUserFilters)
})

const showEmptyState = computed(() => {
  // Show empty state when:
  // 1. Not loading AND
  // 2. No results AND
  // 3. No query AND
  // 4. No active filters
  return !props.isLoading &&
    props.results.length === 0 &&
    (!props.searchQuery || props.searchQuery.trim() === '' || props.searchQuery.trim() === '*') &&
    !props.hasActiveFilters
})

// Event handlers
const loadMore = () => {
  emit('loadMore')
}
</script>

<style scoped>
/* Compact transition animations */
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
