import { afterEach, describe, expect, it, vi } from 'vitest';

import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

import { buildProfileParams, splitSortExpressions, type SearchProfile } from '../profiles';
import { descriptionMatch, documentWithMatch, hasExactNameMatch, matchTitle, normalizeSearchName, type SearchMatchDocument } from '../matches';
import { SearchClientFactory } from '../services/SearchClientFactory';

const { stubGlobal, restoreGlobals } = scopedGlobals();
afterEach(restoreGlobals);

const profile: SearchProfile = {
  version: 2,
  parameters: { query_by: 'title,description_lt,description_en,body,search_text_lt,search_text_en', query_by_weights: '10,4,4,2,1,1', infix: 'fallback,off,off,off,off,off', num_typos: '2,2,2,2,2,2' },
  defaultSort: 'created_at:desc', facetFields: ['lang', 'tenant'], sortFields: ['title', 'created_at'],
};

describe('search profiles and matches', () => {
  it('preserves type promotion expressions containing multiple or comma-containing labels', () => {
    const sort = '_text_match:desc,_eval(content_type:=[`Įstatai`,`Planai, ataskaitos`]):desc,created_at:desc';
    const documentProfile = { ...profile, facetFields: ['content_type'] };
    expect(splitSortExpressions(sort)).toHaveLength(3);
    expect(buildProfileParams(documentProfile, { q: 'įstat', sort_by: sort }).sort_by).toBe(sort);
    expect(() => buildProfileParams(profile, { q: 'įstat', sort_by: sort })).toThrow('Invalid sort');
  });

  it('keeps locale fields aligned with weights and flags, with relevance before recency', () => {
    const result = buildProfileParams(profile, { q: 'students', query_by: 'title' }, 'en');
    expect(result.query_by).toBe('description_en,search_text_en,title,description_lt,body,search_text_lt');
    expect(result.query_by_weights).toBe('4,1,10,4,2,1');
    expect(result.infix).toBe('off,off,fallback,off,off,off');
    expect(result.sort_by).toBe('_text_match:desc,created_at:desc');
    expect(buildProfileParams(profile, { q: 'students', sort_by: 'title:asc' }).sort_by).toBe('title:asc');
    expect(buildProfileParams(profile, { q: '*' }).sort_by).toBe('created_at:desc');
    expect(buildProfileParams(profile, { q: 'students', sort_by: '_text_match(buckets:10):desc,title:asc' }).sort_by).toBe('_text_match:desc,title:asc');
  });

  it('rejects facets and sorts outside the schema and supports legacy configurations', () => {
    expect(() => buildProfileParams(profile, { facet_by: 'email' })).toThrow('Invalid facet');
    expect(() => buildProfileParams(profile, { sort_by: 'body:asc' })).toThrow('Invalid sort');
    expect(buildProfileParams(undefined, { query_by: 'title' })).toEqual({ query_by: 'title' });
  });

  it('narrows explicit VU ŠA queries to locale-preserving search_text_lt without matching VU SA', () => {
    const withHighlight: SearchProfile = { ...profile, parameters: { ...profile.parameters, highlight_fields: String(profile.parameters.query_by) } };
    const result = buildProfileParams(withHighlight, { q: 'VU ŠA studijų kolegija', pinned_hits: '1:1' });
    expect(result).toMatchObject({
      q: '"vu ša" studijų kolegija',
      query_by: 'search_text_lt',
      query_by_weights: '1',
      infix: 'off',
      num_typos: '2',
      highlight_fields: 'search_text_lt',
      drop_tokens_threshold: 0,
      split_join_tokens: 'off',
      enable_synonyms: false,
      enable_curations: false,
      pinned_hits: undefined,
    });
    expect(result).not.toHaveProperty('pre_segmented_query');

    // Decomposed Unicode (NFD) and repeated calls must behave identically.
    expect(buildProfileParams(profile, { q: 'VU S\u030CA' }).q).toBe('"vu ša"');
    expect(buildProfileParams(profile, { q: '"VU ŠA" taryba' }).q).toBe('"VU ŠA" taryba');

    // Legacy profiles without search_text_lt fall back to pre_segmented_query so š never folds to s.
    const legacy = buildProfileParams(undefined, { q: 'VU ŠA', query_by: 'title' });
    expect(legacy).toMatchObject({
      q: '"vu ša"',
      pre_segmented_query: true,
      num_typos: 0,
      drop_tokens_threshold: 0,
      enable_synonyms: false,
      enable_curations: false,
    });

    // Standalone ŠA also narrows to search_text_lt so it never folds into SA, without breaking mid-typing prefixes.
    const standalone = buildProfileParams(profile, { q: 'ŠA' });
    expect(standalone).toMatchObject({ q: 'ŠA', query_by: 'search_text_lt', enable_synonyms: false, enable_curations: false });
    expect(buildProfileParams(profile, { q: 'VU SA ŠA' })).toMatchObject({ q: 'VU SA ŠA', query_by: 'search_text_lt' });

    // Ordinary VU SA, ša-prefixed words and full Šiauliai Academy queries stay untouched.
    const vuSa = buildProfileParams(profile, { q: 'VU SA' });
    expect(vuSa.q).toBe('VU SA');
    expect(vuSa.query_by).toContain('title');
    expect(buildProfileParams(profile, { q: 'šablonas' }).query_by).toContain('title');
    expect(buildProfileParams(profile, { q: 'VU Šiaulių akademija' }).query_by).toContain('title');
  });

  it('matches normalized exact names across identity, short name and alias fields', () => {
    const query = normalizeSearchName('  VU  Senatas ');
    expect(query).toBe('vu senatas');
    expect(hasExactNameMatch({ name_lt: 'VU Senatas' }, query)).toBe(true);
    expect(hasExactNameMatch({ short_name_lt: 'Senatas' }, normalizeSearchName('Senatas'))).toBe(true);
    expect(hasExactNameMatch({ alias: 'vu-senatas' }, normalizeSearchName('vu-senatas'))).toBe(true);
    expect(hasExactNameMatch({ name_lt: 'Studentų atstovas VU Senate' }, query)).toBe(false);
    expect(hasExactNameMatch({ alias: '' }, '')).toBe(false);
  });

  it('omits excerpts from title and name highlights and content snippets that only repeat the title', () => {
    for (const [field, snippet] of [['title', '⟦Guide⟧'], ['name_lt', '⟦Guide⟧'], ['body', '<p>⟦Guide⟧</p>']]) {
      expect(documentWithMatch({ document: { title: 'Guide' }, highlights: [{ field, snippet }] })).not.toHaveProperty('_searchMatch');
    }
    const doc = documentWithMatch({ document: { title: 'Guide' }, highlights: [
      { field: 'title', snippet: '⟦Guide⟧' },
      { field: 'description', snippet: 'A ⟦guide⟧ for representatives' },
    ] });
    expect(doc._searchMatch).toMatchObject({ field: 'description' });
  });

  it('prefers a row\'s own description field over a body excerpt', () => {
    const doc = documentWithMatch({ document: { title: 'Guide', short: '<p>Short for students</p>' }, highlights: [
      { field: 'body', snippet: 'Body about ⟦students⟧' },
      { field: 'short', snippet: 'Short for ⟦students⟧' },
    ] }) as SearchMatchDocument;
    expect(descriptionMatch(doc, 'short')?.segments.map(segment => segment.text).join('')).toBe('Short for students');
    expect(descriptionMatch(doc, 'summary')?.field).toBe('body');
  });
});

