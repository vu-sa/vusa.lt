import { describe, expect, it } from 'vitest';

import {
  getCollectionSortOptions,
  RELEVANCE_SORT_VALUE,
  resolveSortValue,
} from '../collectionFacetConfig';

describe('getCollectionSortOptions', () => {
  it('prepends the relevance option for known collections', () => {
    const options = getCollectionSortOptions('meetings');

    expect(options[0].value).toBe(RELEVANCE_SORT_VALUE);
    expect(options.some((o) => o.value === 'start_time:desc')).toBe(true);
  });

  it('prepends the relevance option for the generic fallback', () => {
    const options = getCollectionSortOptions('resources');

    expect(options[0].value).toBe(RELEVANCE_SORT_VALUE);
    expect(options.some((o) => o.value === 'created_at:desc')).toBe(true);
  });
});

describe('resolveSortValue', () => {
  it('expands the relevance sentinel into a bucketed text_match sort with a date tiebreak', () => {
    expect(resolveSortValue('meetings', RELEVANCE_SORT_VALUE))
      .toBe('_text_match(buckets:10):desc,start_time:desc');
    expect(resolveSortValue('institutions', RELEVANCE_SORT_VALUE))
      .toBe('_text_match(buckets:10):desc,created_at:desc');
  });

  it('passes concrete sort values through unchanged', () => {
    expect(resolveSortValue('meetings', 'start_time:asc')).toBe('start_time:asc');
  });
});
