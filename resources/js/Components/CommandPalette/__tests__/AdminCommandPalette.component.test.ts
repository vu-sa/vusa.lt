import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import { Plus } from '@lucide/vue';
import { router } from '@inertiajs/vue3';

import { apiRequest } from '@/Composables/apiRequest';
import { useAdminSearch } from '@/Composables/useAdminSearch';
import { useCommandPalette } from '@/Composables/useCommandPalette';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import { loadWikiLinkTargets } from '@/Search/admin/useWikiLinkTargets';
import { KnowledgeItemKind } from '@/Types/enums';

import { useCommandActions } from '../useCommandActions';
import AdminCommandPalette from '../AdminCommandPalette.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/apiRequest', () => ({ apiRequest: vi.fn() }));
vi.mock('@/Composables/useAdminSearch', () => ({ useAdminSearch: vi.fn() }));
vi.mock('@/Composables/useCommandPalette', () => ({ useCommandPalette: vi.fn() }));
vi.mock('@/Composables/useUIPreferences', () => ({ useUIPreferences: vi.fn() }));
vi.mock('../useCommandActions', () => ({ useCommandActions: vi.fn() }));
vi.mock('@/Search/admin/useWikiLinkTargets', () => ({ loadWikiLinkTargets: vi.fn() }));

