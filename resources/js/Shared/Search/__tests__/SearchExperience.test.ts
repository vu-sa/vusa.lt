import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import { buildProfileParams, splitSortExpressions, type SearchProfile } from '../profiles';
import { documentWithMatch, matchTitle, type SearchMatchDocument } from '../matches';
import { SearchClientFactory } from '../services/SearchClientFactory';

import SearchMatch from '@/Components/ui/SearchMatch.vue';

const profile: SearchProfile = {
  version: 2,
  parameters: { query_by: 'title,description_lt,description_en,body,search_text_lt,search_text_en', query_by_weights: '10,4,4,2,1,1', infix: 'fallback,off,off,off,off,off', num_typos: '2,2,2,2,2,2' },
  defaultSort: 'created_at:desc', facetFields: ['lang', 'tenant'], sortFields: ['title', 'created_at'],
};

afterEach(() => vi.unstubAllGlobals());

describe('search profiles and matches', () => {
  it('preserves type promotion expressions containing multiple or comma-containing labels', () => {
    const sort = '_text_match:desc,_eval(content_type:=[`Įstatai`,`Planai, ataskaitos`]):desc,created_at:desc';
    const documentProfile = { ...profile, facetFields: ['content_type'] };
    expect(splitSortExpressions(sort)).toHaveLength(3);
    expect(buildProfileParams(documentProfile, { q: 'įstat', sort_by: sort }).sort_by).toBe(sort);
    expect(() => buildProfileParams(profile, { q: 'įstat', sort_by: sort })).toThrow('Invalid sort');
  });

  it('highlights inflected title words from stemmed fields without adding an excerpt', () => {
    const title = 'VU SA Įstatai (nuo 2025 m.)';
    const doc = documentWithMatch({ document: { title }, highlights: [{ field: 'search_text_lt', snippet: 'VU SA ⟦Įstatai⟧ (nuo 2025 m.)' }] }) as SearchMatchDocument;
    const wrapper = mount(SearchMatch, { props: { match: matchTitle(title, doc._searchTitleMatches), inline: true } });
    expect(wrapper.text()).toBe(title);
    expect(wrapper.get('mark').text()).toBe('Įstatai');
    expect(doc._searchMatch).toBeUndefined();
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

  it('prefers body excerpts and renders untrusted highlight text without HTML', () => {
    const document = documentWithMatch({ document: { title: 'Guide' }, highlights: [
      { field: 'title', snippet: '⟦Guide⟧' },
      { field: 'body', snippet: '<img src=x onerror=alert(1)> ⟦students⟧ & friends' },
    ] });
    const wrapper = mount(SearchMatch, { props: { match: document._searchMatch } });
    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.find('mark').text()).toBe('students');
    expect(wrapper.text()).toBe('students & friends');
  });

  it('strips stored and encoded HTML while keeping highlighted text and block spacing', () => {
    for (const snippet of [
      '<p>For <strong>⟦students⟧</strong></p><p>&amp; representatives</p><script>alert(1)</script>',
      '&lt;p&gt;For &lt;strong&gt;⟦students⟧&lt;/strong&gt;&lt;/p&gt;&lt;p&gt;&amp;amp; representatives&lt;/p&gt;',
    ]) {
      const doc = documentWithMatch({ document: { title: 'Guide' }, highlights: [{ field: 'description', snippet }] });
      const wrapper = mount(SearchMatch, { props: { match: doc._searchMatch, compact: true } });
      expect(wrapper.text()).toBe('For students & representatives');
      expect(wrapper.find('mark').text()).toBe('students');
      expect(wrapper.classes()).toContain('text-xs');
      expect(wrapper.classes()).toContain('line-clamp-1');
    }
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

  it('preserves the complete displayed title when the highlighted snippet is truncated', () => {
    const title = 'A long guide for students and their representatives';
    const doc = documentWithMatch({ document: { title }, highlights: [{ field: 'title', snippet: 'guide for ⟦students⟧ and their' }] }) as SearchMatchDocument;
    const match = matchTitle(title, doc._searchTitleMatches);
    const wrapper = mount(SearchMatch, { props: { match, inline: true } });
    expect(wrapper.text()).toBe(title);
    expect(wrapper.get('mark').text()).toBe('students');
    expect(wrapper.classes()).not.toContain('text-xs');
    expect(matchTitle('A different translation', doc._searchTitleMatches)).toBeUndefined();
  });
});

it('counts facets without their own filter and retains mandatory filters in option searches', async () => {
  const fetch = vi.fn().mockResolvedValueOnce({ ok: true, json: async () => ({ results: [
    { hits: [], found: 1, facet_counts: [{ field_name: 'lang', counts: [{ value: 'lt', count: 1 }] }] },
    { facet_counts: [{ field_name: 'lang', counts: [{ value: 'lt', count: 1 }, { value: 'en', count: 2 }] }] },
  ] }) }).mockResolvedValueOnce({ ok: true, json: async () => ({ facet_counts: [{ field_name: 'lang', counts: [{ value: 'en', count: 2 }] }] }) });
  vi.stubGlobal('fetch', fetch);
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
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => ({ results: [
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
  vi.stubGlobal('fetch', fetch);
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
