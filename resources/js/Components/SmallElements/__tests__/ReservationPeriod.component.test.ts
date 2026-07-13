import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import ReservationPeriod from '@/Components/SmallElements/ReservationPeriod.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function createWrapper(startTime: string, endTime: string, overdue = false) {
  return mount(ReservationPeriod, {
    props: { startTime, endTime, overdue },
    global: { stubs: { ...commonStubs } },
  });
}

describe('compact period', () => {
  it('collapses a same-month range to a single month name', () => {
    const wrapper = createWrapper('2026-07-20T10:00:00Z', '2026-07-24T18:00:00Z');
    const text = wrapper.text();

    // "Jul 20 → 24" — the month is not repeated.
    expect(text).toMatch(/20\s*→\s*24/);
    expect(text).toContain('2026');
  });

  it('keeps both month names when the range crosses a month boundary', () => {
    const wrapper = createWrapper('2026-07-28T10:00:00Z', '2026-08-02T18:00:00Z');
    const text = wrapper.text();

    // Both sides need their month, otherwise "28 → 2" reads as going backwards.
    expect(text).toMatch(/28.*→.*2/);
    expect(text).toContain('2026');
  });

  it('shows times instead of dates when the booking starts and ends on one day', () => {
    const wrapper = createWrapper('2026-07-14T09:00:00Z', '2026-07-14T17:00:00Z');
    const text = wrapper.text();

    // The date is redundant twice over, so the compact line carries the hours.
    expect(text).toMatch(/\d{2}:\d{2}\s*→\s*\d{2}:\d{2}/);
  });

  it('spans the years when a range crosses new year', () => {
    const wrapper = createWrapper('2026-12-28T10:00:00Z', '2027-01-04T10:00:00Z');

    expect(wrapper.text()).toContain('2026');
    expect(wrapper.text()).toContain('2027');
  });
});

describe('overdue', () => {
  it('adds a lateness sub-line when overdue', () => {
    const twoDaysAgo = new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString();
    const wrapper = createWrapper('2020-01-01T10:00:00Z', twoDaysAgo, true);

    expect(wrapper.text()).toContain('reservations.overdue_days');
  });

  it('stays quiet when not overdue', () => {
    const wrapper = createWrapper('2026-07-20T10:00:00Z', '2026-07-24T10:00:00Z', false);

    expect(wrapper.text()).not.toContain('reservations.overdue_days');
  });
});

describe('exact times', () => {
  it('keeps the full date-times available in the tooltip', () => {
    const wrapper = createWrapper('2026-07-20T10:00:00Z', '2026-07-24T18:00:00Z');
    const tooltip = wrapper.find('.tooltip-content');

    expect(tooltip.exists()).toBe(true);
    // Compacting the cell must not lose the hours.
    expect(tooltip.text()).toMatch(/\d{2}:\d{2}/);
  });
});
