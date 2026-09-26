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
    row: '<template #row="{ item }"><div class="row">{{ item.name }}</div></template>',
  },
});

describe('CollectionResults', () => {
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
  });
});
