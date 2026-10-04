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

describe('admin palette relevance', () => {
  it.each([
    { workspace: 'visak', query: 'Austėja', first: 'Austėja Petrauskaitė', usersScore: '90', dutiesScore: '80' },
    { workspace: 'organizacija', query: 'Prezidentė', first: 'Prezidentas', usersScore: '80', dutiesScore: '90' },
  ])('keeps the most relevant record first from $workspace for $query', async ({ workspace, query, first, usersScore, dutiesScore }) => {
    vi.mocked(useCommandActions).mockReturnValue({
      filterActions: () => [], activeWorkspace: ref({ key: workspace }),
      workspaceKeyForEntity: (entity: string) => entity === 'user' ? 'organizacija' : 'visak',
    } as unknown as ReturnType<typeof useCommandActions>);
    const results = createEmptyMultiSearchResults();
    results.users = [{ id: 'u1', name: 'Austėja Petrauskaitė', _text_match: usersScore }] as typeof results.users;
    results.duties = [{ id: 'd1', name_lt: 'Prezidentas', _text_match: dutiesScore }] as typeof results.duties;
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
