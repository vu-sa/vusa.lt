import { describe, it, expect } from 'vitest'
import {
  SearchClientFactory,
  SearchParamsBuilder,
  FilterBuilder
} from '../services/SearchClientFactory'
import type { TypesenseConfig } from '../types'

describe('SearchClientFactory', () => {
  const mockConfig: TypesenseConfig = {
    apiKey: 'test-key',
    nodes: [{ protocol: 'https', host: 'search.example.com', port: 443 }]
  }

  describe('isValidConfig', () => {
    it('returns true for valid config', () => {
      expect(SearchClientFactory.isValidConfig(mockConfig)).toBe(true)
    })

    it('returns false for invalid configs', () => {
      expect(SearchClientFactory.isValidConfig(null)).toBe(false)
      expect(SearchClientFactory.isValidConfig({})).toBe(false)
      expect(SearchClientFactory.isValidConfig({ apiKey: '' })).toBe(false)
      expect(SearchClientFactory.isValidConfig({ apiKey: 'key', nodes: 'not-array' })).toBe(false)
    })
  })

  describe('createTypesenseClient', () => {
    it('creates client with correct properties', () => {
      const client = SearchClientFactory.createTypesenseClient({
        apiKey: 'test-key',
        nodes: mockConfig.nodes
      })

      expect(client.apiKey).toBe('test-key')
      expect(client.nodes).toEqual(mockConfig.nodes)
      expect(typeof client.search).toBe('function')
    })

    it('throws error when no nodes configured', () => {
      expect(() =>
        SearchClientFactory.createTypesenseClient({
          apiKey: 'test-key',
          nodes: []
        })
      ).toThrow('No Typesense nodes configured')
    })
  })

  describe('createPublicClient', () => {
    it('creates client from TypesenseConfig', () => {
      const client = SearchClientFactory.createPublicClient(mockConfig)

      expect(client.apiKey).toBe('test-key')
      expect(client.nodes).toEqual(mockConfig.nodes)
    })
  })

  describe('createAdminClient', () => {
    it('creates client with scoped API key', () => {
      const client = SearchClientFactory.createAdminClient('scoped-key', mockConfig.nodes)

      expect(client.apiKey).toBe('scoped-key')
      expect(client.nodes).toEqual(mockConfig.nodes)
    })
  })
})

describe('SearchParamsBuilder', () => {
  describe('construction', () => {
    it('creates with default wildcard query', () => {
      const params = new SearchParamsBuilder().build()
      expect(params.q).toBe('*')
    })

    it('creates with custom query', () => {
      const params = new SearchParamsBuilder('test').build()
      expect(params.q).toBe('test')
    })
  })

  describe('builder methods', () => {
    it('chains methods correctly', () => {
      const params = new SearchParamsBuilder('search')
        .queryBy(['title', 'description'])
        .facetBy('type,status')
        .filterBy('status:active')
        .sortBy('created_at', 'desc')
        .perPage(20)
        .page(2)
        .maxFacetValues(50)
        .numTypos(2)
        .typoTokensThreshold(3)
        .dropTokensThreshold(1)
        .build()

      expect(params.q).toBe('search')
      expect(params.query_by).toBe('title,description')
      expect(params.facet_by).toBe('type,status')
      expect(params.filter_by).toBe('status:active')
      expect(params.sort_by).toBe('created_at:desc')
      expect(params.per_page).toBe(20)
      expect(params.page).toBe(2)
      expect(params.max_facet_values).toBe(50)
      expect(params.num_typos).toBe(2)
      expect(params.typo_tokens_threshold).toBe(3)
      expect(params.drop_tokens_threshold).toBe(1)
    })

    it('handles array query_by', () => {
      const params = new SearchParamsBuilder()
        .queryBy(['field1', 'field2', 'field3'])
        .build()

      expect(params.query_by).toBe('field1,field2,field3')
    })

    it('handles string query_by', () => {
      const params = new SearchParamsBuilder()
        .queryBy('single_field')
        .build()

      expect(params.query_by).toBe('single_field')
    })

    it('defaults sort direction to desc', () => {
      const params = new SearchParamsBuilder()
        .sortBy('date')
        .build()

      expect(params.sort_by).toBe('date:desc')
    })

    it('skips filterBy when empty', () => {
      const params = new SearchParamsBuilder()
        .filterBy('')
        .build()

      expect(params.filter_by).toBeUndefined()
    })
  })
})

