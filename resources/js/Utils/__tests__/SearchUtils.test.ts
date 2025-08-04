import { describe, it, expect, beforeEach, vi } from 'vitest'
import {
  LanguageUtils,
  RecentSearchManager,
  FilterUtils,
  ErrorUtils,
  DateUtils,
  QueryUtils,
  PerformanceUtils
} from '../SearchUtils'
import type { DocumentSearchFilters, SearchError } from '@/types/DocumentSearchTypes'

// Mock the translation function
vi.mock('laravel-vue-i18n', () => ({
  trans: vi.fn((key: string) => {
    const translations: Record<string, string> = {
      'search.language_unknown': 'Not specified'
    }
    return translations[key] || key
  })
}))

describe('LanguageUtils', () => {
  describe('getLanguageFlag', () => {
    it('returns Lithuanian flag for Lithuanian language values', () => {
      expect(LanguageUtils.getLanguageFlag('Lietuvių')).toBe('https://hatscripts.github.io/circle-flags/flags/lt.svg')
      expect(LanguageUtils.getLanguageFlag('Lithuanian')).toBe('https://hatscripts.github.io/circle-flags/flags/lt.svg')
    })

    it('returns English flag for English language values', () => {
      expect(LanguageUtils.getLanguageFlag('Anglų')).toBe('https://hatscripts.github.io/circle-flags/flags/gb.svg')
      expect(LanguageUtils.getLanguageFlag('English')).toBe('https://hatscripts.github.io/circle-flags/flags/gb.svg')
    })

    it('returns empty string for unknown languages', () => {
      expect(LanguageUtils.getLanguageFlag('Unknown')).toBe('')
      expect(LanguageUtils.getLanguageFlag('Français')).toBe('')
      expect(LanguageUtils.getLanguageFlag('')).toBe('')
    })
  })

  describe('getLanguageDisplay', () => {
    it('returns LT for Lithuanian language values', () => {
      expect(LanguageUtils.getLanguageDisplay('Lietuvių')).toBe('LT')
      expect(LanguageUtils.getLanguageDisplay('Lithuanian')).toBe('LT')
    })

    it('returns EN for English language values', () => {
      expect(LanguageUtils.getLanguageDisplay('Anglų')).toBe('EN')
      expect(LanguageUtils.getLanguageDisplay('English')).toBe('EN')
    })

    it('returns translated "Not specified" for unknown languages', () => {
      expect(LanguageUtils.getLanguageDisplay('Unknown')).toBe('Not specified')
      expect(LanguageUtils.getLanguageDisplay('Français')).toBe('Not specified')
      expect(LanguageUtils.getLanguageDisplay('')).toBe('Not specified')
    })
  })

  describe('getLanguageCode', () => {
    it('returns language codes correctly', () => {
      expect(LanguageUtils.getLanguageCode('Lietuvių')).toBe('lt')
      expect(LanguageUtils.getLanguageCode('Lithuanian')).toBe('lt')
      expect(LanguageUtils.getLanguageCode('Anglų')).toBe('en')
      expect(LanguageUtils.getLanguageCode('English')).toBe('en')
      expect(LanguageUtils.getLanguageCode('Unknown')).toBe('unknown')
    })
  })
})

