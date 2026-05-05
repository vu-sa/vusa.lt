/**
 * FilterUtils - Unified filter manipulation utilities
 *
 * Provides common utilities for working with search filters:
 * - Toggling array values
 * - Counting active filters
 * - Checking for active filters
 * - Clearing filters
 *
 * Used by both admin and public search implementations.
 */

import type { BaseSearchFilters, FacetConfig, DateRangeFilter } from '../types';

/**
 * FilterUtils class - provides static methods for filter manipulation
 */
export class FilterUtils {
  /**
   * Toggle a value in an array (add if not present, remove if present)
   *
   * @param values - Current array of values
   * @param value - Value to toggle
   * @returns New array with value toggled
   */
  static toggleArrayValue<T>(values: T[], value: T): T[] {
    const current = [...values];
    const index = current.indexOf(value);

    if (index >= 0) {
      current.splice(index, 1);
    }
    else {
      current.push(value);
    }

    return current;
  }

  /**
   * Toggle a string value in an optional array
   */
  static toggleStringValue(values: string[] | undefined, value: string): string[] {
    return this.toggleArrayValue(values || [], value);
  }

  /**
   * Toggle a numeric value in an optional array
   */
  static toggleNumericValue(values: number[] | undefined, value: number): number[] {
    return this.toggleArrayValue(values || [], value);
  }

  /**
   * Check if a date range has any active values
   */
  static hasActiveDateRange(dateRange: DateRangeFilter | undefined): boolean {
    if (!dateRange) return false;
    return !!(dateRange.preset || dateRange.from || dateRange.to);
  }

  /**
   * Check if filters have any active values (excluding query)
   *
   * Works with any filter object by checking array lengths and truthy values.
   */
  static hasActiveFilters<T extends BaseSearchFilters>(
    filters: T,
    excludeKeys: string[] = ['query'],
  ): boolean {
    for (const [key, value] of Object.entries(filters)) {
      if (excludeKeys.includes(key)) continue;

      if (Array.isArray(value) && value.length > 0) {
        return true;
      }

      if (key === 'dateRange' && this.hasActiveDateRange(value as DateRangeFilter)) {
        return true;
      }

      if (typeof value === 'boolean' && value === true) {
        return true;
      }

      if (typeof value === 'string' && value.length > 0) {
        return true;
      }

      if (typeof value === 'number') {
        return true;
      }
    }

    return false;
  }

