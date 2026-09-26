import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import OverviewChart from '../OverviewChart.vue';
import OverviewNumbers from '../OverviewNumbers.vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';

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

describe('OverviewChart', () => {
  it('always carries its text summary and points the figure at it', () => {
    const wrapper = mount(OverviewChart, {
      props: { summary: 'Vėluojančių sumažėjo nuo 4 iki 2.' },
      slots: { default: '<svg />' },
    });

    const caption = wrapper.find('figcaption');
    expect(caption.text()).toBe('Vėluojančių sumažėjo nuo 4 iki 2.');
    expect(wrapper.find('figure').attributes('aria-describedby')).toBe(caption.attributes('id'));
  });
});

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
