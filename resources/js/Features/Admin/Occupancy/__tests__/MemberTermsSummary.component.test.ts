import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import MemberTermsSummary from '@/Features/Admin/Occupancy/MemberTermsSummary.vue';

const stubs = {
  Popover: { template: '<div><slot /></div>' },
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: { template: '<div><slot /></div>' },
};

const term = (name: string, start: string, end: string | null = null) => ({
  institution: { name, short_name: null },
  pivot: { start_date: start, end_date: end },
});

describe('MemberTermsSummary.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date('2026-09-20T12:00:00Z'));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('counts terms in office against all of them', () => {
    const wrapper = mount(MemberTermsSummary, {
      props: { duties: [term('Parlamentas', '2026-07-01')], currentCount: 1, totalCount: 4 },
      global: { stubs },
    });

    // Tests resolve translations to their keys.
    expect(wrapper.get('[data-testid="member-terms-summary"]').text()).toContain(':count dabar');
    expect(wrapper.get('[data-testid="member-terms-summary"]').text()).toContain(':count iš viso');
  });

  it('lists where the person serves now and where they served before, with the year it ended', () => {
    const wrapper = mount(MemberTermsSummary, {
      props: {
        duties: [
          term('Parlamentas', '2026-07-01'),
          term('Parlamentas', '2023-07-01', '2024-06-30'),
          term('Valdyba', '2019-07-01', '2020-06-30'),
          term('Valdyba', '2020-07-01', '2021-06-30'),
        ],
        currentCount: 1,
        totalCount: 4,
      },
      global: { stubs },
    });

    const sections = wrapper.findAll('[data-testid="member-terms-list"] section').map(section => section.text());

    expect(sections).toHaveLength(2);
    expect(sections[0]).toContain('Parlamentas');
    // Still serving there, so it is not repeated as a past institution.
    expect(sections[1]).not.toContain('Parlamentas');
    expect(sections[1]).toContain('Valdyba');
    expect(sections[1]).toContain('2021');
  });

  it('does not repeat the count when every term is current', () => {
    const wrapper = mount(MemberTermsSummary, {
      props: { duties: [term('Parlamentas', '2026-07-01')], currentCount: 1, totalCount: 1 },
      global: { stubs },
    });

    expect(wrapper.get('[data-testid="member-terms-summary"]').text()).not.toContain('iš viso');
  });

  it('shows only the total for a former member', () => {
    const wrapper = mount(MemberTermsSummary, {
      props: { duties: [term('Valdyba', '2019-07-01', '2020-06-30')], currentCount: 0, totalCount: 1 },
      global: { stubs },
    });

    expect(wrapper.get('[data-testid="member-terms-summary"]').text()).not.toContain('dabar');
  });
});
