/**
 * Admin Facet Merger
 *
 * Merges initial facets (all available options) with current search facets
 * (results from current query). This ensures users can see all filter options
 * even when current search has limited results.
 */
import type { AdminFacet, AdminFacetValue, AdminSearchFilters, CollectionFacetConfig } from '../Types/AdminSearchTypes';

/**
 * Merges initial facets with current search facets.
 * Shows ALL initial values with updated counts from current search.
 */
export function mergeFacets(
  initialFacets: AdminFacet[],
  currentFacets: AdminFacet[],
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig,
): AdminFacet[] {
  const fields = new Set([...initialFacets, ...currentFacets].map(facet => facet.field));
  for (const field of facetConfig.fields) {
    const selected = getSelectedValuesForField(field.field, filters);
    if (selected.length && !fields.has(field.field)) {
      initialFacets = [...initialFacets, { field: field.field, label: field.label, type: field.type, values: selected.map(value => ({ value, count: null, isSelected: true })) }];
    }
  }
  if (initialFacets.length === 0) {
    initialFacets = currentFacets;
  }

  return initialFacets.map((initialFacet) => {
    // Find corresponding current facet from search results
    const currentFacet = currentFacets.find(f => f.field === initialFacet.field);

    // Create a map of current values for quick lookup
    const currentValueMap = new Map<string, number | null>();
    if (currentFacet) {
      currentFacet.values.forEach((value) => {
        currentValueMap.set(value.value, value.count);
      });
    }

    // Get selected values for this facet field
    const selectedValues = getSelectedValuesForField(initialFacet.field, filters);

    // Merge values: show ALL initial values with updated counts from current search
    const mergedValues = initialFacet.values.map((initialValue) => {
      const currentCount = currentValueMap.get(initialValue.value) ?? null;
      const isSelected = selectedValues.includes(initialValue.value);

      return {
        ...initialValue,
        count: currentCount,
        isSelected,
      };
    });

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
    for (const value of selectedValues) {
      if (!mergedValues.some(option => String(option.value) === String(value))) {
        mergedValues.push({ value: String(value), label: String(value), count: null, isSelected: true });
      }
    }
    sortFacetValues(mergedValues, selectedValues);

    return {
      ...initialFacet,
      values: mergedValues,
    };
  });
}

/**
 * Get selected values for a specific facet field from filters
 */
function getSelectedValuesForField(
  field: string,
  filters: AdminSearchFilters,
): string[] {
  const value = filters[field];

  if (Array.isArray(value)) {
    return value.map(String);
  }
  if (value !== undefined && value !== null) {
    return [String(value)];
  }
  return [];
}

/**
 * Sort facet values: selected first, then by count (descending), then alphabetically
 */
function sortFacetValues(
  values: AdminFacetValue[],
  selectedValues: string[],
): void {
  values.sort((a, b) => {
    const aSelected = selectedValues.includes(a.value);
    const bSelected = selectedValues.includes(b.value);

    // Selected items first
    if (aSelected && !bSelected) return -1;
    if (!aSelected && bSelected) return 1;

    // Then by count (descending)
    if (a.count !== b.count) return (b.count ?? -1) - (a.count ?? -1);

    // Finally alphabetically
    return a.value.localeCompare(b.value);
  });
}

/**
 * Sort facets by priority order from config
 */
export function sortFacetsByConfig(
  facets: AdminFacet[],
  facetConfig: CollectionFacetConfig,
): AdminFacet[] {
  const fieldOrder = facetConfig.fields.map(f => f.field);

  return [...facets].sort((a, b) => {
    const aIndex = fieldOrder.indexOf(a.field);
    const bIndex = fieldOrder.indexOf(b.field);

    // Unknown fields go to the end
    const aPriority = aIndex === -1 ? 999 : aIndex;
    const bPriority = bIndex === -1 ? 999 : bIndex;

    return aPriority - bPriority;
  });
}
