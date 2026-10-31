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
    expect(options.some(o => o.value === 'title:asc')).toBe(true);
    expect(options.some(o => o.value === 'title:desc')).toBe(true);
    expect(options.some(o => o.value === 'agenda_items_count:asc')).toBe(true);
  });

  it('provides sort options for agenda items', () => {
    const options = getCollectionSortOptions('agenda_items');

    expect(options.some(o => o.value === 'title:asc')).toBe(true);
    expect(options.some(o => o.value === 'title:desc')).toBe(true);
  });

  it('provides sort options for resources', () => {
    const options = getCollectionSortOptions('resources');

    expect(options[0].value).toBe(RELEVANCE_SORT_VALUE);
    expect(options.some(o => o.value === 'name_lt:asc')).toBe(true);
    expect(options.some(o => o.value === 'name_lt:desc')).toBe(true);
    expect(options.some(o => o.value === 'capacity:desc')).toBe(true);
    expect(options.some(o => o.value === 'capacity:asc')).toBe(true);
    expect(options.some(o => o.value === 'created_at:desc')).toBe(true);
  });

  it('prepends the relevance option for the generic fallback', () => {
    const options = getCollectionSortOptions('unknown_collection');

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
  it('resolves the resource default into a main-tenant boost and localized names', () => {
    const defaultSort = getCollectionFacetConfig('resources')!.defaultSortBy;

    expect(getCollectionSortOptions('resources').some(option => option.value === defaultSort)).toBe(true);
    expect(resolveSortValue('resources', defaultSort, 'lt', 7)).toBe('_eval(tenant_id:7):desc,name_lt:asc');
    expect(resolveSortValue('resources', defaultSort, 'en', 7)).toBe('_eval(tenant_id:7):desc,name_en:asc');
    expect(resolveSortValue('resources', defaultSort, 'lt', null)).toBe('name_lt:asc');
    expect(resolveSortValue('resources', 'created_at:desc', 'lt', 7)).toBe('created_at:desc');
    expect(resolveSortValue('resources', RELEVANCE_SORT_VALUE, 'en', 7)).toBe('_text_match:desc,name_en:asc');
  });

  it('expands the relevance sentinel into a text_match sort with a date tiebreak', () => {
    expect(resolveSortValue('meetings', RELEVANCE_SORT_VALUE))
      .toBe('_text_match:desc,start_time:desc');
    expect(resolveSortValue('institutions', RELEVANCE_SORT_VALUE))
      .toBe('_text_match:desc,created_at:desc');
  });

  it('passes concrete sort values through unchanged', () => {
    expect(resolveSortValue('meetings', 'start_time:asc')).toBe('start_time:asc');
  });

  it('rewrites localized name field for duties and resources in english', () => {
    expect(resolveSortValue('duties', 'name_lt:asc', 'en')).toBe('name_en:asc');
    expect(resolveSortValue('duties', 'name_lt:asc', 'lt')).toBe('name_lt:asc');
    expect(resolveSortValue('resources', 'name_lt:desc', 'en')).toBe('name_en:desc');
    expect(resolveSortValue('resources', 'name_lt:desc', 'lt')).toBe('name_lt:desc');
  });
});

describe('institution activity filter', () => {
  it('names each status as its badge does', () => {
    for (const [status, presentation] of Object.entries(institutionActivityStatuses)) {
      expect(getFacetValueLabel('activity_status', status)).toBe(presentation.label);
    }
  });
});
