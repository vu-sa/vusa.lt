import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';

import SearchAllPanel from '../SearchAllPanel.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

import type { MultiSearchResults } from '@/Shared/Search/types';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

/** Captures the ordered `hits` prop SearchAllPanel feeds into the split view. */
const SplitViewStub = defineComponent({
  props: { hits: { type: Array, default: () => [] } },
  render() {
    return h('div', (this.hits as NormalizedSearchHit[]).map(hit => h('span', { class: 'hit' }, hit.collection)));
  },
});

function makeResults(partial: Partial<Omit<MultiSearchResults, 'counts'>>): MultiSearchResults {
  return {
    ...createEmptyMultiSearchResults(),
    ...partial,
    counts: {
      meetings: 1, agendaItems: 0, news: 0, pages: 0, calendar: 0,
      institutions: 1, documents: 0, resources: 0, duties: 0, users: 0,
    },
  };
}

const meeting = { id: 'm1', title: 'Studijų kolegijos posėdis', start_time: 1_700_000_000, _text_match: 50 } as never;
const institution = { id: 'i1', name_lt: 'Studijų kolegija', _text_match: 130 } as never;

function mountPanel(query: string) {
  return mount(SearchAllPanel, {
    props: {
      results: makeResults({ meetings: [meeting], institutions: [institution] }),
      query,
      isSearching: false,
      hasSearched: true,
      error: null,
    },
    global: { stubs: { SearchSplitView: SplitViewStub } },
  });
}

describe('SearchAllPanel', () => {
  it('orders the matching institution above the meeting when querying', () => {
    const wrapper = mountPanel('Studijų kolegija');

    const order = wrapper.findAll('.hit').map(n => n.text());
    expect(order).toEqual(['institutions', 'meetings']);
  });

  it('keeps the static collection order when browsing without a query', () => {
    const wrapper = mountPanel('');

    const order = wrapper.findAll('.hit').map(n => n.text());
    expect(order).toEqual(['meetings', 'institutions']);
  });
});
