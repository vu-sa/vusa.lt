import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';

import AdminCommandPalette from '../AdminCommandPalette.vue';
import { useCommandActions } from '../useCommandActions';

import { useAdminSearch } from '@/Composables/useAdminSearch';
import { useCommandPalette } from '@/Composables/useCommandPalette';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

vi.mock('@/Composables/useAdminSearch', () => ({ useAdminSearch: vi.fn() }));
vi.mock('@/Composables/useCommandPalette', () => ({ useCommandPalette: vi.fn() }));
vi.mock('@/Composables/useUIPreferences', () => ({ useUIPreferences: vi.fn() }));
vi.mock('../useCommandActions', () => ({ useCommandActions: vi.fn() }));

beforeEach(() => {
  vi.useFakeTimers();
  vi.mocked(useCommandPalette).mockReturnValue({ isOpen: ref(true), query: ref(''), recentItems: ref([]), close: vi.fn() } as ReturnType<typeof useCommandPalette>);
  vi.mocked(useUIPreferences).mockReturnValue({ isPinned: vi.fn(), togglePin: vi.fn(), pinnedPages: ref([]) } as unknown as ReturnType<typeof useUIPreferences>);
});

afterEach(() => vi.useRealTimers());

const titleHit = (title: string) => [{ field: 'name', segments: [{ text: title, matched: true }] }];

describe('admin palette relevance', () => {
  it.each([
    {
      name: 'a person matched by name over a duty matched through its holder',
      workspace: 'visak', query: 'Austėja', first: 'Austėja Petrauskaitė',
      users: [{ id: 'u1', name: 'Austėja Petrauskaitė', _searchTitleMatches: titleHit('Austėja Petrauskaitė'), _text_match: '80' }],
      duties: [{ id: 'd1', name_lt: 'Prezidentas', _searchFieldMatches: { current_user_names: titleHit('Austėja Petrauskaitė')[0] }, _text_match: '90' }],
      news: [],
    },
    {
      name: 'a duty matched by name over a person matched through their duty',
      workspace: 'organizacija', query: 'Prezidentė', first: 'Prezidentas',
      users: [{ id: 'u1', name: 'Austėja Petrauskaitė', _searchFieldMatches: { current_duty_names: titleHit('Prezidentė')[0] }, _text_match: '90' }],
      duties: [{ id: 'd1', name_lt: 'Prezidentas', _searchTitleMatches: [{ field: 'name_lt', segments: [{ text: 'Prezidentas', matched: true }] }], _text_match: '80' }],
      news: [],
    },
    {
      // Typesense scores the story higher only because its title and body both matched.
      name: 'a person over a news story with the name in its title',
      workspace: 'visak', query: 'Uršul', first: 'Uršulė Barkauskaitė',
      users: [{ id: 'u1', name: 'Uršulė Barkauskaitė', _searchTitleMatches: titleHit('Uršulė Barkauskaitė'), _text_match: '610246614105227017' }],
      duties: [],
      news: [{ id: 'n1', title: 'Išrinkta Uršulė Barkauskaitė', _searchTitleMatches: [{ field: 'title', segments: [{ text: 'Išrinkta Uršulė Barkauskaitė', matched: true }] }], _text_match: '610246614105227019' }],
    },
  ])('ranks $name', async ({ workspace, query, first, users, duties, news }) => {
    vi.mocked(useCommandActions).mockReturnValue({
      filterActions: () => [], activeWorkspace: ref({ key: workspace }),
      workspaceKeyForEntity: (entity: string) => entity === 'user' ? 'organizacija' : 'visak',
    } as unknown as ReturnType<typeof useCommandActions>);
    const results = createEmptyMultiSearchResults();
    results.users = users as unknown as typeof results.users;
    results.duties = duties as unknown as typeof results.duties;
    results.news = news as unknown as typeof results.news;
    vi.mocked(useAdminSearch).mockReturnValue({
      multiSearch: vi.fn().mockResolvedValue(results), initialize: vi.fn(), isRateLimited: ref(false), getDirectInstitutionIds: () => [],
    } as unknown as ReturnType<typeof useAdminSearch>);
    const wrapper = mount(AdminCommandPalette, {
      global: { stubs: {
        PaletteDialog: { template: '<div><slot /></div>' },
        CommandList: { template: '<div><slot /></div>' },
        CommandGroup: { template: '<div><slot /></div>' },
        CommandItem: { template: '<div><slot /></div>' },
        SearchHitRow: { props: ['hit'], template: '<div data-testid="result">{{ hit.title }}</div>' },
      } },
    });
    await wrapper.get('input').setValue(query);
    await vi.advanceTimersByTimeAsync(301);
    await flushPromises();
    expect(wrapper.findAll('[data-testid="result"]')[0].text()).toBe(first);
    wrapper.unmount();
  });
});
