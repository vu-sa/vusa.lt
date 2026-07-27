import { describe, expect, it } from 'vitest';
import { ref } from 'vue';
import * as d3 from 'd3';

import { useGanttViewport } from '../useGanttViewport';

function makeScrollContainer(overrides: Partial<HTMLElement> = {}) {
  return {
    scrollLeft: 0,
    scrollTop: 0,
    clientWidth: 800,
    clientHeight: 600,
    ...overrides,
  } as HTMLElement;
}

describe('useGanttViewport', () => {
  it('updates bounds when only scrollTop changes (pure vertical scroll)', () => {
    const el = makeScrollContainer();
    const container = ref<HTMLElement | null>(el);
    const curX = ref(d3.scaleTime().domain([new Date(2026, 0, 1), new Date(2026, 11, 31)]).range([0, 1000]));

    const viewport = useGanttViewport(container, curX);
    viewport.forceUpdate();

    el.scrollTop = 500;
    const changed = viewport.updateViewport();

    expect(changed).toBe(true);
    expect(viewport.viewportTop.value).toBe(500);
    expect(viewport.viewportBottom.value).toBe(1100);
  });

  it('does not report a change when scroll deltas are below the threshold', () => {
    const el = makeScrollContainer();
    const container = ref<HTMLElement | null>(el);
    const curX = ref(d3.scaleTime().domain([new Date(2026, 0, 1), new Date(2026, 11, 31)]).range([0, 1000]));

    const viewport = useGanttViewport(container, curX, { scrollThreshold: 50 });
    viewport.forceUpdate();

    el.scrollTop = 10;
    el.scrollLeft = 10;
    expect(viewport.updateViewport()).toBe(false);
  });

  it('applies a coarser threshold to vertical scroll when verticalScrollThreshold is set', () => {
    const el = makeScrollContainer();
    const container = ref<HTMLElement | null>(el);
    const curX = ref(d3.scaleTime().domain([new Date(2026, 0, 1), new Date(2026, 11, 31)]).range([0, 1000]));

    const viewport = useGanttViewport(container, curX, { scrollThreshold: 50, verticalScrollThreshold: 250 });
    viewport.forceUpdate();

    // A vertical delta above the (lower) horizontal threshold but below the
    // vertical one must NOT trigger a re-render — this is the whole point of
    // decoupling the thresholds (fewer expensive re-renders during vertical scroll).
    el.scrollTop = 100;
    expect(viewport.updateViewport()).toBe(false);

    el.scrollTop = 260;
    expect(viewport.updateViewport()).toBe(true);
  });

  it('createVisibleRows filters rows overlapping the vertical viewport plus buffer', () => {
    const el = makeScrollContainer({ scrollTop: 1000, clientHeight: 200 });
    const container = ref<HTMLElement | null>(el);
    const curX = ref(d3.scaleTime().domain([new Date(2026, 0, 1), new Date(2026, 11, 31)]).range([0, 1000]));

    const viewport = useGanttViewport(container, curX, { verticalBufferPx: 100 });
    viewport.forceUpdate();

    const rows = [
      { key: 'above', top: 0, height: 50 }, // far above viewport+buffer (bottom edge 50 < 900)
      { key: 'in-buffer', top: 850, height: 50 }, // within the 100px top buffer
      { key: 'visible', top: 1050, height: 50 },
      { key: 'below', top: 2000, height: 50 }, // far below viewport+buffer
    ];

    const visible = viewport.createVisibleRows(() => rows);

    expect(visible.value.map(r => r.key)).toEqual(['in-buffer', 'visible']);
  });
});
