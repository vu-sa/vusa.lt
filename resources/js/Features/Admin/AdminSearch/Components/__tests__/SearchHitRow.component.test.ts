import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SearchHitRow from '../SearchHitRow.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

const hit = {
  id: 'users-1',
  recordId: '1',
  collection: 'users',
  icon: {},
  raw: {},
  title: 'Jonas Jonaitis',
  contextBadge: 'MIF',
  statusBadge: { label: 'Ištrintas', tone: 'danger' },
  viewHref: '/mano/users/1',
  editHref: '/mano/users/1/edit',
} as NormalizedSearchHit;

describe('SearchHitRow', () => {
  it('shows context separately from status and names its quick actions', async () => {
    const wrapper = mount(SearchHitRow, {
      props: { hit, showActions: true },
      global: { stubs: { EntityTypeMark: true } },
    });

    expect(wrapper.text()).toContain('MIF');
    expect(wrapper.text()).toContain('Ištrintas');

    const view = wrapper.get('button[aria-label="Peržiūrėti"]');
    const edit = wrapper.get('button[aria-label="Redaguoti"]');

    await view.trigger('click');
    await edit.trigger('click');

    expect(wrapper.emitted('view')).toHaveLength(1);
    expect(wrapper.emitted('edit')).toHaveLength(1);
  });
});