describe('RecentSearchManager', () => {
  describe('addToRecentSearches', () => {
    it('adds new search to the beginning of the list', () => {
      const recentSearches = ['old search']
      const result = RecentSearchManager.addToRecentSearches(recentSearches, 'new search')
      
      expect(result).toEqual(['new search', 'old search'])
    })

    it('removes duplicates and moves existing search to front', () => {
      const recentSearches = ['search1', 'search2', 'search3']
      const result = RecentSearchManager.addToRecentSearches(recentSearches, 'search2')
      
      expect(result).toEqual(['search2', 'search1', 'search3'])
    })

    it('ignores queries shorter than minimum length', () => {
      const recentSearches = ['existing']
      const result1 = RecentSearchManager.addToRecentSearches(recentSearches, 'ab')
      const result2 = RecentSearchManager.addToRecentSearches(recentSearches, '')
      
      expect(result1).toEqual(['existing'])
      expect(result2).toEqual(['existing'])
    })

    it('handles case-insensitive duplicates', () => {
      const recentSearches = ['Test Search']
      const result = RecentSearchManager.addToRecentSearches(recentSearches, 'test search')
      
      expect(result).toEqual(['test search'])
    })

    it('limits list to maximum length', () => {
      const recentSearches = Array.from({ length: 10 }, (_, i) => `search${i}`)
      const result = RecentSearchManager.addToRecentSearches(recentSearches, 'new search')
      
      expect(result).toHaveLength(10)
      expect(result[0]).toBe('new search')
      expect(result).not.toContain('search9') // Last item should be removed
    })

    it('trims whitespace from queries', () => {
      const recentSearches: string[] = []
      const result = RecentSearchManager.addToRecentSearches(recentSearches, '  test search  ')
      
      expect(result).toEqual(['test search'])
    })
  })

  describe('removeRecentSearch', () => {
    it('removes specific search from list', () => {
      const recentSearches = ['search1', 'search2', 'search3']
      const result = RecentSearchManager.removeRecentSearch(recentSearches, 'search2')
      
      expect(result).toEqual(['search1', 'search3'])
    })

    it('returns unchanged list if search not found', () => {
      const recentSearches = ['search1', 'search2']
      const result = RecentSearchManager.removeRecentSearch(recentSearches, 'not found')
      
      expect(result).toEqual(['search1', 'search2'])
    })

    it('handles empty list', () => {
      const result = RecentSearchManager.removeRecentSearch([], 'any search')
      expect(result).toEqual([])
    })
  })

  describe('clearRecentSearches', () => {
    it('returns empty array', () => {
      const result = RecentSearchManager.clearRecentSearches()
      expect(result).toEqual([])
    })
  })

  describe('isValidSearchQuery', () => {
    it('accepts queries with minimum length or more', () => {
      expect(RecentSearchManager.isValidSearchQuery('abc')).toBe(true)
      expect(RecentSearchManager.isValidSearchQuery('longer query')).toBe(true)
    })

    it('accepts wildcard queries', () => {
      expect(RecentSearchManager.isValidSearchQuery('*')).toBe(true)
      expect(RecentSearchManager.isValidSearchQuery(' * ')).toBe(true) // Should trim
    })

    it('rejects short queries', () => {
      expect(RecentSearchManager.isValidSearchQuery('ab')).toBe(false)
      expect(RecentSearchManager.isValidSearchQuery('')).toBe(false)
      expect(RecentSearchManager.isValidSearchQuery('  ')).toBe(false)
    })
  })
})

