import { describe, it, expect } from 'vitest';

import { FacetMerger, type SelectionMap } from '../services/FacetMerger';
import type { BaseFacet, BaseSearchFilters } from '../types';

// Test helpers
function createFacet(field: string, values: Array<{ value: string; count: number }>): BaseFacet {
  return {
    field,
    label: field,
    values: values.map(v => ({ ...v, label: v.value })),
  };
}

describe('FacetMerger', () => {
  describe('mergeFacetsWithSelectionMap', () => {
    it('returns current facets when initial facets are empty', () => {
      const currentFacets = [createFacet('tenant', [{ value: 'VU SA', count: 10 }])];
      const result = FacetMerger.mergeFacetsWithSelectionMap([], currentFacets, {});

      expect(result).toEqual(currentFacets);
    });

    it('merges counts from current facets into initial facets', () => {
      const initialFacets = [
        createFacet('tenant', [
          { value: 'VU SA', count: 100 },
          { value: 'MIF', count: 50 },
        ]),
      ];
      const currentFacets = [
        createFacet('tenant', [
          { value: 'VU SA', count: 2 },
          { value: 'MIF', count: 0 },
        ]),
      ];

      const result = FacetMerger.mergeFacetsWithSelectionMap(initialFacets, currentFacets, {});

      expect(result[0].values.find(v => v.value === 'VU SA')?.count).toBe(2);
      expect(result[0].values.find(v => v.value === 'MIF')?.count).toBe(0);
    });

    it('marks selected values correctly', () => {
      const initialFacets = [
        createFacet('tenant', [
          { value: 'VU SA', count: 10 },
          { value: 'MIF', count: 5 },
        ]),
      ];
      const selectionMap: SelectionMap = {
        tenant: ['MIF'],
      };

      const result = FacetMerger.mergeFacetsWithSelectionMap(initialFacets, [], selectionMap);

      expect(result[0].values.find(v => v.value === 'MIF')?.isSelected).toBe(true);
      expect(result[0].values.find(v => v.value === 'VU SA')?.isSelected).toBe(false);
    });

    it('adds new values from current facets that are not in initial', () => {
      const initialFacets = [
        createFacet('tenant', [{ value: 'VU SA', count: 10 }]),
      ];
      const currentFacets = [
        createFacet('tenant', [
          { value: 'VU SA', count: 5 },
          { value: 'NEW', count: 3 },
        ]),
      ];

      const result = FacetMerger.mergeFacetsWithSelectionMap(initialFacets, currentFacets, {});

      expect(result[0].values).toHaveLength(2);
      expect(result[0].values.find(v => v.value === 'NEW')).toBeTruthy();
    });

    it('sorts values: selected first, then by count, then alphabetically', () => {
      const initialFacets = [
        createFacet('tenant', [
          { value: 'A', count: 1 },
          { value: 'B', count: 10 },
          { value: 'C', count: 5 },
          { value: 'D', count: 5 },
        ]),
      ];
      const selectionMap: SelectionMap = {
        tenant: ['C'],
      };

      const result = FacetMerger.mergeFacetsWithSelectionMap(initialFacets, initialFacets, selectionMap);

      const values = result[0].values.map(v => v.value);
      // C is selected so it comes first, then B (highest count), then D then A (both 5, alphabetical)
      expect(values).toEqual(['C', 'B', 'D', 'A']);
    });

    it('handles numeric selection values by converting to strings', () => {
      const initialFacets = [
        createFacet('year', [
          { value: '2023', count: 10 },
          { value: '2024', count: 5 },
        ]),
      ];
      const selectionMap: SelectionMap = {
        year: [2024], // Numeric value
      };

      const result = FacetMerger.mergeFacetsWithSelectionMap(initialFacets, [], selectionMap);

      expect(result[0].values.find(v => v.value === '2024')?.isSelected).toBe(true);
    });
  });

  describe('mergeFacets', () => {
    it('uses filter key mapper to determine selected values', () => {
      const initialFacets = [
        createFacet('tenant_shortname', [
          { value: 'VU SA', count: 10 },
          { value: 'MIF', count: 5 },
        ]),
      ];
      const filters: BaseSearchFilters = {
        query: '',
        tenants: ['MIF'],
      };
      const mapper = (field: string, f: BaseSearchFilters) => {
        if (field === 'tenant_shortname') return f.tenants || [];
        return [];
      };

      const result = FacetMerger.mergeFacets(initialFacets, [], filters, mapper);

      expect(result[0].values.find(v => v.value === 'MIF')?.isSelected).toBe(true);
    });
  });

  describe('createDefaultMapper', () => {
    it('maps common field names to filter keys', () => {
      const mapper = FacetMerger.createDefaultMapper<BaseSearchFilters & { tenants: string[]; years: number[] }>();

      const filters = {
        query: '',
        tenants: ['VU SA'],
        years: [2024],
      };

      expect(mapper('tenant_shortname', filters)).toEqual(['VU SA']);
      expect(mapper('year', filters)).toEqual([2024]);
      expect(mapper('unknown', filters)).toEqual([]);
    });
  });

  describe('mergeSimple', () => {
    it('works with default mapper for common cases', () => {
      const initialFacets = [
        createFacet('tenant_shortname', [{ value: 'VU SA', count: 10 }]),
      ];
      const filters: BaseSearchFilters & { tenants: string[] } = {
        query: '',
        tenants: ['VU SA'],
      };

      const result = FacetMerger.mergeSimple(initialFacets, [], filters);

      expect(result[0].values.find(v => v.value === 'VU SA')?.isSelected).toBe(true);
    });
  });
});
