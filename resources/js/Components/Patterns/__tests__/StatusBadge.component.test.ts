import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { StatusBadge } from '..';

import { contentStatuses, taskStatuses } from '@/Constants/statuses';

describe('StatusBadge', () => {
  it('renders the canonical label and icon', () => {
    const wrapper = mount(StatusBadge, {
      props: { status: taskStatuses.completed },
    });

    expect(wrapper.text()).toBe('Atlikta');
    expect(wrapper.find('svg').exists()).toBe(true);
    expect(wrapper.find('svg').attributes('aria-hidden')).toBe('true');
  });

  it('uses the semantic token classes for its role', () => {
    const wrapper = mount(StatusBadge, {
      props: { status: contentStatuses.draft },
    });

    expect(wrapper.attributes('data-status-role')).toBe('neutral');
    expect(wrapper.classes()).toContain('bg-status-neutral-surface');
    expect(wrapper.classes()).toContain('border-status-neutral-border');
    expect(wrapper.classes()).toContain('text-status-neutral');
  });

  it('merges caller classes onto the root', () => {
    const wrapper = mount(StatusBadge, {
      props: { status: taskStatuses.overdue, class: 'uppercase' },
    });

    expect(wrapper.classes()).toContain('uppercase');
  });
});
