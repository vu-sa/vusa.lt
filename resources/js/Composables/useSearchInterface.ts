/**
 * useSearchInterface - Shared search interface state and handlers
 *
 * Encapsulates common UI logic used by Document, Meeting, and Institution
 * search interfaces to eliminate duplication and ensure consistent behavior.
 */

import { ref, computed, watch, type Ref, type ComputedRef } from 'vue';
import { debounce } from 'lodash-es';

import { FilterUtils } from '@/Shared/Search/services/FilterUtils';
import type { BaseSearchFilters } from '@/Shared/Search/types';

// ============================================================================
// Types
// ============================================================================

/**
 * Minimum controller interface required by useSearchInterface.
 * All entity-specific search controllers satisfy this.
 */
export interface SearchInterfaceController<TFilters extends BaseSearchFilters> {
  search: (query: string, immediate?: boolean) => void;
  clearFilters: () => void;
  cancelPendingSearch?: () => void;
  removeRecentSearch?: (search: string) => void;
  clearRecentSearches?: () => void;
  filters: { value: TFilters };
  searchState: { value: { query: string } };
}

export interface UseSearchInterfaceOptions<TFilters extends BaseSearchFilters> {
  searchController: SearchInterfaceController<TFilters>;
  debounceDelay?: number;
}

export interface UseSearchInterfaceReturn {
  inputQuery: Ref<string>;
  typeToSearch: Ref<boolean>;
  hasActiveFilters: ComputedRef<boolean>;
  activeFilterCount: ComputedRef<number>;
  handleQueryUpdate: (query: string) => void;
  handleSearch: (query?: string) => void;
  handleSelectRecent: (search: string) => void;
  handleClear: () => void;
  handleTypeToSearchUpdate: (value: boolean) => void;
  handleRemoveRecent: (search: string) => void;
  handleClearAllHistory: () => void;
}

// ============================================================================
// Composable
// ============================================================================

export function useSearchInterface<TFilters extends BaseSearchFilters>(
  options: UseSearchInterfaceOptions<TFilters>,
): UseSearchInterfaceReturn {
  const { searchController, debounceDelay = 200 } = options;

  // Local UI state
  const inputQuery = ref('');
  const typeToSearch = ref(true); // Enable auto-search by default

  // Computed filter state (delegates to shared FilterUtils)
  const hasActiveFilters = computed(() =>
    FilterUtils.hasActiveFilters(searchController.filters.value, ['query']),
  );

  const activeFilterCount = computed(() =>
    FilterUtils.countActiveFiltersPerCategory(searchController.filters.value, ['query']),
  );

  // Debounced auto-search when typing
  const debouncedAutoSearch = debounce((q: string) => {
    searchController.search(q);
  }, debounceDelay);

  // --------------------------------------------------------------------------
  // Event handlers
  // --------------------------------------------------------------------------

  const handleQueryUpdate = (query: string) => {
    inputQuery.value = query;

    if (typeToSearch.value && query.trim() !== '') {
      debouncedAutoSearch(query);
    }
    else {
      debouncedAutoSearch.cancel();
      if (query.trim() === '' && typeToSearch.value) {
        searchController.search('*', true);
      }
    }
  };

  const handleSearch = (query?: string) => {
    const raw = (query ?? inputQuery.value).trim();
    const searchQuery = raw === '' ? '*' : raw;

    inputQuery.value = raw;
    debouncedAutoSearch.cancel();
    searchController.search(searchQuery, true);
  };

  const handleSelectRecent = (search: string) => {
    inputQuery.value = search;
    debouncedAutoSearch.cancel();
    searchController.search(search, true);
  };

  /**
   * Clear all filters and reset to the "show all" state.
   * This is the fix for the "Išvalyti filtrus" button not working
   * when filters yield zero results.
   */
  const handleClear = () => {
    inputQuery.value = '';
    debouncedAutoSearch.cancel();
    searchController.clearFilters();
    searchController.cancelPendingSearch?.();
    searchController.search('*', true);
  };

  const handleTypeToSearchUpdate = (value: boolean) => {
    typeToSearch.value = value;
  };

  const handleRemoveRecent = (search: string) => {
    searchController.removeRecentSearch?.(search);
  };

  const handleClearAllHistory = () => {
    searchController.clearRecentSearches?.();
  };

  // --------------------------------------------------------------------------
  // Watchers
  // --------------------------------------------------------------------------

  // Keep inputQuery in sync with controller query updates
  watch(
    () => searchController.searchState.value.query,
    (q) => {
      inputQuery.value = q === '*' ? '' : (q || '');
    },
  );

  return {
    inputQuery,
    typeToSearch,
    hasActiveFilters,
    activeFilterCount,
    handleQueryUpdate,
    handleSearch,
    handleSelectRecent,
    handleClear,
    handleTypeToSearchUpdate,
    handleRemoveRecent,
    handleClearAllHistory,
  };
}
