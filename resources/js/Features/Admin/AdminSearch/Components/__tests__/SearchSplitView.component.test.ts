import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

import SearchSplitView from '../SearchSplitView.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

const hit = {
  id: 'resources-1',
  recordId: '1',
  collection: 'resources',
  title: 'Kamera',
  icon: {},
  raw: {},
} as NormalizedSearchHit;

afterEach(() => {
  document.body.innerHTML = '';
});

describe('SearchSplitView keyboard selection', () => {
  it('toggles a highlighted hit from the page but leaves focused controls alone', async () => {
    const wrapper = mount(SearchSplitView, {
      attachTo: document.body,
      props: { hits: [hit], selectedHit: hit, selectable: true },
      global: { stubs: { SearchResultList: true, SearchDetailPane: true } },
    });
    await nextTick();

    document.body.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
    expect(wrapper.emitted('toggleSelect')).toHaveLength(1);

    const confirm = document.createElement('button');
    document.body.appendChild(confirm);
    confirm.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));
    expect(wrapper.emitted('toggleSelect')).toHaveLength(1);

    wrapper.unmount();
  });
});
