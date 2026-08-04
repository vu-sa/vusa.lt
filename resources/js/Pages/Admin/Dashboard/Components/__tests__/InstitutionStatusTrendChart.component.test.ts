import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionStatusTrendChart from '../InstitutionStatusTrendChart.vue';
import type { InstitutionStatusHistoryPoint } from '../../types';

// jsdom has no ResizeObserver; a minimal stub lets the component mount so we can
// assert its prop-driven wiring. Actual D3/SVG geometry (path shapes, pixel
// positions) is a rendering concern that can't be meaningfully asserted in
// jsdom — that gap is intentional, per resources/js/CLAUDE.md.
class ResizeObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}

function point(date: string, overrides: Partial<InstitutionStatusHistoryPoint> = {}): InstitutionStatusHistoryPoint {
  return {
    date,
    all: 4,
    needs_attention: 1,
    overdue: 1,
    approaching: 0,
    no_activity: 0,
    current: 3,
    ...overrides,
  };
}

describe('InstitutionStatusTrendChart', () => {
  let wrapper: ReturnType<typeof mount>;
  let originalResizeObserver: typeof ResizeObserver | undefined;

  beforeEach(() => {
    originalResizeObserver = globalThis.ResizeObserver;
    globalThis.ResizeObserver = ResizeObserverStub as unknown as typeof ResizeObserver;
  });

  afterEach(() => {
    wrapper?.unmount();
    globalThis.ResizeObserver = originalResizeObserver as typeof ResizeObserver;
    vi.restoreAllMocks();
  });

  it('shows the empty state when there is no data and it is not loading', () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: { data: [], days: 90, loading: false },
    });

    expect(wrapper.text()).toContain('visak.institution_summary.trend_empty');
    expect(wrapper.find('[data-testid="trend-chart-svg"]').exists()).toBe(false);
  });

  it('shows a loading indicator instead of the empty state while the first fetch is pending', () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: { data: [], days: 90, loading: true },
    });

    expect(wrapper.text()).not.toContain('visak.institution_summary.trend_empty');
    expect(wrapper.find('[data-testid="trend-chart-svg"]').exists()).toBe(false);
  });

  it('renders the chart once data arrives on an already-mounted (initially empty) instance', async () => {
    // Regression test: on first Trend-tab activation the chart mounts with
    // data=[] before the fetch resolves, then data arrives while the component
    // stays mounted (no remount) — the <svg> only enters the DOM at that point
    // (see template v-else), so the data watcher must wait a tick for Vue to
    // patch the DOM before it can find the ref.
    wrapper = mount(InstitutionStatusTrendChart, {
      props: { data: [], days: 90, loading: true },
    });
    expect(wrapper.find('[data-testid="trend-chart-svg"]').exists()).toBe(false);

    await wrapper.setProps({ data: [point('2026-05-01'), point('2026-05-02')], loading: false });

    expect(wrapper.find('[data-testid="trend-chart-svg"]').exists()).toBe(true);
  });

  it('renders the chart and a legend entry per status series once data arrives', () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [point('2026-05-01'), point('2026-05-02'), point('2026-05-03')],
        days: 90,
        loading: false,
      },
    });

    expect(wrapper.find('[data-testid="trend-chart-svg"]').exists()).toBe(true);
    // One legend swatch per stacked series: current, approaching, overdue, no_activity
    expect(wrapper.findAll('.flex-wrap.gap-3 > span').length).toBe(4);
  });

  it('emits update:days when a range option is clicked', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [point('2026-05-01')],
        days: 90,
        loading: false,
      },
    });

    await wrapper.get('[data-testid="trend-range-30"]').trigger('click');

    expect(wrapper.emitted('update:days')).toEqual([[30]]);
  });

  it('marks the currently selected range button as active', () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [point('2026-05-01')],
        days: 180,
        loading: false,
      },
    });

    expect(wrapper.get('[data-testid="trend-range-180"]').classes()).toContain('bg-primary');
    expect(wrapper.get('[data-testid="trend-range-30"]').classes()).not.toContain('bg-primary');
  });
});
