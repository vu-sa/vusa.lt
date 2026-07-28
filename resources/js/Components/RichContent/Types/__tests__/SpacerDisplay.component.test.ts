import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SpacerDisplay from '../SpacerDisplay.vue';
import { SPACER_SIZE_CLASS } from '../spacerSizes';

import type { Spacer } from '@/Types/contentParts';

function makeElement(size?: Spacer['options']['size']): Spacer {
  return {
    json_content: {},
    options: size ? { size } : {},
  };
}

describe('SpacerDisplay', () => {
  it('renders a single empty gap div with aria-hidden so it stays out of the a11y tree', () => {
    const wrapper = mount(SpacerDisplay, { props: { element: makeElement('md') } });

    expect(wrapper.findAll('[data-testid="rc-spacer"]')).toHaveLength(1);
    expect(wrapper.find('[data-testid="rc-spacer"]').attributes('aria-hidden')).toBe('true');
    // No text content — a spacer is purely visual.
    expect(wrapper.text()).toBe('');
  });

  it.each(Object.entries(SPACER_SIZE_CLASS) as Array<[keyof typeof SPACER_SIZE_CLASS, string]>)(
    'maps the %s size to the %s height class',
    (size, expectedClass) => {
      const wrapper = mount(SpacerDisplay, { props: { element: makeElement(size) } });

      expect(wrapper.find('[data-testid="rc-spacer"]').classes()).toContain(expectedClass);
    },
  );

  it('falls back to the md height class when options.size is missing', () => {
    // Old rows saved before the field existed (or a freshly-defaulted block) must still
    // render a sensible gap rather than collapsing to zero height.
    const wrapper = mount(SpacerDisplay, { props: { element: makeElement(undefined) } });

    expect(wrapper.find('[data-testid="rc-spacer"]').classes()).toContain(SPACER_SIZE_CLASS.md);
  });

  it('renders without throwing for an unknown size by falling back to md', () => {
    // Backend validation clamps to the six known sizes, but a hand-edited row could
    // still ship a bad value — the display must never throw over it.
    const wrapper = mount(SpacerDisplay, {
      props: { element: { json_content: {}, options: { size: 'huge' as unknown } } as Spacer },
    });

    const gap = wrapper.find('[data-testid="rc-spacer"]');
    expect(gap.exists()).toBe(true);
    expect(gap.classes()).toContain(SPACER_SIZE_CLASS.md);
  });
});
