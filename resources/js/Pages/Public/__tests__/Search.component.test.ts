/**
 * Smoke test for the unified public search page.
 *
 * The search behaviour itself (multi_search, distribution, ordering, load-more, browse
 * mode, collection filtering) is covered by `usePublicMultiSearch.test.ts`. Here we only
 * assert the page wires up: it renders the search input, the content-type toggles, fires a
 * browse search on mount, and shows the resulting counts.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import Search from '../Search.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

/** One result per requested collection, keyed by collection name — mirrors the composable's own test fake. */
const fakeMultiSearchResponse = (searches: Array<{ collection: string }>) => ({
  results: searches.map((search) => {
    const found: Record<string, number> = {
      public_institutions: 3,
      public_meetings: 2,
      documents: 5,
      news: 1,
      pages: 6,
      calendar: 4,
    };
    const count = found[search.collection] ?? 1;
    return {
      found: count,
      hits: [{ document: { id: `${search.collection}-1`, title: `${search.collection} hit` }, text_match: 10 }],
    };
  }),
});

const mockFetch = vi.fn();

describe('Public/Search.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // `route()` is already stubbed globally in tests/setup.ts — assign fetch directly rather
    // than `vi.stubGlobal`/`vi.unstubAllGlobals`, which would tear that stub down too.
    globalThis.fetch = mockFetch;
    mockFetch.mockImplementation(async (_url: string, init: { body: string }) => {
      const { searches } = JSON.parse(init.body);
      return {
        ok: true,
        json: async () => fakeMultiSearchResponse(searches),
      };
    });
    vi.mocked(usePage).mockReturnValue(createMockPage() as any);
  });

  const mountPage = () =>
    mount(Search, {
      props: { initialQuery: '' },
      global: {
        stubs: {
          ...commonStubs,
          Head: true,
          DocumentResults: true,
          MeetingResults: true,
          InstitutionResults: true,
          GenericResults: true,
        },
      },
    });

  it('renders the search input', () => {
    const wrapper = mountPage();
    expect(wrapper.find('input[role="search"]').exists()).toBe(true);
  });

  it('renders a toggle for every content type', () => {
    const wrapper = mountPage();
    const text = wrapper.text();
    // The test i18n bundle returns keys verbatim, so assert on the label keys.
    for (const key of [
      'search.section_institutions',
      'search.section_meetings',
      'search.section_documents',
      'search.section_news',
      'search.section_pages',
      'search.section_calendar',
    ]) {
      expect(text).toContain(key);
    }
  });

  it('runs a wildcard browse search on mount and shows populated counts', async () => {
    const wrapper = mountPage();
    await flushPromises();

    expect(mockFetch).toHaveBeenCalledTimes(1);
    const body = JSON.parse(mockFetch.mock.calls[0][1].body);
    expect(body.searches).toHaveLength(6);
    expect(body.searches.every((s: { q: string }) => s.q === '*')).toBe(true);

    // "search.all_search_prompt" no longer renders — the page opens already populated.
    expect(wrapper.text()).not.toContain('search.all_search_prompt');
    // A collection count from the fake browse response shows up in the sidebar.
    expect(wrapper.text()).toContain('5'); // documents
  });

  it('does not filter results when no collection checkbox is checked', async () => {
    const wrapper = mountPage();
    await flushPromises();

    // Nothing is checked by default, so every collection with hits is displayed.
    const checkboxes = wrapper.findAll('button[role="checkbox"]');
    expect(checkboxes.some(cb => cb.attributes('aria-checked') === 'true')).toBe(false);
  });

  it('carries the current search term into "view all" links and the page switcher', async () => {
    const wrapper = mountPage();
    await flushPromises();

    const input = wrapper.find('input[role="search"]');
    await input.setValue('studentai');
    await input.trigger('keydown.enter');
    await flushPromises();

    // documents has more hits than shown (found: 5, only 1 hit returned by the fake response)
    // and a global "view all" target, so at least one of its links must carry the search term —
    // the other is the page-switcher tab at the top, which has no `q` when nothing's searched.
    const documentsLinks = wrapper.findAll('a').filter(a => (a.attributes('href') ?? '').includes('/documents'));
    expect(documentsLinks.length).toBeGreaterThanOrEqual(2);
    expect(documentsLinks.every(a => (a.attributes('href') ?? '').includes('q=studentai'))).toBe(true);
  });
});
