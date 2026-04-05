/**
 * Admin Search Service
 *
 * Handles building search parameters and filter strings for Typesense.
 * Provides utilities for converting between URL params and Typesense filters.
 */

import type {
  AdminSearchFilters,
  AdminFacet,
  AdminSearchResponse,
  CollectionFacetConfig,
  AdminCollection,
} from '../Types/AdminSearchTypes';

/**
 * Build a Typesense filter_by string from admin search filters
 */
export function buildFilterString(
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig,
): string {
  const filterParts: string[] = [];

  // Process each field in the facet config
  for (const fieldConfig of facetConfig.fields) {
    const value = filters[fieldConfig.field];

    if (value === undefined || value === null) {
      continue;
    }

    // Handle array values (multi-select)
    if (Array.isArray(value) && value.length > 0) {
      // For string arrays, wrap values in brackets
      if (typeof value[0] === 'string') {
        const escapedValues = value.map(v => escapeFilterValue(v as string));
        filterParts.push(`${fieldConfig.field}:[${escapedValues.join(',')}]`);
      }
      // For number arrays (like years)
      else if (typeof value[0] === 'number') {
        filterParts.push(`${fieldConfig.field}:[${value.join(',')}]`);
      }
    }
    // Handle boolean values
    else if (typeof value === 'boolean') {
      filterParts.push(`${fieldConfig.field}:${value}`);
    }
    // Handle single string values
    else if (typeof value === 'string' && value.length > 0) {
      filterParts.push(`${fieldConfig.field}:=${escapeFilterValue(value)}`);
    }
    // Handle single number values
    else if (typeof value === 'number') {
      filterParts.push(`${fieldConfig.field}:${value}`);
    }
  }

  return filterParts.join(' && ');
}

/**
 * Escape special characters in filter values
 * Must escape backslashes first, then backticks, to prevent injection attacks
 */
export function escapeFilterValue(value: string): string {
  // Typesense requires backtick escaping for special characters
  if (value.includes(',') || value.includes(':') || value.includes('`') || value.includes('\\')) {
    // Escape backslashes first, then backticks
    return `\`${value.replace(/\\/g, '\\\\').replace(/`/g, '\\`')}\``;
  }
  return value;
}

/**
 * Parse Typesense facet_counts response into AdminFacet array
 */
export function parseFacets(
  facetCounts: Array<{
    field_name: string;
    counts: Array<{ value: string; count: number }>;
  }>,
  facetConfig: CollectionFacetConfig,
  currentFilters: AdminSearchFilters,
): AdminFacet[] {
  return facetCounts.map((facetCount) => {
    const fieldConfig = facetConfig.fields.find(
      f => f.field === facetCount.field_name,
    );

    // Get selected values for this field
    const selectedValues = getSelectedValues(
      currentFilters[facetCount.field_name],
    );

    return {
      field: facetCount.field_name,
      label: fieldConfig?.label || facetCount.field_name,
      type: fieldConfig?.type || 'checkbox',
      icon: fieldConfig?.icon,
      values: facetCount.counts.map(count => ({
        value: count.value,
        count: count.count,
        isSelected: selectedValues.includes(count.value),
      })),
    };
  });
}

/**
 * Get selected values from a filter value (handles arrays and singles)
 */
function getSelectedValues(value: unknown): string[] {
  if (Array.isArray(value)) {
    return value.map(v => String(v));
  }
  if (value !== undefined && value !== null) {
    return [String(value)];
  }
  return [];
}

/**
 * Build URL search params from filters
 */
export function filtersToUrlParams(
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig,
): URLSearchParams {
  const params = new URLSearchParams();

  // Add query if present
  if (filters.query && filters.query !== '*') {
    params.set('q', filters.query);
  }

  // Add each filter field
  for (const fieldConfig of facetConfig.fields) {
    const value = filters[fieldConfig.field];

    if (value === undefined || value === null) {
      continue;
    }

    // Handle arrays
    if (Array.isArray(value) && value.length > 0) {
      params.set(fieldConfig.field, value.join(','));
    }
    // Handle booleans
    else if (typeof value === 'boolean') {
      params.set(fieldConfig.field, String(value));
    }
    // Handle single values
    else if (typeof value === 'string' || typeof value === 'number') {
      params.set(fieldConfig.field, String(value));
    }
  }

  return params;
}

/**
 * Parse URL search params into filters
 */
