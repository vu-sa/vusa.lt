import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import OverviewPage from '../OverviewPage.vue';

describe('OverviewPage', () => {
  it('draws eyebrow, title and lead, then the attention slot before the sections', () => {
    const wrapper = mount(OverviewPage, {
      props: { eyebrow: 'ViSAK', title: 'Apžvalga', lead: 'Kas laukia.' },
      slots: { attention: '<div data-testid="attention" />', default: '<div data-testid="section" />' },
    });

    expect(wrapper.find('h1').text()).toBe('Apžvalga');
    expect(wrapper.text()).toContain('ViSAK');
    expect(wrapper.text()).toContain('Kas laukia.');
    const order = wrapper.findAll('[data-testid]:not([data-testid="inertia-head"])').map(node => node.attributes('data-testid'));
    expect(order).toEqual(['attention', 'section']);
  });

  it('lets the heading slot replace the h1', () => {
    const wrapper = mount(OverviewPage, {
      props: { title: 'Mano VU SA' },
      slots: { heading: '<h1 data-testid="greeting">Labas rytas, Jonai!</h1>' },
    });

    expect(wrapper.findAll('h1')).toHaveLength(1);
    expect(wrapper.find('[data-testid="greeting"]').exists()).toBe(true);
  });
});
