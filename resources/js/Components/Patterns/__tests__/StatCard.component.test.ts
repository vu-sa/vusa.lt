import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import StatCard from '../StatCard.vue';

describe('StatCard', () => {
  it('renders a plain element when it is not a filter', () => {
    const wrapper = mount(StatCard, { props: { label: 'Pending', value: 12 } });

    expect(wrapper.element.tagName).toBe('DIV');
    expect(wrapper.attributes('aria-pressed')).toBeUndefined();
    expect(wrapper.text()).toContain('12');
  });

  it('becomes a pressable button when interactive', async () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Awaiting', value: 3, interactive: true, active: true },
    });

    expect(wrapper.element.tagName).toBe('BUTTON');
    expect(wrapper.attributes('aria-pressed')).toBe('true');

    await wrapper.trigger('click');
    expect(wrapper.emitted('click')).toHaveLength(1);
  });

  it('does not emit a click when it is not interactive', async () => {
    const wrapper = mount(StatCard, { props: { label: 'Pending', value: 0 } });

    await wrapper.trigger('click');

    expect(wrapper.emitted('click')).toBeUndefined();
  });

  it('dims a tile whose count is zero', () => {
    const wrapper = mount(StatCard, { props: { label: 'Overdue', value: 0, muted: true } });

    expect(wrapper.classes()).toContain('opacity-60');
  });
});
