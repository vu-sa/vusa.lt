/**
 * Filter Pills Composable
 *
 * Shared presentation logic for active facet filter pills, extracted from the
 * per-collection search pages. Works on top of an AdminCollectionSearchController.
 */

import { ref, computed, watch } from 'vue';

import type { AdminCollectionSearchController } from './useAdminCollectionSearch';
import { getFacetValueLabel } from '../Config/collectionFacetConfig';

export function useFilterPills(controller: AdminCollectionSearchController) {
  // Track if user has performed a search (typed, filtered, or got results)
  const hasSearched = ref(false);

  /**
   * Active filter pills (excluding empty arrays and query).
   * Iterates over known facetConfig fields (whitelist) to avoid prototype pollution.
   */
  const activeFilterPills = computed<Record<string, (string | number)[]>>(() => {
    const pills: Record<string, (string | number)[]> = {};
    const filters = controller.filters.value;

    for (const fieldConfig of controller.facetConfig.fields) {
      const value = filters[fieldConfig.field];
      if (Array.isArray(value) && value.length > 0) {
        pills[fieldConfig.field] = value;
      }
      else if (typeof value === 'string' && value.length > 0) {
        pills[fieldConfig.field] = [value];
      }
    }

    return pills;
  });

  const getFieldLabel = (field: string): string => {
    const fieldConfig = controller.facetConfig.fields.find(f => f.field === field);
    return fieldConfig?.label || field;
  };

  const formatFilterValues = (field: string, values: (string | number)[]): string => {
    const formatted = values.map(v => getFacetValueLabel(field, String(v)));
    if (formatted.length <= 2) {
      return formatted.join(', ');
    }
    return `${formatted[0]}, ${formatted[1]} +${formatted.length - 2}`;
  };

  const clearFieldFilter = (field: string) => {
    controller.setFilter(field, undefined);
  };

  const handleQueryChange = (value: string) => {
    controller.search(value);
    hasSearched.value = true;
  };

  const handleSearch = () => {
    controller.search(controller.query.value, true);
    hasSearched.value = true;
  };

  // Mark as searched when results come back
  watch(
    () => controller.results.value,
    (results) => {
      if (results.length > 0 || controller.query.value || controller.hasActiveFilters.value) {
        hasSearched.value = true;
      }
    },
  );

  return {
    hasSearched,
    activeFilterPills,
    getFieldLabel,
    formatFilterValues,
    clearFieldFilter,
    handleQueryChange,
    handleSearch,
  };
}