export function urlParamsToFilters(
  params: URLSearchParams,
  facetConfig: CollectionFacetConfig,
): AdminSearchFilters {
  const filters: AdminSearchFilters = {
    query: params.get('q') || '',
  };

  for (const fieldConfig of facetConfig.fields) {
    const value = params.get(fieldConfig.field);

    if (!value) {
      continue;
    }

    // Parse based on expected type
    if (fieldConfig.type === 'checkbox' || fieldConfig.type === 'year-pills') {
      // Multi-value fields - split by comma
      const values = value.split(',').filter(Boolean);

      // Convert to numbers if it's a year field
      if (fieldConfig.field.includes('year')) {
        filters[fieldConfig.field] = values.map(Number).filter(n => !isNaN(n));
      }
      else {
        filters[fieldConfig.field] = values;
      }
    }
    else if (fieldConfig.type === 'radio') {
      // Single value field
      filters[fieldConfig.field] = value;
    }
  }

  return filters;
}

/**
 * Count active filters (excluding query)
 */
export function countActiveFilters(
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig,
): number {
  let count = 0;

  for (const fieldConfig of facetConfig.fields) {
    const value = filters[fieldConfig.field];

    if (value === undefined || value === null) {
      continue;
    }

    if (Array.isArray(value)) {
      count += value.length;
    }
    else if (typeof value === 'boolean' && value === true) {
      count += 1;
    }
    else if (typeof value === 'string' && value.length > 0) {
      count += 1;
    }
    else if (typeof value === 'number') {
      count += 1;
    }
  }

  return count;
}

/**
 * Check if any filters are active (excluding query)
 */
export function hasActiveFilters(
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig,
): boolean {
  return countActiveFilters(filters, facetConfig) > 0;
}

/**
 * Clear all filters except query
 */
export function clearFilters(
  filters: AdminSearchFilters,
  facetConfig: CollectionFacetConfig,
): AdminSearchFilters {
  const cleared: AdminSearchFilters = {
    query: filters.query,
  };

  // Reset each field to its default (undefined)
  for (const fieldConfig of facetConfig.fields) {
    cleared[fieldConfig.field] = undefined;
  }

  return cleared;
}

/**
 * Toggle a value in a multi-select filter
 */
export function toggleFilterValue(
  currentValue: string[] | undefined,
  valueToToggle: string,
): string[] {
  const values = currentValue || [];

  if (values.includes(valueToToggle)) {
    return values.filter(v => v !== valueToToggle);
  }

  return [...values, valueToToggle];
}

/**
 * Toggle a numeric value in a multi-select filter (for years)
 */
export function toggleNumericFilterValue(
  currentValue: number[] | undefined,
  valueToToggle: number,
): number[] {
  const values = currentValue || [];

  if (values.includes(valueToToggle)) {
    return values.filter(v => v !== valueToToggle);
  }

  return [...values, valueToToggle];
}

/**
 * Validate that search results match expected collection
 */
export function validateSearchResults<T>(
  results: unknown[],
  collection: AdminCollection,
): T[] {
  // Basic validation - just ensure it's an array
  if (!Array.isArray(results)) {
    console.warn(`Invalid search results for ${collection}`);
    return [];
  }

  return results as T[];
}

/**
 * Format a Typesense timestamp to human-readable date
 */
export function formatTimestamp(timestamp: number, locale = 'lt-LT'): string {
  if (!timestamp) return '';

  const date = new Date(timestamp * 1000);
  return date.toLocaleDateString(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

/**
 * Format a Typesense timestamp to ISO date string
 */
export function formatTimestampISO(timestamp: number): string {
  if (!timestamp) return '';

  const date = new Date(timestamp * 1000);
  return date.toISOString().split('T')[0];
}

/**
 * Extract unique years from search results for year filter
 */
export function extractYearsFromResults<T extends { year?: number }>(
  results: T[],
): number[] {
  const years = new Set<number>();

  for (const result of results) {
    if (result.year) {
      years.add(result.year);
    }
  }

  return Array.from(years).sort((a, b) => b - a); // Descending
}

/**
 * Generate a sort_by string for Typesense
 */
export function buildSortString(sortBy: string, direction: 'asc' | 'desc' = 'desc'): string {
  return `${sortBy}:${direction}`;
}

/**
 * Parse sort param from URL
 */
export function parseSortParam(sortParam: string | null): { field: string; direction: 'asc' | 'desc' } | null {
  if (!sortParam) return null;

  const [field, direction] = sortParam.split(':');
  if (!field) return null;

  return {
    field,
    direction: direction === 'asc' ? 'asc' : 'desc',
  };
}
