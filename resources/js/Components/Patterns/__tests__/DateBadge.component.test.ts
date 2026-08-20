import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { DateBadge } from '..';

describe('DateBadge', () => {
  it('renders the day of month', () => {
    const wrapper = mount(DateBadge, {
      props: { date: '2026-03-17T10:00:00Z' },
    });

    expect(wrapper.text()).toContain('17');
  });

  it('accepts a Date as well as an ISO string', () => {
    const fromString = mount(DateBadge, { props: { date: '2026-03-17T10:00:00Z' } });
    const fromDate = mount(DateBadge, { props: { date: new Date('2026-03-17T10:00:00Z') } });

    expect(fromDate.text()).toBe(fromString.text());
  });

  it('shows a different month label for a different month', () => {
    const march = mount(DateBadge, { props: { date: '2026-03-17T10:00:00Z' } });
    const july = mount(DateBadge, { props: { date: '2026-07-17T10:00:00Z' } });

    expect(march.text()).not.toBe(july.text());
  });

  it('merges caller classes onto the root', () => {
    const wrapper = mount(DateBadge, {
      props: { date: '2026-03-17T10:00:00Z', class: 'size-12' },
    });

    expect(wrapper.classes()).toContain('size-12');
  });
});
