import { getCurrentInstance, inject, provide, type InjectionKey } from 'vue';

export interface FacetOption {
  value: string;
  count: number | null;
}

export type FacetSearch = (field: string, query: string, signal?: AbortSignal) => Promise<FacetOption[]>;
const facetSearchKey: InjectionKey<FacetSearch> = Symbol('facet-search');

export function provideFacetSearch(search: FacetSearch): void {
  if (getCurrentInstance()) provide(facetSearchKey, search);
}

export function useFacetSearch(): FacetSearch | undefined {
  return inject(facetSearchKey, undefined);
}

export interface RawFacet {
  field_name: string;
  counts: Array<{ value: string; count: number }>;
}

export function facetSearches(params: Record<string, unknown>, filters: Record<string, string> = {}): Record<string, unknown>[] {
  const { facetFilters: _filters, ...main } = params;
  return [main, ...Object.entries(filters).map(([field, filter]) => ({
    ...main, filter_by: filter, facet_by: field, per_page: 0, page: 1,
  }))];
}

export function mergeDisjunctiveFacets(main: RawFacet[], fields: string[], results: Array<{ facet_counts?: RawFacet[]; error?: string }>): RawFacet[] {
  const facets = main.filter(facet => !fields.includes(facet.field_name));
  fields.forEach((field, index) => {
    const result = results[index];
    const facet = !result?.error && result?.facet_counts?.find(item => item.field_name === field);
    if (facet) facets.push(facet);
  });
  return facets;
}
