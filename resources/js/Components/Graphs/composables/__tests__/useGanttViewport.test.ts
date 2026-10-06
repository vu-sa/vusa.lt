import { afterEach, describe, expect, it, vi } from 'vitest';
import { effectScope, ref } from 'vue';
import { mount } from '@vue/test-utils';
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
  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('can be created outside setup without lifecycle warnings', () => {
    const warn = vi.spyOn(console, 'warn');
    const viewport = useGanttViewport(ref(null), ref(null));

    viewport.cleanup();

    expect(warn).not.toHaveBeenCalled();
  });

  it.each(['manual', 'scope', 'unmount', 'detach'] as const)('removes scroll tracking and pending frames on %s cleanup', (mode) => {
    vi.useFakeTimers();
    const el = document.createElement('div');
    const container = ref<HTMLElement | null>(el);
    const changed = vi.fn();
    const scope = effectScope();
    let viewport!: ReturnType<typeof useGanttViewport>;
    const createViewport = () => {
      viewport = useGanttViewport(container, ref(null), { onViewportChange: changed });
    };
    const wrapper = mode === 'unmount' ? mount({ setup: createViewport, render: () => null }) : null;
    if (mode === 'scope') scope.run(createViewport);
    if (mode === 'manual' || mode === 'detach') createViewport();
    const detach = viewport.attachViewportTracking();
    changed.mockClear();

    el.scrollTop = 500;
    el.dispatchEvent(new Event('scroll'));
    expect(vi.getTimerCount()).toBe(1);
    // Template refs may already be cleared when cleanup runs.
    container.value = null;
    if (mode === 'manual') viewport.cleanup();
    if (mode === 'detach') detach();
    if (mode === 'scope') scope.stop();
    wrapper?.unmount();
    expect(vi.getTimerCount()).toBe(0);
    container.value = el;
    vi.runAllTimers();
    el.dispatchEvent(new Event('scroll'));
    vi.runAllTimers();
    detach();
    viewport.cleanup();
    scope.stop();

    expect(changed).not.toHaveBeenCalled();
    expect(viewport.viewportTop.value).toBe(0);
  });

  it('detaches the previous container when tracking is reattached', () => {
    vi.useFakeTimers();
    const oldContainer = document.createElement('div');
    const newContainer = document.createElement('div');
    const container = ref<HTMLElement | null>(oldContainer);
    const changed = vi.fn();
    const viewport = useGanttViewport(container, ref(null), { onViewportChange: changed });
    const oldDetach = viewport.attachViewportTracking();
    container.value = newContainer;
    const detach = viewport.attachViewportTracking();
    oldDetach();
    changed.mockClear();

    newContainer.scrollTop = 500;
    oldContainer.dispatchEvent(new Event('scroll'));
    vi.runAllTimers();
    expect(changed).not.toHaveBeenCalled();

    newContainer.dispatchEvent(new Event('scroll'));
    vi.runAllTimers();
    expect(changed).toHaveBeenCalledOnce();
    detach();
  });

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