describe('FilterBuilder', () => {
  describe('equals', () => {
    it('builds exact match for string', () => {
      const filter = new FilterBuilder()
        .equals('status', 'active')
        .build()

      expect(filter).toBe('status:=active')
    })

    it('builds exact match for number', () => {
      const filter = new FilterBuilder()
        .equals('year', 2024)
        .build()

      expect(filter).toBe('year:2024')
    })

    it('builds exact match for boolean', () => {
      const filter = new FilterBuilder()
        .equals('is_active', true)
        .build()

      expect(filter).toBe('is_active:true')
    })

    it('skips undefined values', () => {
      const filter = new FilterBuilder()
        .equals('status', undefined as any)
        .build()

      expect(filter).toBe('')
    })
  })

  describe('anyOf', () => {
    it('builds array filter for strings', () => {
      const filter = new FilterBuilder()
        .anyOf('tenant', ['VU SA', 'MIF', 'CHGF'])
        .build()

      expect(filter).toBe('tenant:[VU SA,MIF,CHGF]')
    })

    it('builds array filter for numbers', () => {
      const filter = new FilterBuilder()
        .anyOf('year', [2023, 2024])
        .build()

      expect(filter).toBe('year:[2023,2024]')
    })

    it('skips empty arrays', () => {
      const filter = new FilterBuilder()
        .anyOf('tenant', [])
        .build()

      expect(filter).toBe('')
    })

    it('escapes special characters in values', () => {
      const filter = new FilterBuilder()
        .anyOf('title', ['Test, Value', 'Normal'])
        .build()

      expect(filter).toContain('`Test, Value`')
    })
  })

  describe('range', () => {
    it('builds range with min and max', () => {
      const filter = new FilterBuilder()
        .range('price', 10, 100)
        .build()

      expect(filter).toBe('price:[10..100]')
    })

    it('builds gte filter with min only', () => {
      const filter = new FilterBuilder()
        .range('price', 10, undefined)
        .build()

      expect(filter).toBe('price:>=10')
    })

    it('builds lte filter with max only', () => {
      const filter = new FilterBuilder()
        .range('price', undefined, 100)
        .build()

      expect(filter).toBe('price:<=100')
    })

    it('skips when no values provided', () => {
      const filter = new FilterBuilder()
        .range('price', undefined, undefined)
        .build()

      expect(filter).toBe('')
    })
  })

  describe('timestampRange', () => {
    it('builds timestamp range filter', () => {
      const from = new Date('2024-01-01T00:00:00Z')
      const to = new Date('2024-12-31T23:59:59Z')

      const filter = new FilterBuilder()
        .timestampRange('created_at', from, to)
        .build()

      expect(filter).toMatch(/created_at:\[\d+\.\.\d+\]/)
    })

    it('handles from only', () => {
      const from = new Date('2024-01-01T00:00:00Z')

      const filter = new FilterBuilder()
        .timestampRange('created_at', from, undefined)
        .build()

      expect(filter).toMatch(/created_at:>=\d+/)
    })

    it('handles to only', () => {
      const to = new Date('2024-12-31T23:59:59Z')

      const filter = new FilterBuilder()
        .timestampRange('created_at', undefined, to)
        .build()

      expect(filter).toMatch(/created_at:<=\d+/)
    })
  })

  describe('chaining and combining', () => {
    it('combines multiple filters with &&', () => {
      const filter = new FilterBuilder()
        .equals('status', 'active')
        .anyOf('tenant', ['VU SA', 'MIF'])
        .range('year', 2020, 2024)
        .build()

      expect(filter).toBe('status:=active && tenant:[VU SA,MIF] && year:[2020..2024]')
    })

    it('isEmpty returns true for empty builder', () => {
      const builder = new FilterBuilder()
      expect(builder.isEmpty()).toBe(true)
    })

    it('isEmpty returns false after adding filter', () => {
      const builder = new FilterBuilder().equals('status', 'active')
      expect(builder.isEmpty()).toBe(false)
    })
  })
})
