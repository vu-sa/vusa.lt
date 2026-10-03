import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import TopProgressBar from '../TopProgressBar.vue';

describe('TopProgressBar (Patterns)', () => {
  it('renders indeterminate progress bar by default with accessibility attributes', () => {
    const wrapper = mount(TopProgressBar);

    expect(wrapper.find('[data-slot="top-progress-bar"]').exists()).toBe(true);
    expect(wrapper.attributes('role')).toBe('progressbar');
    expect(wrapper.attributes('aria-busy')).toBe('true');
    expect(wrapper.attributes('aria-valuenow')).toBeUndefined();
    expect(wrapper.find('.top-progress-indeterminate').exists()).toBe(true);
  });

  it('renders determinate progress bar with computed width', () => {
    const wrapper = mount(TopProgressBar, {
      props: {
        indeterminate: false,
        modelValue: 45,
      },
    });

    expect(wrapper.attributes('aria-valuenow')).toBe('45');
    const bar = wrapper.find('.bg-brand-fill');
    expect(bar.attributes('style')).toContain('width: 45%');
  });

  it('clamps determinate values between 0 and 100', () => {
    const wrapperHigh = mount(TopProgressBar, {
      props: {
        indeterminate: false,
        modelValue: 150,
      },
    });
    expect(wrapperHigh.attributes('aria-valuenow')).toBe('100');

    const wrapperLow = mount(TopProgressBar, {
      props: {
        indeterminate: false,
        modelValue: -20,
      },
    });
    expect(wrapperLow.attributes('aria-valuenow')).toBe('0');
  });

  it('does not render when active is false', () => {
    const wrapper = mount(TopProgressBar, {
      props: {
        active: false,
      },
    });

    expect(wrapper.find('[data-slot="top-progress-bar"]').exists()).toBe(false);
  });
});
