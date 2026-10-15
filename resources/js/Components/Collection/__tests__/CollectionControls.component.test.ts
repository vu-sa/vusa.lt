import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CollectionControlRow from '../CollectionControlRow.vue';
import CollectionFilterBar from '../CollectionFilterBar.vue';
import CollectionPrimaryCell from '../CollectionPrimaryCell.vue';
import CollectionResults from '../CollectionResults.vue';
import CollectionViewToggle from '../CollectionViewToggle.vue';

import { commonStubs } from '@/tests/stubs';

describe('collection controls', () => {
  it('fills the Filtrai button only while the filters show, and lets the count tell set filters', () => {
    const toggleOf = (filtersOpen: boolean, activeFilterCount: number) => mount(CollectionControlRow, {
      props: { query: '', placeholder: 'Ieškoti', hasFilters: true, filtersOpen, activeFilterCount },
    }).get('[data-slot="collection-filters-toggle"]');

    const closedWithFilters = toggleOf(false, 2);
    expect(closedWithFilters.classes()).not.toContain('border-brand');
    expect(closedWithFilters.attributes('aria-label')).toBe('Filtrai');
    expect(closedWithFilters.text()).toContain('2');

    expect(toggleOf(true, 0).classes()).toContain('border-brand');
  });

  it('clamps a title to two lines when requested', () => {
    const wrapped = mount(CollectionPrimaryCell, {
      props: { title: 'A long page title', titleLines: 2 },
      global: { stubs: commonStubs },
    });
    const defaultCell = mount(CollectionPrimaryCell, {
      props: { title: 'A long page title' },
      global: { stubs: commonStubs },
    });

    expect(wrapped.find('p').classes()).toContain('line-clamp-2');
    expect(defaultCell.find('p').classes()).toContain('truncate');
  });

  it('keeps the deleted filter inside the filter band and emits its toggle', async () => {
    const wrapper = mount(CollectionFilterBar, {
      props: {
        facets: [],
        open: true,
        sheetOpen: false,
        isAtLeastMd: true,
        activeCount: 0,
        trash: { count: 2, active: false },
      },
      global: { stubs: commonStubs },
    });

    const deleted = wrapper.find('[data-slot="collection-filter-bar"] button');
    expect(deleted.text()).toContain('Ištrinti');
    expect(deleted.attributes('aria-pressed')).toBe('false');

    await deleted.trigger('click');

    expect(wrapper.emitted('toggleTrash')).toHaveLength(1);
  });

  it('shows quick filters first in the phone sheet and forwards their choices', async () => {
    const wrapper = mount(CollectionFilterBar, {
      props: {
        facets: [],
        quickFilters: [{ id: 'mine', label: 'Mano', active: true }],
        open: false,
        sheetOpen: true,
        isAtLeastMd: false,
        activeCount: 0,
      },
      global: {
        stubs: {
          ...commonStubs,
          Sheet: { template: '<div><slot /></div>' },
          SheetContent: { template: '<div><slot /></div>' },
        },
      },
    });

    const quickFilters = wrapper.get('[data-slot="collection-quick-filters"]');
    expect(quickFilters.find('button').attributes('aria-pressed')).toBe('true');

    await quickFilters.find('button').trigger('click');
    expect(wrapper.emitted('quickFilter')).toEqual([['mine']]);
  });

  it('renders view choices as labelled icons', () => {
    const wrapper = mount(CollectionViewToggle, {
      props: { modelValue: 'table', views: ['rows', 'table'] },
    });

    expect(wrapper.findAll('[role="radio"]')).toHaveLength(2);
    expect(wrapper.find('[aria-label="Lentelė"]').exists()).toBe(true);
    expect(wrapper.text()).toBe('');
  });

  it('places the result count and full-width sort select beside columns', async () => {
    const wrapper = mount(CollectionResults, {
      props: {
        collection: 'control-test',
        view: 'table',
        items: [],
        itemKey: (item: { id: string }) => item.id,
        columns: [
          { key: 'title', label: 'Pavadinimas' },
          { key: 'tenant', label: 'Padalinys' },
          { key: 'actions', label: 'Veiksmai', pinned: true },
        ],
        isLoading: false,
        isLoadingMore: false,
        hasSearched: true,
        hasMore: false,
        error: null,
        isFiltered: true,
        selectedKey: null,
        sortBy: 'title:asc',
        sortOptions: [
          { value: 'title:asc', label: 'A–Z' },
          { value: 'title:desc', label: 'Z–A' },
        ],
        total: 0,
      },
      global: { stubs: commonStubs },
    });

    const toolbar = wrapper.find('[data-slot="collection-results-toolbar"]');
    expect(toolbar.text()).toContain('Rasta 0');
    expect(toolbar.text()).toContain('Stulpeliai');

    const select = toolbar.find('select');
    expect(select.classes()).toContain('inset-0');
    await select.setValue('title:desc');

    expect(wrapper.emitted('sort')?.[0]).toEqual(['title:desc']);
  });
});