describe('FilterUtils', () => {
  let baseFilters: DocumentSearchFilters

  beforeEach(() => {
    baseFilters = {
      query: 'test',
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {} // Empty object should not trigger "data" in summary
    }
  })

  describe('hasActiveFilters', () => {
    it('returns false for empty filters', () => {
      expect(FilterUtils.hasActiveFilters(baseFilters)).toBe(false)
    })

    it('detects tenant filters', () => {
      const filters = { ...baseFilters, tenants: ['vu-sa'] }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(true)
    })

    it('detects content type filters', () => {
      const filters = { ...baseFilters, contentTypes: ['protocol'] }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(true)
    })

    it('detects language filters', () => {
      const filters = { ...baseFilters, languages: ['Lithuanian'] }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(true)
    })

    it('detects date range filters', () => {
      const filters1 = { ...baseFilters, dateRange: { preset: '3months' } }
      const filters2 = { ...baseFilters, dateRange: { from: new Date() } }
      const filters3 = { ...baseFilters, dateRange: { to: new Date() } }
      
      expect(FilterUtils.hasActiveFilters(filters1)).toBe(true)
      expect(FilterUtils.hasActiveFilters(filters2)).toBe(true)
      expect(FilterUtils.hasActiveFilters(filters3)).toBe(true)
    })

    it('ignores "recent" preset as it is default', () => {
      const filters = { ...baseFilters, dateRange: { preset: 'recent' } }
      expect(FilterUtils.hasActiveFilters(filters)).toBe(false)
    })
  })

  describe('countActiveFilters', () => {
    it('counts zero for empty filters', () => {
      expect(FilterUtils.countActiveFilters(baseFilters)).toBe(0)
    })

    it('counts each filter type once', () => {
      const filters: DocumentSearchFilters = {
        ...baseFilters,
        tenants: ['vu-sa', 'vu-mif'],
        contentTypes: ['protocol'],
        languages: ['Lithuanian', 'English'],
        dateRange: { preset: '3months' }
      }
      
      expect(FilterUtils.countActiveFilters(filters)).toBe(4)
    })

    it('counts date range as one filter regardless of from/to/preset', () => {
      const filters = {
        ...baseFilters,
        dateRange: { from: new Date(), to: new Date(), preset: 'custom' }
      }
      
      expect(FilterUtils.countActiveFilters(filters)).toBe(1)
    })
  })

  describe('getFilterSummary', () => {
    it('returns empty array for no active filters', () => {
      // Use a truly empty filter set
      const emptyFilters: DocumentSearchFilters = {
        query: 'test',
        tenants: [],
        contentTypes: [],
        languages: [],
        dateRange: {} // No preset, from, or to should mean no date filter
      }
      expect(FilterUtils.getFilterSummary(emptyFilters)).toEqual([])
    })

    it('creates summary for active filters', () => {
      const filters: DocumentSearchFilters = {
        ...baseFilters,
        tenants: ['vu-sa', 'vu-mif'],
        contentTypes: ['protocol'],
        languages: ['Lithuanian'],
        dateRange: { preset: '3months' }
      }
      
      const summary = FilterUtils.getFilterSummary(filters)
      expect(summary).toContain('2 org.')
      expect(summary).toContain('1 tipas')
      expect(summary).toContain('1 kalba')
      expect(summary).toContain('data')
    })
  })

  describe('clearFilters', () => {
    it('clears all filters but keeps query', () => {
      const filters: DocumentSearchFilters = {
        query: 'search term',
        tenants: ['vu-sa'],
        contentTypes: ['protocol'],
        languages: ['Lithuanian'],
        dateRange: { preset: '3months' }
      }
      
      const cleared = FilterUtils.clearFilters(filters.query)
      
      expect(cleared).toEqual({
        query: 'search term',
        tenants: [],
        contentTypes: [],
        languages: [],
        dateRange: {}
      })
    })
  })
})

