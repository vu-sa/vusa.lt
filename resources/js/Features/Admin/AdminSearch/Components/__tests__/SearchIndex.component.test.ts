import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { ref } from 'vue';

import SearchIndex from '@/Pages/Admin/Search/SearchIndex.vue';
import { normalizeHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';
import { commonStubs } from '@/tests/stubs';

const multiSearch = vi.fn();

vi.mock('@/Composables/useAdminSearch', () => ({
  useAdminSearch: () => ({
    initialize: vi.fn(() => Promise.resolve()),
    multiSearch,
    isRateLimited: ref(false),
    isSuperAdmin: ref(false),
    getCollectionTenantIds: () => [],
    getDirectInstitutionIds: () => [],
    hasCollectionAccess: () => true,
  }),
}));

const destinations = {
  meetings: { href: '/mano/meetings', queryKey: 'q' },
  agendaItems: { href: '/mano/search?tab=agenda-items', queryKey: 'q' },
  institutions: { href: '/mano/institutions', queryKey: 'search' },
  resources: { href: null, queryKey: 'q' },
  duties: { href: '/mano/duties', queryKey: 'search' },
  documents: { href: '/mano/documents', queryKey: 'search' },
  news: { href: '/mano/news', queryKey: 'search' },
  pages: { href: '/mano/pages', queryKey: 'search' },
  calendar: { href: '/mano/calendar', queryKey: 'search' },
  users: { href: '/mano/users', queryKey: 'search' },
};

const withResults = () => {
  const results = createEmptyMultiSearchResults();
  results.meetings = [{ id: '1', title: 'Senato posėdis', institution_name_lt: 'VU Senatas', start_time: 1_700_000_000, completion_status: 'complete' }] as never;
  results.institutions = [{ id: '2', name: 'VU Senatas' }] as never;
  results.counts.meetings = 30;
  results.counts.institutions = 1;
  return results;
};

const stubs = {
  ...commonStubs,
  SearchCollectionPanel: { name: 'SearchCollectionPanel', template: '<div data-testid="collection-panel" />' },
};

async function mountPage(url = '/') {
  window.history.replaceState({}, '', url);
  const wrapper = mount(SearchIndex, { props: { destinations }, global: { stubs } });
  await vi.advanceTimersByTimeAsync(400);
  await flushPromises();

  return wrapper;
}

beforeEach(() => {
  vi.useFakeTimers();
  multiSearch.mockReset();
  multiSearch.mockResolvedValue(withResults());
  vi.stubGlobal('route', (name: string, params?: Record<string, string>) =>
    `/mano/${name}${params && Object.keys(params).length ? `?${new URLSearchParams(params).toString()}` : ''}`);
});

describe('cross-entity search', () => {
  it('shows one group per entity that has hits, in a fixed order, and hides the rest', async () => {
    const wrapper = await mountPage('/?q=senatas');

    const groups = wrapper.findAll('[data-slot="search-result-group"]').map(group => group.attributes('data-collection'));
    expect(groups).toEqual(['meetings', 'institutions']);
  });

  it('sends each group to its own list with the query in the key that list reads', async () => {
    const wrapper = await mountPage('/?q=senatas');

    const links = wrapper.findAll('[data-slot="search-result-group"] header a').map(link => link.attributes('href'));
    expect(links).toEqual(['/mano/meetings?q=senatas', '/mano/institutions?search=senatas']);
  });

  it('asks Typesense for a handful of rows per entity, not a page of them', async () => {
    await mountPage('/?q=senatas');

    expect(multiSearch).toHaveBeenCalledWith('senatas', expect.objectContaining({ meetingsLimit: 5, usersLimit: 5 }));
  });

  it('says so when nothing matches, and offers to clear the query', async () => {
    multiSearch.mockResolvedValue(createEmptyMultiSearchResults());
    const wrapper = await mountPage('/?q=nieko');

    expect(wrapper.find('[data-slot="search-result-group"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="empty-state"]').exists()).toBe(true);
  });

  it('keeps a single-collection view for the tabs that have no page of their own', async () => {
    const wrapper = await mountPage('/?tab=agenda-items&q=x');

    expect(wrapper.find('[data-testid="collection-panel"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="search-result-group"]').exists()).toBe(false);
    expect(multiSearch).not.toHaveBeenCalled();
  });

  it('ignores a tab it does not know and shows the grouped results', async () => {
    const wrapper = await mountPage('/?tab=nonsense&q=senatas');

    expect(wrapper.find('[data-testid="collection-panel"]').exists()).toBe(false);
    expect(wrapper.findAll('[data-slot="search-result-group"]').length).toBeGreaterThan(0);
  });
});

// normalizeHit is exercised for real above; this keeps the import honest if the fixtures drift.
it('builds hits the groups can link', () => {
  expect(normalizeHit('meetings', { id: '1', title: 'x' }).viewHref).toContain('meetings.show');
});
