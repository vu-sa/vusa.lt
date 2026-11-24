import { afterEach, describe, expect, it, vi } from 'vitest';

import { GUST_MS, useSeasonSnow } from '../useSeasonSnow';

describe('useSeasonSnow', () => {
  afterEach(() => {
    useSeasonSnow().stop();
    vi.useRealTimers();
  });

  it('snows for a while after the fir is touched, then stops', () => {
    vi.useFakeTimers();
    const { gusting, start } = useSeasonSnow();

    start();
    expect(gusting.value).toBe(true);

    vi.advanceTimersByTime(GUST_MS - 1);
    expect(gusting.value).toBe(true);
    vi.advanceTimersByTime(1);
    expect(gusting.value).toBe(false);
  });

  it('a second touch stops the snow, and the old timer cannot cut a new snowfall short', () => {
    vi.useFakeTimers();
    const { gusting, toggle } = useSeasonSnow();

    toggle();
    vi.advanceTimersByTime(GUST_MS - 1000);
    toggle();
    expect(gusting.value).toBe(false);

    toggle();
    vi.advanceTimersByTime(2000);
    expect(gusting.value).toBe(true);
  });
});
