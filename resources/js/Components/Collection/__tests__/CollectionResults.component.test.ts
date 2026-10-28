import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import CollectionResults from '../CollectionResults.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const items = [{ id: 'a', name: 'Projektorius' }, { id: 'b', name: 'Palapinė' }];

const mountResults = (view: 'cards' | 'rows') => mount(CollectionResults, {
  props: {
    collection: 'resources',
    view,
    items,
    itemKey: (item: { id: string }) => item.id,
    isLoading: false,
    isLoadingMore: false,
    hasSearched: true,
    hasMore: false,
    error: null,
    isFiltered: false,
    selectedKey: null,
    total: items.length,
  } as never,
  slots: {
    card: '<template #card="{ item }"><div class="card">{{ item.name }}</div></template>',
    row: '<template #row="{ item, index }"><div class="row" :data-index="index">{{ item.name }}</div></template>',
  },
});

describe('CollectionResults', () => {
  it('renders the built-in sticky actions last and leaves pinned data columns in flow', () => {
    const wrapper = mount(CollectionResults, {
      props: {
        collection: 'sticky-actions', view: 'table', items,
        itemKey: (item: { id: string }) => item.id,
        columns: [{ key: 'name', label: 'Pavadinimas', pinned: true }, { key: 'id', label: 'ID' }],
        rowActions: () => [{ key: 'open', label: 'Atidaryti', role: 'open', href: '/mano/resources/a' }],
        isLoading: false, isLoadingMore: false, hasSearched: true, hasMore: false,
        error: null, isFiltered: false, selectedKey: null, total: 2,
      } as never,
      slots: { cell: '<template #cell="{ item, column }">{{ item[column.key] }}</template>' },
    });
    const headers = wrapper.findAll('th');
    expect(headers.at(-1)?.text()).toBe('Veiksmai');
    expect(headers.at(-1)?.classes()).toEqual(expect.arrayContaining(['sticky', 'right-0']));
    expect(headers[0].classes()).not.toContain('sticky');
    const cells = wrapper.findAll('tbody tr')[0].findAll('td');
    expect(cells.at(-1)?.classes()).toEqual(expect.arrayContaining(['sticky', 'right-0', 'whitespace-nowrap']));
    expect(cells[0].classes()).not.toContain('sticky');
    expect(wrapper.get('[data-slot="collection-table"]').classes()).toContain('overflow-x-auto');
  });

  it('lays the items out as cards in the cards view', () => {
    const wrapper = mountResults('cards');

    expect(wrapper.find('[data-slot="collection-cards"]').classes()).toContain('xl:grid-cols-4');
    expect(wrapper.findAll('.card').map(card => card.text())).toEqual(['Projektorius', 'Palapinė']);
    expect(wrapper.find('.row').exists()).toBe(false);
  });

  it('keeps rows in the rows view', () => {
    const wrapper = mountResults('rows');

    expect(wrapper.find('[data-slot="collection-cards"]').exists()).toBe(false);
    expect(wrapper.findAll('.row')).toHaveLength(2);
    expect(wrapper.findAll('.row').map(row => row.attributes('data-index'))).toEqual(['0', '1']);
  });
});
