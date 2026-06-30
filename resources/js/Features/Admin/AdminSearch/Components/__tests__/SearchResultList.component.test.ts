import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { h } from 'vue';

import SearchResultList from '../SearchResultList.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

const DummyIcon = { render: () => h('span') };

function makeHit(id: string, title: string): NormalizedSearchHit {
  return {
    id,
    recordId: id,
    collection: 'meetings',
    icon: DummyIcon,
    title,
    raw: {},
  };
}

const hits = [makeHit('meetings-1', 'Alpha'), makeHit('meetings-2', 'Beta')];

describe('SearchResultList', () => {
  it('shows the initial hint before searching', () => {
    const wrapper = mount(SearchResultList, { props: { hits: [], hasSearched: false } });
    expect(wrapper.text()).toContain('Pradėkite rašyti');
  });

  it('shows the empty message after searching with no results', () => {
    const wrapper = mount(SearchResultList, {
      props: { hits: [], hasSearched: true, emptyMessage: 'Nieko nerasta' },
    });
    expect(wrapper.text()).toContain('Nieko nerasta');
  });

  it('renders hits and emits select on click', async () => {
    const wrapper = mount(SearchResultList, { props: { hits, selectedId: 'meetings-1' } });

    expect(wrapper.text()).toContain('Alpha');
    expect(wrapper.text()).toContain('Beta');

    const betaButton = wrapper.findAll('button').find(b => b.text().includes('Beta'));
    await betaButton!.trigger('click');

    expect(wrapper.emitted('select')?.[0]?.[0]).toMatchObject({ id: 'meetings-2' });
  });

  it('emits loadMore when the load-more button is clicked', async () => {
    const wrapper = mount(SearchResultList, { props: { hits, hasMore: true } });

    const loadMore = wrapper.findAll('button').find(b => b.text().includes('Rodyti daugiau'));
    await loadMore!.trigger('click');

    expect(wrapper.emitted('loadMore')).toBeTruthy();
  });
});
