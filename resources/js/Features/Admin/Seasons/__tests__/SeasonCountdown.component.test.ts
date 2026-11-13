import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import SeasonCountdown from '../countdown/SeasonCountdown.vue';

const START = '2026-12-01T00:00:00+02:00';
const time = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-slot="season-countdown-time"]');

describe('SeasonCountdown', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it.each([
    ['2026-11-26T10:40:00+02:00', 'season_countdown.days_hours'],
    ['2026-11-30T18:00:00+02:00', 'season_countdown.hours'],
    ['2026-11-30T23:20:00+02:00', 'season_countdown.minutes'],
  ])('counts down from %s in the right units', (now, key) => {
    vi.setSystemTime(new Date(now));

    expect(time(mount(SeasonCountdown, { props: { startsAt: START } })).text()).toContain(key);
  });

  it('is gone once the season has started', () => {
    vi.setSystemTime(new Date('2026-12-01T00:00:30+02:00'));

    expect(mount(SeasonCountdown, { props: { startsAt: START } }).find('[data-slot="season-countdown-hero"]').exists()).toBe(false);
  });
});
