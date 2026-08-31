import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { useEventStatus } from '@/Composables/useEventStatus';

const hoursFromNow = (hours: number) => new Date(Date.now() + hours * 3600_000).toISOString();

describe('useEventStatus', () => {
  it('marks an event that already ended as past', () => {
    const { isPast, isLive, isUpcoming, tone, statusLabel } = useEventStatus({
      date: hoursFromNow(-48),
      end_date: hoursFromNow(-24),
    });

    expect(isPast.value).toBe(true);
    expect(isLive.value).toBe(false);
    expect(isUpcoming.value).toBe(false);
    expect(tone.value).toBe('past');
    expect(statusLabel.value).toBeTruthy();
  });

  it('marks an event currently running as live', () => {
    const { isLive, isPast, tone, statusLabel } = useEventStatus({
      date: hoursFromNow(-1),
      end_date: hoursFromNow(1),
    });

    expect(isLive.value).toBe(true);
    expect(isPast.value).toBe(false);
    expect(tone.value).toBe('live');
    expect(statusLabel.value).toBeTruthy();
  });

  it('gives an upcoming event no status badge, only a relative time', () => {
    const { isUpcoming, tone, statusLabel, relativeLabel } = useEventStatus({
      date: hoursFromNow(24 * 5),
      end_date: hoursFromNow(24 * 5 + 3),
    });

    expect(isUpcoming.value).toBe(true);
    expect(tone.value).toBe('upcoming');
    // No "Netrukus"/"Artėja"-style urgency badge for upcoming events.
    expect(statusLabel.value).toBeNull();
    expect(relativeLabel.value).toBeTruthy();
  });

  it('states the relative time precisely rather than vaguely', () => {
    expect(useEventStatus({ date: hoursFromNow(24) }).relativeLabel.value).toBe('rytoj');
    expect(useEventStatus({ date: hoursFromNow(24 * 3) }).relativeLabel.value).toContain('3');
  });

  it('offers no relative time once the event has started', () => {
    expect(useEventStatus({ date: hoursFromNow(-2), end_date: hoursFromNow(2) }).relativeLabel.value)
      .toBeNull();
  });

  /**
   * The same page announces meetings, and "Renginys įvyko" reads as the wrong kind of
   * thing for one — the caller says which it is.
   */
  it('names a past meeting a meeting rather than an event', () => {
    const past = { date: hoursFromNow(-48), end_date: hoursFromNow(-24) };

    expect(useEventStatus(past, true).statusLabel.value).toBe('Posėdis įvyko');
    expect(useEventStatus(past).statusLabel.value).toBe('Renginys įvyko');
  });

  it('leaves a running meeting on the shared live label', () => {
    const live = { date: hoursFromNow(-1), end_date: hoursFromNow(1) };

    expect(useEventStatus(live, true).statusLabel.value).toBe('Vyksta dabar');
  });

  it('treats an event without an end date as ending when it starts', () => {
    const { isPast } = useEventStatus({ date: hoursFromNow(-2), end_date: null });

    expect(isPast.value).toBe(true);
  });
});