describe('ErrorUtils', () => {
  describe('createSearchError', () => {
    it('creates error object with all properties', () => {
      const error = ErrorUtils.createSearchError(
        'network',
        'Connection failed',
        'Check your internet connection',
        'NET_ERR',
        true
      )
      
      expect(error).toMatchObject({
        type: 'network',
        message: 'Connection failed',
        userMessage: 'Check your internet connection',
        code: 'NET_ERR',
        retryable: true,
        timestamp: expect.any(Date)
      })
    })

    it('uses default values when not provided', () => {
      const error = ErrorUtils.createSearchError('client', 'Error', 'User message')
      
      expect(error.retryable).toBe(true)
      expect(error.code).toBeUndefined()
    })
  })

  describe('error type detection', () => {
    it('detects network errors', () => {
      const errors = [
        new Error('fetch failed'),
        new Error('network error occurred'),
        new Error('connection timeout')
      ]
      
      errors.forEach(error => {
        expect(ErrorUtils.isNetworkError(error)).toBe(true)
        expect(ErrorUtils.getErrorType(error)).toBe('network')
      })
    })

    it('detects timeout errors', () => {
      const error = new Error('Request timeout')
      expect(ErrorUtils.isTimeoutError(error)).toBe(true)
      expect(ErrorUtils.getErrorType(error)).toBe('timeout')
    })

    it('detects abort errors', () => {
      const error = new Error('Aborted')
      error.name = 'AbortError'
      expect(ErrorUtils.isAbortError(error)).toBe(true)
      expect(ErrorUtils.getErrorType(error)).toBe('abort')
    })

    it('detects client errors', () => {
      const errors = [
        new Error('400 Bad Request'),
        new Error('404 Not Found')
      ]
      
      errors.forEach(error => {
        expect(ErrorUtils.getErrorType(error)).toBe('client')
      })
    })

    it('detects server errors', () => {
      const errors = [
        new Error('500 Internal Server Error'),
        new Error('503 Service Unavailable')
      ]
      
      errors.forEach(error => {
        expect(ErrorUtils.getErrorType(error)).toBe('server')
      })
    })
  })

  describe('getUserFriendlyMessage', () => {
    it('returns appropriate messages for each error type', () => {
      const testCases = [
        { error: new Error('fetch failed'), expected: 'Check your internet connection and try again' },
        { error: new Error('timeout'), expected: 'Search is taking too long. Please try again.' },
        { error: new Error('400 error'), expected: 'There was a problem with your search. Please try different terms.' },
        { error: new Error('500 error'), expected: 'Search is temporarily unavailable. Please try again in a few minutes.' }
      ]
      
      testCases.forEach(({ error, expected }) => {
        expect(ErrorUtils.getUserFriendlyMessage(error)).toBe(expected)
      })
    })

    it('handles AbortError correctly', () => {
      const error = new Error('Aborted')
      error.name = 'AbortError'
      expect(ErrorUtils.getUserFriendlyMessage(error)).toBe('Search was cancelled')
    })
  })
})

describe('DateUtils', () => {
  describe('formatDateToTimestamp', () => {
    it('converts Date objects to timestamps', () => {
      const date = new Date('2024-01-01T00:00:00.000Z')
      const timestamp = DateUtils.formatDateToTimestamp(date)
      expect(timestamp).toBe(Math.floor(date.getTime() / 1000))
    })

    it('converts date strings to timestamps', () => {
      const dateString = '2024-01-01T00:00:00.000Z'
      const expectedTimestamp = Math.floor(new Date(dateString).getTime() / 1000)
      expect(DateUtils.formatDateToTimestamp(dateString)).toBe(expectedTimestamp)
    })

    it('returns 0 for invalid dates', () => {
      expect(DateUtils.formatDateToTimestamp(null)).toBe(0)
      expect(DateUtils.formatDateToTimestamp(undefined)).toBe(0)
      expect(DateUtils.formatDateToTimestamp('invalid date')).toBe(0)
    })
  })

  describe('getPresetDateRange', () => {
    it('returns correct ranges for presets', () => {
      const now = Date.now()
      const result = DateUtils.getPresetDateRange('3months')
      
      expect(result.to).toBeCloseTo(Math.floor(now / 1000), -2) // Within ~100s of now
      expect(result.from).toBeLessThan(result.to)
      
      // Should be approximately 3 months ago
      const expectedFrom = now - (3 * 30 * 24 * 60 * 60 * 1000)
      expect(result.from).toBeCloseTo(Math.floor(expectedFrom / 1000), -4) // Within ~10000s
    })

    it('handles different preset values', () => {
      const presets = ['recent', '3months', '6months', '1year']
      
      presets.forEach(preset => {
        const range = DateUtils.getPresetDateRange(preset)
        expect(range.from).toBeLessThan(range.to)
        expect(range.from).toBeGreaterThan(0)
        expect(range.to).toBeGreaterThan(0)
      })
    })

    it('uses default range for unknown presets', () => {
      const unknownRange = DateUtils.getPresetDateRange('unknown')
      const defaultRange = DateUtils.getPresetDateRange('recent')
      
      expect(unknownRange).toEqual(defaultRange)
    })
  })
})

