<template>
  <SearchResults
    :is-loading="isSearching"
    :has-results
    :has-searched
    :has-active-filters
    :error="error ? { message: error.message, userMessage: error.userMessage, retryable: error.retryable } : undefined"
    :empty-message
    :layout
    :grid-cols
    :skeleton-count="6"
    no-results-title-key="Rezultatų nerasta"
    no-results-description-key="Pabandykite pakeisti paieškos frazę arba filtrus"
    empty-state-title-key="Pradėkite paiešką"
    empty-state-description-key="Įveskite paieškos frazę arba naudokite filtrus"
    @retry="$emit('retry')"
    @clear-filters="$emit('clearFilters')"
  >
    <template #skeleton>
      <div
        v-for="i in 6"
        :key="i"
        class="border rounded-lg p-4 space-y-3"
      >
        <div class="h-5 bg-muted animate-pulse rounded w-3/4" />
        <div class="h-4 bg-muted/50 animate-pulse rounded w-1/2" />
        <div class="flex gap-2">
          <div class="h-6 bg-muted/30 animate-pulse rounded-full w-20" />
          <div class="h-6 bg-muted/30 animate-pulse rounded-full w-24" />
        </div>
      </div>
    </template>

    <slot />
  </SearchResults>
</template>

<script setup lang="ts">
import type { AdminSearchError } from '../Types/AdminSearchTypes';

import { SearchResults } from '@/Components/Shared/Search';

interface Props {
  isSearching: boolean;
  hasResults: boolean;
  hasSearched: boolean;
  hasActiveFilters?: boolean;
  error: AdminSearchError | null;
  emptyMessage?: string;
  layout?: 'grid' | 'list';
  gridCols?: 1 | 2 | 3;
}

interface Emits {
  (e: 'retry'): void;
  (e: 'clearFilters'): void;
}

withDefaults(defineProps<Props>(), {
  hasActiveFilters: false,
  emptyMessage: '',
  layout: 'grid',
  gridCols: 2,
});

defineEmits<Emits>();
</script>