it('counts facets without their own filter and retains mandatory filters in option searches', async () => {
  const fetch = vi.fn().mockResolvedValueOnce({ ok: true, json: async () => ({ results: [
    { hits: [], found: 1, facet_counts: [{ field_name: 'lang', counts: [{ value: 'lt', count: 1 }] }] },
    { facet_counts: [{ field_name: 'lang', counts: [{ value: 'lt', count: 1 }, { value: 'en', count: 2 }] }] },
  ] }) }).mockResolvedValueOnce({ ok: true, json: async () => ({ facet_counts: [{ field_name: 'lang', counts: [{ value: 'en', count: 2 }] }] }) });
  stubGlobal('fetch', fetch);
  const client = SearchClientFactory.createTypesenseClient({ apiKey: 'public-key', nodes: [{ protocol: 'https', host: 'search.example.com', port: 443 }] });
  const result = await client.search('documents', { q: 'student', per_page: 20, facet_by: 'lang', filter_by: 'is_active:=true && tenant:=MIF && lang:=lt', facetFilters: { lang: 'is_active:=true && tenant:=MIF' } });
  const body = JSON.parse(fetch.mock.calls[0][1].body);
  expect(body.searches[0].filter_by).toContain('lang:=lt');
  expect(body.searches[1]).toMatchObject({ q: 'student', per_page: 0, filter_by: 'is_active:=true && tenant:=MIF' });
  expect(result.facet_counts?.[0].counts).toHaveLength(2);
  await client.searchFacet('lang', 'en');
  const query = new URL(fetch.mock.calls[1][0]).searchParams;
  expect(query.get('facet_query')).toBe('lang:en');
  expect(query.get('filter_by')).toBe('is_active:=true && tenant:=MIF');
});

it('does not retain misleading counts when a facet query fails', async () => {
  stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => ({ results: [
    { hits: [], found: 1, facet_counts: [{ field_name: 'lang', counts: [{ value: 'lt', count: 1 }] }] },
    { error: 'Unavailable', code: 503 },
  ] }) }));
  const client = SearchClientFactory.createTypesenseClient({ apiKey: 'key', nodes: [{ protocol: 'https', host: 'search.example.com', port: 443 }] });
  const result = await client.search('documents', { q: '*', per_page: 20, facet_by: 'lang', filter_by: 'lang:=lt', facetFilters: { lang: '' } });
  expect(result.facet_counts).toEqual([]);
});

it('matches pinned records normally in the same scoped request to recover title highlights', async () => {
  const fetch = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ results: [
    { hits: [{ document: { id: '42', title: 'VU SA Įstatai' }, highlights: [] }], found: 1 },
    { hits: [{ document: { id: '42', title: 'VU SA Įstatai' }, highlights: [{ field: 'search_text_lt', snippet: 'VU SA ⟦Įstatai⟧' }] }] },
  ] }) });
  stubGlobal('fetch', fetch);
  const client = SearchClientFactory.createTypesenseClient({ apiKey: 'scoped-key', nodes: [{ protocol: 'https', host: 'search.example.com', port: 443 }] });
  const result = await client.search('documents', { q: 'įstatų', per_page: 24, pinned_hits: '42:1', filter_by: 'is_active:=true && language_code:=lt' });
  expect(fetch).toHaveBeenCalledTimes(1);
  const request = fetch.mock.calls[0][1];
  expect(request.headers['X-TYPESENSE-API-KEY']).toBe('scoped-key');
  const { searches } = JSON.parse(request.body);
  expect(searches).toHaveLength(2);
  expect(searches[1]).toMatchObject({ filter_by: '(is_active:=true && language_code:=lt) && id:=[`42`]', enable_overrides: false, per_page: 1 });
  expect(searches[1]).not.toHaveProperty('pinned_hits');
  const doc = result.hits?.[0].document as SearchMatchDocument;
  expect(matchTitle('VU SA Įstatai', doc._searchTitleMatches)?.segments).toContainEqual({ text: 'Įstatai', matched: true });
});
