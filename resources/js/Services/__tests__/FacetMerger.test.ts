import { describe, it, expect } from 'vitest';

import { FacetMerger } from '../FacetMerger';

describe('FacetMerger.mergeFacetsWithSelectionMap', () => {
  it('merges counts and selected values across facets', () => {
    const initialFacets = [
      {
        field: 'tenant_shortname',
        label: 'Tenant',
        values: [
          { value: 'VU SA', label: 'VU SA', count: 10 },
          { value: 'MIF', label: 'MIF', count: 5 },
        ],
      },
      {
        field: 'type_slugs',
        label: 'Type',
        values: [
          { value: 'faculty', label: 'Faculty', count: 3 },
          { value: 'department', label: 'Department', count: 2 },
        ],
      },
    ];

    const currentFacets = [
      {
        field: 'tenant_shortname',
        label: 'Tenant',
        values: [
          { value: 'VU SA', label: 'VU SA', count: 2 },
          { value: 'GMC', label: 'GMC', count: 4 },
        ],
      },
    ];

    const merged = FacetMerger.mergeFacetsWithSelectionMap(
      initialFacets,
      currentFacets,
      {
        tenant_shortname: ['MIF'],
      },
    );

    const tenantFacet = merged.find(facet => facet.field === 'tenant_shortname');
    expect(tenantFacet).toBeTruthy();
    expect(tenantFacet?.values.find(value => value.value === 'VU SA')?.count).toBe(2);
    expect(tenantFacet?.values.find(value => value.value === 'MIF')?.isSelected).toBe(true);
    expect(tenantFacet?.values.find(value => value.value === 'GMC')).toBeTruthy();

    const typeFacet = merged.find(facet => facet.field === 'type_slugs');
    expect(typeFacet).toBeTruthy();
    expect(typeFacet?.values.find(value => value.value === 'department')?.count).toBe(0);
  });
});
