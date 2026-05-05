/**
 * FacetMerger - Unified facet merging utility
 *
 * Handles merging initial facets (all available options) with current search facets
 * (filtered results). This ensures users can see all filter options even when current
 * search has limited results.
 *
 * Used by both admin and public search implementations.
 */

import type { BaseFacet, FacetValue, BaseSearchFilters } from '../types';

/**
 * Selection map for determining which values are selected
 */
export type SelectionMap = Record<string, (string | number)[]>;

/**
 * Field to filter key mapping function
 */
export type FilterKeyMapper<TFilters extends BaseSearchFilters> = (
  field: string,
  filters: TFilters,
) => (string | number)[];

/**
 * FacetMerger class - provides static methods for merging facets
 */
export class FacetMerger {
  /**
   * Generic facet merging with explicit selection map
   *
   * Use this when you have a custom mapping between facet fields and filter keys.
   *
   * @param initialFacets - All available facet values (from initial load)
   * @param currentFacets - Current facet values (from filtered search)
   * @param selectedValuesByField - Map of field name to selected values
   * @returns Merged facets with updated counts and selection states
   */
  static mergeFacetsWithSelectionMap<T extends BaseFacet>(
    initialFacets: T[],
    currentFacets: T[],
    selectedValuesByField: SelectionMap,
  ): T[] {
    // If no initial facets, return current facets as-is
    if (initialFacets.length === 0) {
      return currentFacets;
    }

    return initialFacets.map((initialFacet) => {
      const currentFacet = currentFacets.find(f => f.field === initialFacet.field);

      // Create map of current counts for quick lookup
      const currentValueMap = new Map<string, number>();
      if (currentFacet) {
        currentFacet.values.forEach((value) => {
          currentValueMap.set(value.value, value.count);
        });
      }

      // Get selected values for this field (convert to strings for comparison)
      const selectedValues = (selectedValuesByField[initialFacet.field] || [])
        .map(v => String(v));

      // Merge values: show ALL initial values with updated counts from current search
      const mergedValues: FacetValue[] = initialFacet.values.map(initialValue => ({
        ...initialValue,
        count: currentValueMap.get(initialValue.value) || 0,
        isSelected: selectedValues.includes(initialValue.value),
      }));

      // Add any new values from current search that weren't in initial facets
      if (currentFacet) {
        currentFacet.values.forEach((currentValue) => {
          const existsInInitial = initialFacet.values.some(
            iv => iv.value === currentValue.value,
          );
          if (!existsInInitial) {
            mergedValues.push({
              ...currentValue,
              isSelected: selectedValues.includes(currentValue.value),
            });
          }
        });
      }

      // Sort: selected items first, then by count, then alphabetically
      mergedValues.sort((a, b) => {
        const aSelected = a.isSelected ?? false;
        const bSelected = b.isSelected ?? false;

        if (aSelected && !bSelected) return -1;
        if (!aSelected && bSelected) return 1;
        if (a.count !== b.count) return b.count - a.count;
        return a.value.localeCompare(b.value);
      });

      return {
        ...initialFacet,
        values: mergedValues,
      } as T;
    });
  }

  /**
   * Merge facets using a filter key mapper function
   *
   * This is more flexible when filter key names don't match facet field names.
   *
   * @param initialFacets - All available facet values
   * @param currentFacets - Current facet values from filtered search
   * @param filters - Current filter values
   * @param getSelectedValues - Function to extract selected values for a field
   * @returns Merged facets
   */
  static mergeFacets<TFilters extends BaseSearchFilters, T extends BaseFacet>(
    initialFacets: T[],
    currentFacets: T[],
    filters: TFilters,
    getSelectedValues: FilterKeyMapper<TFilters>,
  ): T[] {
    const selectionMap: SelectionMap = {};

    for (const facet of initialFacets) {
      selectionMap[facet.field] = getSelectedValues(facet.field, filters);
    }

    return this.mergeFacetsWithSelectionMap(initialFacets, currentFacets, selectionMap);
  }

  /**
   * Create a default filter key mapper based on common conventions
   *
   * Maps common facet fields to their filter key equivalents:
   * - tenant_shortname -> tenants
   * - content_type -> contentTypes
   * - language -> languages
   * - year -> years
   */
  static createDefaultMapper<TFilters extends BaseSearchFilters>(): FilterKeyMapper<TFilters> {
    const fieldMappings: Record<string, string> = {
      tenant_shortname: 'tenants',
      content_type: 'contentTypes',
      language: 'languages',
      year: 'years',
      institution_type_title: 'institutionTypes',
      student_success_rate: 'successRateRanges',
    };

    return (field: string, filters: TFilters): (string | number)[] => {
      const filterKey = fieldMappings[field] || field;

      const value = filters[filterKey];
      if (Array.isArray(value)) {
        return value;
      }
      if (value !== undefined && value !== null && value !== '') {
        return [value as string | number];
      }
      return [];
    };
  }

  /**
   * Convenience method for simple cases where facet field matches filter key
   */
  static mergeSimple<T extends BaseFacet>(
    initialFacets: T[],
    currentFacets: T[],
    filters: BaseSearchFilters,
  ): T[] {
    return this.mergeFacets(initialFacets, currentFacets, filters, this.createDefaultMapper());
  }
}
