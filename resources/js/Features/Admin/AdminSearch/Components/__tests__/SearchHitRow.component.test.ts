import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SearchHitRow from '../SearchHitRow.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';
import { documentWithMatch } from '@/Shared/Search/matches';

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
  it('keeps the matching context smaller than the title and limited to one line', () => {
    const wrapper = mount(SearchHitRow, {
      props: { hit: { ...hit, raw: { _searchMatch: { field: 'current_user_names', segments: [{ text: 'Petras Petraitis', matched: true }] } } } },
      global: { stubs: { EntityTypeMark: true } },
    });
    const excerpt = wrapper.get('[data-slot="search-match"]');
    expect(excerpt.classes()).toContain('text-xs');
    expect(excerpt.classes()).toContain('line-clamp-1');
  });
  it('highlights the existing name without adding a duplicate excerpt', () => {
    const raw = documentWithMatch({ document: { name: hit.title }, highlights: [
      { field: 'name', snippet: '⟦Jonas⟧ Jonaitis' },
      { field: 'current_user_names', snippet: '⟦Jonas⟧ Jonaitis' },
    ] });
    const wrapper = mount(SearchHitRow, { props: { hit: { ...hit, raw } }, global: { stubs: { EntityTypeMark: true } } });
    expect(wrapper.get('[data-slot="search-title-match"] mark').text()).toBe('Jonas');
    expect(wrapper.find('[data-slot="search-match"]').exists()).toBe(false);
    expect(wrapper.text().match(/Jonas Jonaitis/g)).toHaveLength(1);
  });

  it('does not highlight a name when only a related record matched', () => {
    const raw = documentWithMatch({ document: { name: hit.title }, highlights: [{ field: 'current_duty_names', snippet: '⟦Prezidentė⟧' }] });
    const wrapper = mount(SearchHitRow, { props: { hit: { ...hit, raw } }, global: { stubs: { EntityTypeMark: true } } });
    expect(wrapper.find('[data-slot="search-title-match"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="search-match"] mark').text()).toBe('Prezidentė');
  });
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
