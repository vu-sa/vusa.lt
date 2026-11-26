import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import OverviewChart from '../OverviewChart.vue';

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
