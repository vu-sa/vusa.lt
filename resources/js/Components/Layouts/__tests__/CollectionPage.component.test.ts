import { flushPromises, mount } from '@vue/test-utils';
import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import CollectionPage from '../CollectionPage.vue';

import type { CollectionSource } from '@/Composables/useCollectionSource';
import { commonStubs } from '@/tests/stubs';

const media = vi.hoisted(() => ({ md: true, xl: false }));

vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal<Record<string, unknown>>();
  const { computed: vueComputed } = await import('vue');

  return {
    ...actual,
    useMediaQuery: (query: string) => vueComputed(() => (query.includes('768') ? media.md : media.xl)),
  };
});

interface Item {
  id: string;
  name: string;
}

function makeSource(overrides: Partial<Record<keyof CollectionSource<Item>, unknown>> = {}) {
  const spies = {
    search: vi.fn(),
    toggleFilter: vi.fn(),
    setFilter: vi.fn(),
    clearFilters: vi.fn(),
    clearChip: vi.fn(),
    setSortBy: vi.fn(),
    loadMore: vi.fn(),
    refresh: vi.fn(),
  };

  const source = {
    items: ref<Item[]>([{ id: 'a', name: 'Pirmas' }, { id: 'b', name: 'Antras' }]),
    total: ref(2),
    isLoading: ref(false),
    isLoadingMore: ref(false),
    hasMore: ref(false),
    hasSearched: ref(true),
    error: ref<string | null>(null),
    query: ref(''),
    filters: ref({}),
    facets: computed(() => []),
    chips: computed(() => []),
    activeFilterCount: ref(0),
    sortBy: ref('start_time:desc'),
    sortOptions: computed(() => [
      { value: 'start_time:desc', label: 'Naujausi pirmi' },
      { value: 'start_time:asc', label: 'Seniausi pirmi' },
    ]),
    ...spies,
    ...overrides,
  } as unknown as CollectionSource<Item>;

  return { source, spies };
}

function mountPage(source: CollectionSource<Item>, extra: Record<string, unknown> = {}, slots: Record<string, string> = {}) {
  return mount(CollectionPage as never, {
    props: {
      source,
      collection: 'test',
      entityType: 'meeting',
      eyebrow: 'ViSAK · Posėdžiai',
      title: 'Posėdžiai',
      defaultView: 'rows',
      itemKey: (item: Item) => item.id,
      columns: [{ key: 'name', label: 'Pavadinimas' }],
      ...extra,
    },
    slots: {
      row: '<template #row="{ item }"><a data-collection-open :href="`/x/${item.id}`" class="row">{{ item.name }}</a></template>',
      cell: '<template #cell="{ item }"><span class="cell">{{ item.name }}</span></template>',
      preview: '<template #preview="{ item }"><p class="preview">Peržiūra: {{ item.name }}</p></template>',
      ...slots,
    },
    global: { stubs: { ...commonStubs, Teleport: true } },
    attachTo: document.body,
  });
}

beforeEach(() => {
  media.md = true;
  media.xl = false;
  window.history.replaceState({}, '', '/mano/meetings');
  localStorage.clear();
  sessionStorage.clear();
});