  /**
   * Count the number of active filter values (excluding query)
   *
   * For arrays, counts the number of items.
   * For booleans/strings/numbers, counts as 1 if active.
   */
  static countActiveFilters<T extends BaseSearchFilters>(
    filters: T,
    excludeKeys: string[] = ['query'],
  ): number {
    let count = 0;

    for (const [key, value] of Object.entries(filters)) {
      if (excludeKeys.includes(key)) continue;

      if (Array.isArray(value)) {
        count += value.length;
      }
      else if (key === 'dateRange' && this.hasActiveDateRange(value as DateRangeFilter)) {
        count += 1;
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
   * Count active filters per category (for UI display)
   */
  static countActiveFiltersPerCategory<T extends BaseSearchFilters>(
    filters: T,
    excludeKeys: string[] = ['query'],
  ): number {
    let count = 0;

    for (const [key, value] of Object.entries(filters)) {
      if (excludeKeys.includes(key)) continue;

      if (Array.isArray(value) && value.length > 0) {
        count += 1; // Count the category, not individual values
      }
      else if (key === 'dateRange' && this.hasActiveDateRange(value as DateRangeFilter)) {
        count += 1;
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
   * Get a summary of active filters (for display)
   */
  static getFilterSummary<T extends BaseSearchFilters>(
    filters: T,
    labels: Record<string, string> = {},
  ): string[] {
    const summary: string[] = [];

    for (const [key, value] of Object.entries(filters)) {
      if (key === 'query') continue;

      const label = labels[key] || key;

      if (Array.isArray(value) && value.length > 0) {
        summary.push(`${value.length} ${label}`);
      }
      else if (key === 'dateRange' && this.hasActiveDateRange(value as DateRangeFilter)) {
        summary.push(label);
      }
    }

    return summary;
  }

  /**
   * Clear all filters except query, returning a new filter object
   */
  static clearFilters<T extends BaseSearchFilters>(
    currentFilters: T,
    defaultFilters: T,
  ): T {
    return {
      ...defaultFilters,
      query: currentFilters.query,
    };
  }

  /**
   * Clear filters using facet config (for admin search)
   */
  static clearFiltersWithConfig<T extends BaseSearchFilters>(
    filters: T,
    facetConfig: FacetConfig,
  ): T {
    const cleared = { query: filters.query } as T;

    for (const fieldConfig of facetConfig.fields) {
      (cleared as Record<string, unknown>)[fieldConfig.field] = undefined;
    }

    return cleared;
  }

  /**
   * Set a filter value, handling both array and single values
   */
  static setFilter<T extends BaseSearchFilters, K extends keyof T>(
    filters: T,
    key: K,
    value: T[K],
  ): T {
    return {
      ...filters,
      [key]: value,
    };
  }

  /**
   * Build URL search params from filters
   */
  static filtersToUrlParams<T extends BaseSearchFilters>(
    filters: T,
    facetConfig?: FacetConfig,
  ): URLSearchParams {
    const params = new URLSearchParams();

    // Add query if present
    if (filters.query && filters.query !== '*') {
      params.set('q', filters.query);
    }

    // Add each filter field
    for (const [key, value] of Object.entries(filters)) {
      if (key === 'query') continue;

      if (value === undefined || value === null) continue;

      // Handle arrays
      if (Array.isArray(value) && value.length > 0) {
        params.set(key, value.join(','));
      }
      // Handle booleans
      else if (typeof value === 'boolean') {
        params.set(key, String(value));
      }
      // Handle single values
      else if (typeof value === 'string' || typeof value === 'number') {
        params.set(key, String(value));
      }
      // Handle date range
      else if (key === 'dateRange' && typeof value === 'object') {
        const dateRange = value as DateRangeFilter;
        if (dateRange.preset) {
          params.set('datePreset', dateRange.preset);
        }
        if (dateRange.from) {
          params.set('dateFrom', String(Math.floor(dateRange.from.getTime() / 1000)));
        }
        if (dateRange.to) {
          params.set('dateTo', String(Math.floor(dateRange.to.getTime() / 1000)));
        }
      }
    }

    return params;
  }

  /**
   * Parse URL search params into filters
   */
  static urlParamsToFilters<T extends BaseSearchFilters>(
    params: URLSearchParams,
    defaultFilters: T,
    facetConfig?: FacetConfig,
  ): T {
    const filters = { ...defaultFilters };

    // Parse query
    const query = params.get('q');
    if (query) {
      filters.query = query;
    }

    // Parse facet fields if config provided
    if (facetConfig) {
      for (const fieldConfig of facetConfig.fields) {
        const value = params.get(fieldConfig.field);
        if (!value) continue;

        if (fieldConfig.type === 'checkbox' || fieldConfig.type === 'year-pills') {
          const values = value.split(',').filter(Boolean);

          // Convert to numbers if it's a year field
          if (fieldConfig.field.includes('year')) {
            (filters as Record<string, unknown>)[fieldConfig.field] = values
              .map(Number)
              .filter(n => !isNaN(n));
          }
          else {
            (filters as Record<string, unknown>)[fieldConfig.field] = values;
          }
        }
        else if (fieldConfig.type === 'radio') {
          (filters as Record<string, unknown>)[fieldConfig.field] = value;
        }
      }
    }

    // Parse date range params
    const datePreset = params.get('datePreset');
    const dateFrom = params.get('dateFrom');
    const dateTo = params.get('dateTo');

    if (datePreset || dateFrom || dateTo) {
      const dateRange: DateRangeFilter = {};
      if (datePreset) {
        dateRange.preset = datePreset as DateRangeFilter['preset'];
      }
      if (dateFrom) {
        dateRange.from = new Date(Number(dateFrom) * 1000);
      }
      if (dateTo) {
        dateRange.to = new Date(Number(dateTo) * 1000);
      }
      (filters as Record<string, unknown>).dateRange = dateRange;
    }

    return filters;
  }
}
