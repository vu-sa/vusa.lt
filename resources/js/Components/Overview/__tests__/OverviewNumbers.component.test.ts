import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import OverviewNumbers from '../OverviewNumbers.vue';

describe('OverviewNumbers', () => {
  const numbers = [
    { key: 'overdue', label: 'Vėluoja', value: 2, href: '/mano/institutions', tone: 'danger' as const },
    { key: 'incomplete', label: 'Neužpildyti posėdžiai', value: 0, href: '/mano/meetings', tone: 'attention' as const },
  ];

  it('makes every number a link to what it counts', () => {
    const wrapper = mount(OverviewNumbers, { props: { numbers } });

    expect(wrapper.findAll('a').map(link => link.attributes('href'))).toEqual(['/mano/institutions', '/mano/meetings']);
  });

  it('lights a number only while it is non-zero', () => {
    const wrapper = mount(OverviewNumbers, { props: { numbers } });

    expect(wrapper.find('[data-number="overdue"] span').classes()).toContain('text-status-danger');
    expect(wrapper.find('[data-number="incomplete"] span').classes()).not.toContain('text-status-attention');
  });
});