beforeEach(() => {
  vi.useFakeTimers();
  vi.mocked(apiRequest).mockResolvedValue({ data: [] } as Awaited<ReturnType<typeof apiRequest>>);
  vi.mocked(useCommandPalette).mockReturnValue({ isOpen: ref(true), query: ref(''), recentItems: ref([]), close: vi.fn() } as ReturnType<typeof useCommandPalette>);
  vi.mocked(useUIPreferences).mockReturnValue({ isPinned: vi.fn(), togglePin: vi.fn(), pinnedPages: ref([]) } as unknown as ReturnType<typeof useUIPreferences>);
  vi.mocked(loadWikiLinkTargets).mockResolvedValue([]);
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
    {
      name: 'an agenda item returned under its backend collection ID',
      workspace: 'visak', query: 'Punktas', first: 'Punktas',
      users: [], duties: [], news: [],
      agendaItems: [{ id: 'a1', title: 'Punktas' }],
    },
  ])('ranks $name', async ({ workspace, query, first, users, duties, news, agendaItems = [] }) => {
    vi.mocked(useCommandActions).mockReturnValue({
      filterActions: () => [], activeWorkspace: ref({ key: workspace }),
    } as unknown as ReturnType<typeof useCommandActions>);
    const results = {
      users: { hits: users, found: users.length },
      duties: { hits: duties, found: duties.length },
      news: { hits: news, found: news.length },
      agenda_items: { hits: agendaItems, found: agendaItems.length },
    };
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

describe('admin palette Wiki results', () => {
  it('lists matching Wiki articles from the cached index', async () => {
    vi.mocked(loadWikiLinkTargets).mockResolvedValue([
      { id: 'kvorumas', kind: KnowledgeItemKind.Term, title: 'Kvorumas', abbreviation: '', summary: '', summaryHtml: '', url: '/mano/knowledge/items/kvorumas', thumbnailUrl: null, sectionId: null, countsInProgress: true, updatedAt: null },
      { id: 'senatas', kind: KnowledgeItemKind.Term, title: 'Senatas', abbreviation: '', summary: '', summaryHtml: '', url: '/mano/knowledge/items/senatas', thumbnailUrl: null, sectionId: null, countsInProgress: true, updatedAt: null },
    ]);
    vi.mocked(useCommandActions).mockReturnValue({
      filterActions: () => [], activeWorkspace: ref({ key: 'wiki' }),
    } as unknown as ReturnType<typeof useCommandActions>);
    vi.mocked(useAdminSearch).mockReturnValue({
      multiSearch: vi.fn().mockResolvedValue({}), initialize: vi.fn(), isRateLimited: ref(false), getDirectInstitutionIds: () => [],
    } as unknown as ReturnType<typeof useAdminSearch>);
    const wrapper = mount(AdminCommandPalette, {
      global: { stubs: {
        PaletteDialog: { template: '<div><slot /></div>' },
        CommandList: { template: '<div><slot /></div>' },
        PaletteGroup: { template: '<div><slot /></div>' },
        CommandItem: { template: '<div><slot /></div>' },
        PaletteRow: { props: ['title', 'value'], template: '<div data-testid="palette-row" :data-value="value">{{ title }}</div>' },
      } },
    });
    await flushPromises();
    await wrapper.get('input').setValue('kvor');
    await vi.advanceTimersByTimeAsync(301);
    await flushPromises();
    expect(wrapper.findAll('[data-testid="palette-row"]').filter(row => row.attributes('data-value')?.startsWith('wiki-')).map(row => row.text())).toEqual(['Kvorumas']);
    wrapper.unmount();
  });
});

it('shows personal records on open and every matching create command while search loads', async () => {
  const href = '/mano/institutions/1';
  vi.mocked(apiRequest).mockResolvedValue({
    data: [{ id: '1', kind: 'followed', title: 'Universiteto senatas', context: null, href }],
  } as Awaited<ReturnType<typeof apiRequest>>);
  vi.mocked(useAdminSearch).mockReturnValue({
    multiSearch: vi.fn().mockResolvedValue({}), initialize: vi.fn(), isRateLimited: ref(false), getDirectInstitutionIds: () => [],
  } as unknown as ReturnType<typeof useAdminSearch>);
  const commands = Array.from({ length: 8 }, (_, index) => ({
    id: `create-${index}`, label: `New record ${index}`, category: 'create' as const, keywords: [], icon: Plus, action: vi.fn(),
  }));
  const radio = { id: 'radio', label: 'START FM', category: 'action' as const, keywords: [], icon: Plus, action: vi.fn() };
  vi.mocked(useCommandActions).mockReturnValue({
    filterActions: (term: string) => term === 'radio' ? [radio] : [...commands, ...(!term ? [radio] : [])],
    activeWorkspace: ref(undefined),
  } as unknown as ReturnType<typeof useCommandActions>);
  const wrapper = mount(AdminCommandPalette, {
    global: { stubs: {
      PaletteDialog: { template: '<div><slot /></div>' },
      CommandList: { template: '<div><slot /></div>' },
      PaletteGroup: { props: ['heading'], template: '<section :data-group="heading"><slot /></section>' },
      CommandItem: { emits: ['select'], template: `<button @click="$emit('select')"><slot /></button>` },
    } },
  });

  await flushPromises();
  expect(wrapper.find('[data-group="shell.palette.actions"]').exists()).toBe(false);
  expect(wrapper.find('[data-group="shell.palette.create"]').exists()).toBe(false);
  const personal = wrapper.get('[data-group="shell.palette.personal"] button');
  expect(personal.text()).toContain('Universiteto senatas');
  await personal.trigger('click');
  expect(router.visit).toHaveBeenCalledWith(href);

  await wrapper.get('input').setValue('new');
  expect(wrapper.find('[data-group="shell.palette.personal"]').exists()).toBe(false);
  expect(wrapper.findAll('[data-slot="create-command-icon"]')).toHaveLength(8);
  await wrapper.get('[data-group="shell.palette.create"] button').trigger('click');
  expect(commands[0]?.action).toHaveBeenCalledOnce();

  await wrapper.get('input').setValue('radio');
  await wrapper.get('[data-group="shell.palette.actions"] button').trigger('click');
  expect(radio.action).toHaveBeenCalledOnce();

  await wrapper.get('input').setValue('   ');
  expect(wrapper.find('[data-group="shell.palette.create"]').exists()).toBe(false);
  wrapper.unmount();
});
