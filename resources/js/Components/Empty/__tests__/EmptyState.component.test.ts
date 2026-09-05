import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import EmptyState from '../EmptyState.vue';
import TurtleMascot from '../TurtleMascot.vue';

describe('EmptyState', () => {
  const props = { title: 'Nothing here', description: 'Nothing to show yet.' };

  it('falls back to the turtle mascot when no icon is supplied', () => {
    const wrapper = mount(EmptyState, { props });

    expect(wrapper.findComponent(TurtleMascot).exists()).toBe(true);
  });

  it('renders a supplied icon instead of the mascot', () => {
    const wrapper = mount(EmptyState, {
      props,
      slots: { icon: '<svg data-testid="custom" />' },
    });

    expect(wrapper.find('[data-testid="custom"]').exists()).toBe(true);
    expect(wrapper.findComponent(TurtleMascot).exists()).toBe(false);
  });

  it('keeps the muted chip around a supplied icon, but not around the mascot', () => {
    const withIcon = mount(EmptyState, { props, slots: { icon: '<svg />' } });
    const withMascot = mount(EmptyState, { props });

    expect(withIcon.find('.rounded-full').exists()).toBe(true);
    expect(withMascot.find('.rounded-full').exists()).toBe(false);
  });
});
