import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SearchResultGroup from '../SearchResultGroup.vue';
import { normalizeHit } from '../../Utils/searchHitMappers';

const meeting = (id: string, extra: Record<string, unknown> = {}) => normalizeHit('meetings', {
  id,
  title: `Posėdis ${id}`,
  institution_name_lt: 'VU Senatas',
  start_time: 1_700_000_000,
  completion_status: 'incomplete',
  ...extra,
});

function mountGroup(props: Partial<InstanceType<typeof SearchResultGroup>['$props']> = {}) {
  return mount(SearchResultGroup, {
    props: { collection: 'meetings', hits: [meeting('1'), meeting('2')], total: 40, href: '/mano/meetings?q=senatas', ...props },
  });
}

describe('SearchResultGroup', () => {
  it('names its entity in the plural and shows every row it was given', () => {
    const wrapper = mountGroup();

    expect(wrapper.get('h2').text()).toContain('Posėdžiai');
    expect(wrapper.findAll('li')).toHaveLength(2);
  });

  it('leads to the full list when the user may open it', () => {
    const wrapper = mountGroup();

    const link = wrapper.get('header a');
    expect(link.attributes('href')).toBe('/mano/meetings?q=senatas');
    expect(link.text()).toContain('Rodyti visus');
  });

  it('offers no link at all when the user may not open the full list', () => {
    expect(mountGroup({ href: null }).find('header a').exists()).toBe(false);
  });

  it('links a row to the record, preferring the view page over the public URL', () => {
    const document = normalizeHit('documents', { id: '3', title: 'Nuostatai', anonymous_url: 'https://vusa.lt/public/nuostatai' });
    const wrapper = mountGroup({ collection: 'documents', hits: [document], total: 1 });

    const href = wrapper.get('li a').attributes('href');
    expect(href).toContain('documents.show');
    expect(href).not.toContain('vusa.lt/public');
  });

  it('says a meeting is unfilled in words, not only in colour', () => {
    const wrapper = mountGroup({ hits: [meeting('1')] });

    const badge = wrapper.get('[data-slot="status-badge"]');
    expect(badge.text()).toBe('Neužpildyta');
    expect(badge.attributes('data-status-role')).toBe('attention');
  });
});