describe('QueryUtils', () => {
  describe('sanitizeQuery', () => {
    it('trims whitespace', () => {
      expect(QueryUtils.sanitizeQuery('  test query  ')).toBe('test query')
      expect(QueryUtils.sanitizeQuery('\t\ntest\t\n')).toBe('test')
    })
  })

  describe('isWildcardQuery', () => {
    it('detects wildcard queries', () => {
      expect(QueryUtils.isWildcardQuery('*')).toBe(true)
      expect(QueryUtils.isWildcardQuery(' * ')).toBe(true)
    })

    it('rejects non-wildcard queries', () => {
      expect(QueryUtils.isWildcardQuery('test')).toBe(false)
      expect(QueryUtils.isWildcardQuery('*test')).toBe(false)
      expect(QueryUtils.isWildcardQuery('')).toBe(false)
    })
  })

  describe('isValidQuery', () => {
    it('accepts valid queries', () => {
      expect(QueryUtils.isValidQuery('abc')).toBe(true)
      expect(QueryUtils.isValidQuery('longer query')).toBe(true)
      expect(QueryUtils.isValidQuery('*')).toBe(true)
    })

    it('rejects invalid queries', () => {
      expect(QueryUtils.isValidQuery('ab')).toBe(false)
      expect(QueryUtils.isValidQuery('')).toBe(false)
      expect(QueryUtils.isValidQuery('  ')).toBe(false)
    })
  })

  describe('shouldSearch', () => {
    it('allows wildcard searches', () => {
      expect(QueryUtils.shouldSearch('*', false)).toBe(true)
      expect(QueryUtils.shouldSearch('*', true)).toBe(true)
    })

    it('allows queries with sufficient length', () => {
      expect(QueryUtils.shouldSearch('abc', false)).toBe(true)
      expect(QueryUtils.shouldSearch('longer', true)).toBe(true)
    })

    it('allows empty queries with active filters', () => {
      expect(QueryUtils.shouldSearch('', true)).toBe(true)
    })

    it('rejects short queries without filters', () => {
      expect(QueryUtils.shouldSearch('ab', false)).toBe(false)
      expect(QueryUtils.shouldSearch('', false)).toBe(false)
    })
  })

  describe('formatQueryForSearch', () => {
    it('formats valid queries correctly', () => {
      expect(QueryUtils.formatQueryForSearch('*', false)).toBe('*')
      expect(QueryUtils.formatQueryForSearch('test query', false)).toBe('test query')
      expect(QueryUtils.formatQueryForSearch('', true)).toBe('*') // Empty with filters becomes wildcard
    })

    it('returns empty string for invalid queries', () => {
      expect(QueryUtils.formatQueryForSearch('ab', false)).toBe('')
      expect(QueryUtils.formatQueryForSearch('', false)).toBe('')
    })
  })
})

describe('PerformanceUtils', () => {
  describe('debounce', () => {
    it('delays function execution', async () => {
      let callCount = 0
      const debouncedFn = PerformanceUtils.debounce(() => callCount++, 50)
      
      debouncedFn()
      debouncedFn()
      debouncedFn()
      
      expect(callCount).toBe(0)
      
      await new Promise(resolve => setTimeout(resolve, 60))
      expect(callCount).toBe(1)
    })

    it('cancels previous calls', async () => {
      let lastArg = ''
      const debouncedFn = PerformanceUtils.debounce((arg: string) => lastArg = arg, 50)
      
      debouncedFn('first')
      debouncedFn('second')
      debouncedFn('third')
      
      await new Promise(resolve => setTimeout(resolve, 60))
      expect(lastArg).toBe('third')
    })
  })

  describe('throttle', () => {
    it('limits function calls', async () => {
      let callCount = 0
      const throttledFn = PerformanceUtils.throttle(() => callCount++, 50)
      
      throttledFn()
      throttledFn()
      throttledFn()
      
      expect(callCount).toBe(1)
      
      await new Promise(resolve => setTimeout(resolve, 60))
      throttledFn()
      
      expect(callCount).toBe(2)
    })
  })
})