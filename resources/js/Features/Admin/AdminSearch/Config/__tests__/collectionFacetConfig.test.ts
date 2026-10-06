import { describe, expect, it } from 'vitest';

import {
  getCollectionFacetConfig,
  getCollectionSortOptions,
  getFacetValueLabel,
  RELEVANCE_SORT_VALUE,
  resolveSortValue,
} from '../collectionFacetConfig';

import { institutionActivityStatuses } from '@/Constants/statuses';

describe('getCollectionSortOptions', () => {
  it('prepends the relevance option for known collections', () => {
    const options = getCollectionSortOptions('meetings');

    expect(options[0].value).toBe(RELEVANCE_SORT_VALUE);
    expect(options.some(o => o.value === 'start_time:desc')).toBe(true);
  });

  it('prepends the relevance option for the generic fallback', () => {
    const options = getCollectionSortOptions('resources');

    expect(options[0].value).toBe(RELEVANCE_SORT_VALUE);
    expect(options.some(o => o.value === 'created_at:desc')).toBe(true);
  });
});

describe('page facets', () => {
  it('filters pages by tenant short name', () => {
    const config = getCollectionFacetConfig('pages');

    expect(config?.facetBy).toContain('tenant_shortname');
    expect(config?.fields.find(field => field.label === 'Padalinys')?.field).toBe('tenant_shortname');
  });
});

describe('news facets', () => {
  it('filters news by tenant short name', () => {
    const config = getCollectionFacetConfig('news');

    expect(config?.facetBy).toContain('tenant_shortname');
    expect(config?.fields.find(field => field.label === 'Padalinys')?.field).toBe('tenant_shortname');
  });
});

describe('resolveSortValue', () => {
  it('expands the relevance sentinel into a text_match sort with a date tiebreak', () => {
    expect(resolveSortValue('meetings', RELEVANCE_SORT_VALUE))
      .toBe('_text_match:desc,start_time:desc');
    expect(resolveSortValue('institutions', RELEVANCE_SORT_VALUE))
      .toBe('_text_match:desc,created_at:desc');
  });

  it('passes concrete sort values through unchanged', () => {
    expect(resolveSortValue('meetings', 'start_time:asc')).toBe('start_time:asc');
  });
});

describe('institution activity filter', () => {
  it('names each status as its badge does', () => {
    for (const [status, presentation] of Object.entries(institutionActivityStatuses)) {
      expect(getFacetValueLabel('activity_status', status)).toBe(presentation.label);
    }
  });
});
