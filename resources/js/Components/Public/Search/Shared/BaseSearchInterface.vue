<template>
  <SearchErrorBoundary
    :error="searchError"
    :is-online
    :is-retrying="isSearching"
    :retry-count
    :max-retries
    @retry="emit('retry')"
    @clear-error="emit('clearError')"
  >
    <div class="w-full">
      <!-- Search Header -->
      <div class="my-3 sm:my-4 lg:my-6 px-2 sm:px-3 lg:px-4">
        <div class="text-center max-w-2xl mx-auto">
          <div class="flex items-center justify-center gap-3 mb-3 sm:mb-4">
            <SearchPageSwitcher :page />
          </div>
          <h1 class="text-2xl sm:text-4xl font-bold text-foreground mb-2 sm:mb-3">
            {{ $t(titleKey) }}
          </h1>
          <p class="text-base sm:text-lg text-muted-foreground px-2">
            {{ $t(descriptionKey) }}
          </p>
          <!-- Slot for additional content below subtitle -->
          <slot name="after-subtitle" />
        </div>
      </div>

      <!-- Search Input Slot -->
      <div class="mb-2">
        <slot name="search-input" />
      </div>

      <!-- Offline indicator -->
      <div
        v-if="!isOnline"
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
            <slot name="facet-sidebar" />
          </div>

          <!-- Results Area -->
          <div class="min-w-0">
            <!-- Results Header -->
            <div class="flex items-center justify-between mb-4 sm:mb-6 bg-muted/30 rounded-lg px-3 sm:px-4 py-2">
              <!-- Results Count -->
              <div class="text-xs sm:text-sm text-muted-foreground min-w-0 flex-1 pr-2">
                <template v-if="totalHits > 0">
                  <template v-if="searchQuery === '*' && !hasActiveFilters">
                    <span class="hidden sm:inline">{{ $t('search.showing_results') }} </span>
                    <strong class="text-foreground">{{ totalHits.toLocaleString() }}</strong>
                    <span class="hidden sm:inline"> {{ totalHits === 1 ?
                      $t(resultSingularKey) : $t(resultPluralKey) }}</span>
                    <span class="sm:hidden">{{ totalHits === 1 ? resultShortLabel : resultShortLabel }}</span>
                    <span v-if="showNewestFirst" class="hidden sm:inline">{{ $t('search.newest_first') }}</span>
                  </template>
                  <template v-else>
                    <span class="hidden sm:inline">{{ $t('search.found_results') }} </span>
                    <strong class="text-foreground">{{ totalHits.toLocaleString() }}</strong>
                    <span class="hidden sm:inline"> {{ totalHits === 1 ?
                      $t(resultSingularKey) : $t(resultPluralKey) }}</span>
                    <span class="sm:hidden">{{ totalHits === 1 ? resultShortLabel : resultShortLabel }}</span>
                    <template v-if="searchQuery && searchQuery !== '*'">
                      <span class="hidden sm:inline"> {{ $t('search.by_query') }} </span>
                      <span class="hidden sm:inline"><strong class="text-foreground">"{{ searchQuery }}"</strong></span>
                    </template>
                  </template>
                </template>
                <template v-else-if="searchQuery && searchQuery.length > 0 && !isSearching">
                  {{ $t(noResultsKey) }}
                </template>
                <template v-else-if="!searchQuery && !isSearching">
                  <span class="hidden sm:inline">{{ $t(browsePromptKey) }}</span>
                  <span class="sm:hidden">{{ $t(browsePromptMobileKey) }}</span>
                </template>
              </div>

              <!-- View Controls Slot -->
              <slot name="view-controls" />
            </div>

            <!-- Filter Tags (Mobile) -->
            <div v-if="hasActiveFilters" class="md:hidden mb-3 sm:mb-4">
              <slot name="mobile-filter-tags" />
            </div>

            <!-- Search Results Slot -->
            <slot name="results" />
          </div>
        </div>
      </div>
    </div>
  </SearchErrorBoundary>
</template>

<script setup lang="ts">
import { WifiOff } from 'lucide-vue-next';

import SearchErrorBoundary from '../SearchErrorBoundary.vue';
import SearchPageSwitcher from '../SearchPageSwitcher.vue';

interface Props {
  // Page identifier for SearchPageSwitcher
  page: 'documents' | 'contacts' | 'meetings';

  // Translation keys
  titleKey: string;
  descriptionKey: string;
  resultSingularKey: string;
  resultPluralKey: string;
  noResultsKey: string;
  browsePromptKey: string;
  browsePromptMobileKey?: string;

  // Short label for mobile (e.g., 'dok.', 'pos.')
  resultShortLabel?: string;

  // Show "newest first" text
  showNewestFirst?: boolean;

  // Search state
  searchQuery?: string;
  totalHits?: number;
  isSearching?: boolean;
  hasActiveFilters?: boolean;

  // Error boundary state
  searchError?: any;
  isOnline?: boolean;
  retryCount?: number;
  maxRetries?: number;
}

withDefaults(defineProps<Props>(), {
  browsePromptMobileKey: '',
  resultShortLabel: '',
  showNewestFirst: false,
  searchQuery: '',
  totalHits: 0,
  isSearching: false,
  hasActiveFilters: false,
  isOnline: true,
  retryCount: 0,
  maxRetries: 3,
});

const emit = defineEmits<{
  retry: [];
  clearError: [];
}>();
</script>
