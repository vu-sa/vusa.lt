import { describe, it, expect } from 'vitest'
import { FilterUtils } from '../services/FilterUtils'
import type { BaseSearchFilters, DateRangeFilter } from '../types'

// Test filter type
interface TestFilters extends BaseSearchFilters {
  tenants: string[]
  years: number[]
  contentTypes: string[]
  dateRange: DateRangeFilter
  isActive?: boolean
}

const createDefaultFilters = (): TestFilters => ({
  query: '',
  tenants: [],
  years: [],
  contentTypes: [],
  dateRange: {}
})

describe('FilterUtils', () => {
  describe('toggleArrayValue', () => {
    it('adds value when not present', () => {
      const result = FilterUtils.toggleArrayValue(['a', 'b'], 'c')
      expect(result).toEqual(['a', 'b', 'c'])
    })

    it('removes value when present', () => {
      const result = FilterUtils.toggleArrayValue(['a', 'b', 'c'], 'b')
      expect(result).toEqual(['a', 'c'])
    })

    it('works with numbers', () => {
      const result = FilterUtils.toggleArrayValue([1, 2, 3], 2)
      expect(result).toEqual([1, 3])
    })

    it('does not mutate original array', () => {
      const original = ['a', 'b']
      FilterUtils.toggleArrayValue(original, 'c')
      expect(original).toEqual(['a', 'b'])
    })
  })

  describe('toggleStringValue', () => {
    it('handles undefined input', () => {
      const result = FilterUtils.toggleStringValue(undefined, 'test')
      expect(result).toEqual(['test'])
    })

    it('toggles existing value', () => {
      const result = FilterUtils.toggleStringValue(['a', 'b'], 'a')
      expect(result).toEqual(['b'])
    })
  })

  describe('toggleNumericValue', () => {
    it('handles undefined input', () => {
      const result = FilterUtils.toggleNumericValue(undefined, 2024)
      expect(result).toEqual([2024])
    })

    it('toggles existing value', () => {
      const result = FilterUtils.toggleNumericValue([2023, 2024], 2023)
      expect(result).toEqual([2024])
    })
  })

  describe('hasActiveDateRange', () => {
    it('returns false for empty date range', () => {
      expect(FilterUtils.hasActiveDateRange({})).toBe(false)
      expect(FilterUtils.hasActiveDateRange(undefined)).toBe(false)
    })

    it('returns true when preset is set', () => {
      expect(FilterUtils.hasActiveDateRange({ preset: 'recent' })).toBe(true)
    })

    it('returns true when from date is set', () => {
      expect(FilterUtils.hasActiveDateRange({ from: new Date() })).toBe(true)
    })

    it('returns true when to date is set', () => {
      expect(FilterUtils.hasActiveDateRange({ to: new Date() })).toBe(true)
    })
  })

  describe('hasActiveFilters', () => {
    it('returns false for empty filters', () => {
      const filters = createDefaultFilters()
      expect(FilterUtils.hasActiveFilters(filters)).toBe(false)
    })

    it('returns true when array filter has values', () => {
      const filters = createDefaultFilters()
      filters.tenants = ['VU SA']
      expect(FilterUtils.hasActiveFilters(filters)).toBe(true)
    })

    it('returns true when boolean filter is true', () => {
      const filters = { ...createDefaultFilters(), isActive: true }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(true)
    })

    it('returns true when date range has values', () => {
      const filters = createDefaultFilters()
      filters.dateRange = { preset: 'recent' }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(true)
    })

    it('excludes specified keys', () => {
      const filters = { ...createDefaultFilters(), query: 'test' }
      expect(FilterUtils.hasActiveFilters(filters, ['query'])).toBe(false)
    })

    it('returns false for boolean false', () => {
      const filters = { ...createDefaultFilters(), isActive: false }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(false)
    })
  })

  describe('countActiveFilters', () => {
    it('returns 0 for empty filters', () => {
      const filters = createDefaultFilters()
      expect(FilterUtils.countActiveFilters(filters)).toBe(0)
    })

    it('counts array items', () => {
      const filters = createDefaultFilters()
      filters.tenants = ['VU SA', 'MIF']
      filters.years = [2024]
      expect(FilterUtils.countActiveFilters(filters)).toBe(3)
    })

    it('counts date range as 1', () => {
      const filters = createDefaultFilters()
      filters.dateRange = { from: new Date(), to: new Date() }
      expect(FilterUtils.countActiveFilters(filters)).toBe(1)
    })

    it('excludes query by default', () => {
      const filters = { ...createDefaultFilters(), query: 'test' }
      expect(FilterUtils.countActiveFilters(filters)).toBe(0)
    })
  })

  describe('countActiveFiltersPerCategory', () => {
    it('counts categories, not individual values', () => {
      const filters = createDefaultFilters()
      filters.tenants = ['VU SA', 'MIF', 'CHGF']
      filters.years = [2023, 2024]
      expect(FilterUtils.countActiveFiltersPerCategory(filters)).toBe(2)
    })
  })

  describe('getFilterSummary', () => {
    it('returns summary of active filters', () => {
      const filters = createDefaultFilters()
      filters.tenants = ['VU SA', 'MIF']
      filters.dateRange = { preset: 'recent' }

      const summary = FilterUtils.getFilterSummary(filters, {
        tenants: 'orgs',
        dateRange: 'date'
      })

      expect(summary).toContain('2 orgs')
      expect(summary).toContain('date')
    })
  })

  describe('clearFilters', () => {
    it('clears all filters except query', () => {
      const current: TestFilters = {
        query: 'search term',
        tenants: ['VU SA'],
        years: [2024],
        contentTypes: ['pdf'],
        dateRange: { preset: 'recent' }
      }
      const defaults = createDefaultFilters()

      const result = FilterUtils.clearFilters(current, defaults)

      expect(result.query).toBe('search term')
      expect(result.tenants).toEqual([])
      expect(result.years).toEqual([])
    })
  })

  describe('setFilter', () => {
    it('sets a filter value immutably', () => {
      const filters = createDefaultFilters()
      const result = FilterUtils.setFilter(filters, 'tenants', ['VU SA'])

      expect(result.tenants).toEqual(['VU SA'])
      expect(filters.tenants).toEqual([]) // Original unchanged
    })
  })

  describe('filtersToUrlParams', () => {
    it('converts filters to URL params', () => {
      const filters: TestFilters = {
        query: 'test search',
        tenants: ['VU SA', 'MIF'],
        years: [2024],
        contentTypes: [],
        dateRange: { preset: 'recent', from: new Date(1704067200000) }
      }

      const params = FilterUtils.filtersToUrlParams(filters)

      expect(params.get('q')).toBe('test search')
      expect(params.get('tenants')).toBe('VU SA,MIF')
      expect(params.get('years')).toBe('2024')
      expect(params.get('datePreset')).toBe('recent')
      expect(params.has('dateFrom')).toBe(true)
    })

    it('ignores empty arrays and wildcard query', () => {
      const filters: TestFilters = {
        query: '*',
        tenants: [],
        years: [],
        contentTypes: [],
        dateRange: {}
      }

      const params = FilterUtils.filtersToUrlParams(filters)

      expect(params.has('q')).toBe(false)
      expect(params.has('tenants')).toBe(false)
    })
  })

  describe('urlParamsToFilters', () => {
    it('parses URL params into filters', () => {
      const params = new URLSearchParams()
      params.set('q', 'test search')
      params.set('datePreset', '3months')
      params.set('dateFrom', '1704067200')

      const defaults = createDefaultFilters()
      const result = FilterUtils.urlParamsToFilters(params, defaults)

      expect(result.query).toBe('test search')
      expect(result.dateRange.preset).toBe('3months')
      expect(result.dateRange.from).toBeInstanceOf(Date)
    })
  })
})