describe('CollectionPage', () => {
  it('states where the person is and draws the entity identity tile', () => {
    const { source } = makeSource();
    const wrapper = mountPage(source);

    expect(wrapper.text()).toContain('ViSAK · Posėdžiai');
    expect(wrapper.find('h1').text()).toBe('Posėdžiai');
    expect(wrapper.find('[data-slot="collection-title-band"] [aria-hidden="true"]').exists()).toBe(true);
  });

  it('renders one row per item through the row slot', () => {
    const { source } = makeSource();
    const wrapper = mountPage(source);

    expect(wrapper.findAll('.row').map(row => row.text())).toEqual(['Pirmas', 'Antras']);
  });

  it('shows a skeleton until the first request has come back, not an empty state', () => {
    const { source } = makeSource({ items: ref([]), hasSearched: ref(false), total: ref(0) });
    const wrapper = mountPage(source, {}, { empty: '<template #empty><p class="custom-empty" /></template>' });

    expect(wrapper.find('.custom-empty').exists()).toBe(false);
    expect(wrapper.find('[data-slot="collection-skeleton"]').exists()).toBe(true);
    expect(wrapper.find('.row').exists()).toBe(false);
  });

  it('tells a truly empty collection apart from a filtered one that found nothing', async () => {
    const empty = makeSource({ items: ref([]), total: ref(0) });
    const first = mountPage(empty.source, {}, { empty: '<template #empty><p class="custom-empty" /></template>' });
    expect(first.find('.custom-empty').exists()).toBe(true);

    const filtered = makeSource({ items: ref([]), total: ref(0), query: ref('nėra tokio') });
    const second = mountPage(filtered.source, {}, { empty: '<template #empty><p class="custom-empty" /></template>' });
    expect(second.find('.custom-empty').exists()).toBe(false);

    const clear = second.findAll('button').find(button => button.text().includes('tables.clear_filters'));
    await clear?.trigger('click');
    expect(filtered.spies.clearFilters).toHaveBeenCalled();
    expect(filtered.spies.search).toHaveBeenCalledWith('', true);
  });

  it('offers "Rodyti daugiau" only while there is more, and asks the source for it', async () => {
    const { source, spies } = makeSource({ hasMore: ref(true) });
    const wrapper = mountPage(source);

    const more = wrapper.findAll('button').find(button => button.text().includes('Rodyti daugiau'));
    expect(more).toBeDefined();

    await more!.trigger('click');
    expect(spies.loadMore).toHaveBeenCalledTimes(1);

    const done = mountPage(makeSource({ hasMore: ref(false) }).source);
    expect(done.findAll('button').some(button => button.text().includes('Rodyti daugiau'))).toBe(false);
  });

  it('lists active filters as chips that can be removed one by one or all at once', async () => {
    const { source, spies } = makeSource({
      chips: computed(() => [{ id: 'year:2026', label: 'Metai: 2026' }, { id: 'completion_status:incomplete', label: 'Būsena: Neužpildyta' }]),
      activeFilterCount: ref(2),
    });
    const wrapper = mountPage(source);

    expect(wrapper.text()).toContain('Metai: 2026');
    expect(wrapper.text()).toContain('Rasta :count');

    await wrapper.findAll('[data-slot="collection-active-chips"] button')[0].trigger('click');
    expect(spies.clearChip).toHaveBeenCalledWith('year:2026');

    const clearAll = wrapper.findAll('[data-slot="collection-active-chips"] button').find(button => button.text().includes('Išvalyti visus'));
    await clearAll!.trigger('click');
    expect(spies.clearFilters).toHaveBeenCalled();
  });

  it('shows the filter count on the Filtrai button and toggles the inline filter row from md', async () => {
    const facet = { field: 'year', label: 'Metai', type: 'year-pills', values: [{ value: '2026', label: '2026', count: 4, isSelected: false }] };
    const { source } = makeSource({ facets: computed(() => [facet]), activeFilterCount: ref(3) });
    const wrapper = mountPage(source);

    const filters = wrapper.findAll('button').find(button => button.text().includes('Filtrai'));
    expect(filters!.text()).toContain('3');
    expect(wrapper.find('[data-slot="collection-filter-bar"]').exists()).toBe(false);

    await filters!.trigger('click');
    expect(wrapper.find('[data-slot="collection-filter-bar"]').exists()).toBe(true);
  });

  it('sends typed text to the source and keeps the attribute the `/` shortcut looks for', async () => {
    const { source, spies } = makeSource();
    const wrapper = mountPage(source);

    const input = wrapper.find('input[data-admin-collection-search]');
    expect(input.exists()).toBe(true);

    await input.setValue('senatas');
    expect(spies.search).toHaveBeenCalledWith('senatas', undefined);
  });

  it('reports quick filter clicks and reflects which are active', async () => {
    const { source } = makeSource();
    const wrapper = mountPage(source, {
      quickFilters: [
        { id: 'mine', label: 'Mano institucijos', active: true },
        { id: 'this_year', label: 'Šie metai', active: false },
      ],
    });

    const buttons = wrapper.findAll('[data-slot="collection-quick-filters"] button');
    expect(buttons.map(button => button.attributes('aria-pressed'))).toEqual(['true', 'false']);

    await buttons[1].trigger('click');
    expect(wrapper.emitted('quickFilter')).toEqual([['this_year']]);
  });

  describe('views', () => {
    it('offers rows and table at md, and adds the preview pane only from xl', () => {
      const { source } = makeSource();
      expect(mountPage(source).findAll('[role="radio"]').map(radio => radio.attributes('aria-label'))).toEqual(['Eilutės', 'Lentelė']);

      media.xl = true;
      expect(mountPage(source).findAll('[role="radio"]').map(radio => radio.attributes('aria-label'))).toEqual(['Eilutės', 'Lentelė', 'Peržiūra']);
    });

    it('is rows only on a phone, with no toggle to choose from', () => {
      media.md = false;
      const wrapper = mountPage(makeSource().source);

      expect(wrapper.find('[data-slot="collection-view-toggle"]').exists()).toBe(false);
      expect(wrapper.find('.row').exists()).toBe(true);
    });

    it('switches to the table, remembers the choice and puts it in the URL', async () => {
      const wrapper = mountPage(makeSource().source);

      await wrapper.findAll('[role="radio"]').find(radio => radio.attributes('aria-label') === 'Lentelė')!.trigger('click');

      expect(wrapper.findAll('table td .cell').map(cell => cell.text())).toEqual(['Pirmas', 'Antras']);
      expect(wrapper.find('th').text()).toBe('Pavadinimas');
      expect(new URLSearchParams(window.location.search).get('view')).toBe('table');
      expect(localStorage.getItem('admin-collection-view:1:test')).toBe('table');
    });

    it('lets the URL win over the remembered view (U1)', () => {
      window.history.replaceState({}, '', '/mano/meetings?view=table');
      const wrapper = mountPage(makeSource().source);

      expect(wrapper.find('table').exists()).toBe(true);
    });

    it('drops the default view from the URL instead of pinning it there', async () => {
      window.history.replaceState({}, '', '/mano/meetings?view=table');
      const wrapper = mountPage(makeSource().source);

      await wrapper.findAll('[role="radio"]').find(radio => radio.attributes('aria-label') === 'Eilutės')!.trigger('click');

      expect(new URLSearchParams(window.location.search).has('view')).toBe(false);
    });
  });

  describe('preview pane', () => {
    beforeEach(() => {
      media.xl = true;
      window.history.replaceState({}, '', '/mano/meetings?view=preview');
    });

    it('opens on the first item and records it in the URL', async () => {
      const wrapper = mountPage(makeSource().source);
      await flushPromises();

      expect(wrapper.find('.preview').text()).toBe('Peržiūra: Pirmas');
      expect(new URLSearchParams(window.location.search).get('item')).toBe('a');
    });

    it('restores the item named in the URL', async () => {
      window.history.replaceState({}, '', '/mano/meetings?view=preview&item=b');
      const wrapper = mountPage(makeSource().source);
      await flushPromises();

      expect(wrapper.find('.preview').text()).toBe('Peržiūra: Antras');
    });

    it('selects on a plain click of a row link instead of navigating', async () => {
      const wrapper = mountPage(makeSource().source);
      await flushPromises();

      const event = new MouseEvent('click', { bubbles: true, cancelable: true, button: 0 });
      wrapper.findAll('a.row')[1].element.dispatchEvent(event);
      await flushPromises();

      expect(event.defaultPrevented).toBe(true);
      expect(wrapper.find('.preview').text()).toBe('Peržiūra: Antras');
    });

    it('leaves modified clicks to the browser so a row can still open in a new tab', async () => {
      const wrapper = mountPage(makeSource().source);
      await flushPromises();

      const event = new MouseEvent('click', { bubbles: true, cancelable: true, button: 0, ctrlKey: true });
      wrapper.findAll('a.row')[1].element.dispatchEvent(event);
      await flushPromises();

      expect(event.defaultPrevented).toBe(false);
      expect(wrapper.find('.preview').text()).toBe('Peržiūra: Pirmas');
    });
  });

  it('pins recently changed rows ahead of the results, once', () => {
    const { source } = makeSource();
    const wrapper = mountPage(source, {
      pinnedItems: [{ id: 'z', name: 'Ką tik sukurtas' }, { id: 'a', name: 'Pirmas' }],
    });

    // "a" is already in the results, so only the genuinely new one is pinned.
    expect(wrapper.findAll('.row').map(row => row.text())).toEqual(['Ką tik sukurtas', 'Pirmas', 'Antras']);
  });

  it('does not claim "Rasta 0" next to an error — the count is unknown, not zero', () => {
    const { source } = makeSource({ error: ref('Paieška nepavyko.'), items: ref([]), total: ref(0) });

    expect(mountPage(source).find('[data-slot="collection-active-chips"]').text()).not.toContain('Rasta');
  });

  it('shows the source error with a way to retry', async () => {
    const { source, spies } = makeSource({ error: ref('Paieška nepavyko. Bandykite dar kartą.') });
    const wrapper = mountPage(source);

    expect(wrapper.find('[role="alert"]').text()).toContain('Paieška nepavyko');

    await wrapper.find('[role="alert"] button').trigger('click');
    expect(spies.refresh).toHaveBeenCalled();
  });

  it('restores the scroll position saved when the person left', async () => {
    const scroller = document.createElement('main');
    document.body.appendChild(scroller);
    scroller.scrollTo = vi.fn() as never;
    sessionStorage.setItem('admin-collection-scroll:/mano/meetings', '480');

    const { source } = makeSource({ hasSearched: ref(false) });
    const wrapper = mount(CollectionPage as never, {
      props: { source, collection: 'test', entityType: 'meeting', eyebrow: 'e', title: 't', defaultView: 'rows', itemKey: (item: Item) => item.id },
      slots: { row: '<template #row="{ item }"><span>{{ item.name }}</span></template>' },
      global: { stubs: { ...commonStubs, Teleport: true } },
      attachTo: scroller,
    });

    (source.hasSearched as unknown as { value: boolean }).value = true;
    await flushPromises();

    expect(scroller.scrollTo).toHaveBeenCalledWith({ top: 480 });
    wrapper.unmount();
  });
});
