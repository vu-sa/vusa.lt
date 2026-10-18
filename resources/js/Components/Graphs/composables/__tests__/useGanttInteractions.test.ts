import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import * as d3 from 'd3';

import { useGanttInteractions } from '../useGanttInteractions';

function setup(dayWidth: number, viewportWidth = 1000) {
  const rightScroll = ref<HTMLElement | null>(document.createElement('div'));
  Object.defineProperty(rightScroll.value!, 'clientWidth', { value: viewportWidth });
  const dayWidthPx = ref(dayWidth);
  const afterRender = vi.fn((callback: () => void) => callback());

  const interactions = useGanttInteractions(
    { daysBefore: 60, daysAfter: 60, infiniteScroll: true, extendThresholdPx: 200, extendStepDays: 30 },
    { rightScroll, leftLabels: ref(null), curX: ref(null), dayWidthPx },
    { afterRender },
  );

  return { interactions, dayWidthPx, rightScroll };
}

const spanDays = (interactions: ReturnType<typeof useGanttInteractions>) =>
  d3.timeDay.count(interactions.minTime.value, interactions.maxTime.value);

describe('useGanttInteractions', () => {
  beforeEach(() => vi.useFakeTimers({ now: new Date(2026, 9, 18) }));
  afterEach(() => vi.useRealTimers());

  it('covers a viewport on each side of the screen from the start, however far out it is zoomed', () => {
    const { interactions } = setup(1);

    interactions.applyInitialExtension();

    // 1000 days on screen at 1px/day; the range reaches one more viewport each way.
    expect(spanDays(interactions)).toBeGreaterThanOrEqual(3000);
  });

  it('widens the range when zooming out, instead of stretching the scale', () => {
    const { interactions, dayWidthPx } = setup(9);
    interactions.applyInitialExtension();
    const before = spanDays(interactions);

    interactions.onScaleChange(1);

    expect(dayWidthPx.value).toBe(1);
    expect(spanDays(interactions)).toBeGreaterThan(before * 5);
  });

  it('extends a whole viewport per step near an edge at low zoom', () => {
    const { interactions, rightScroll } = setup(1);
    const el = rightScroll.value!;
    Object.defineProperty(el, 'scrollWidth', { value: 5000 });
    el.scrollLeft = 0;

    const before = interactions.extraBefore.value;
    interactions.onScroll();

    expect(interactions.extraBefore.value - before).toBe(1000);
  });
});
