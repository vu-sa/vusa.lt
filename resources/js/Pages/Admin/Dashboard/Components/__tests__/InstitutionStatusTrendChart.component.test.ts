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
    recent_check_in: 0,
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
    expect(wrapper.find('[data-slot="institution-trend-skeleton"]').exists()).toBe(true);
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
    expect(wrapper.find('[data-slot="institution-trend-skeleton"]').exists()).toBe(false);
  });

  it('renders the chart without a verbose legend once data arrives', () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [point('2026-05-01'), point('2026-05-02'), point('2026-05-03')],
        days: 90,
        loading: false,
      },
    });

    expect(wrapper.find('[data-testid="trend-chart-svg"]').exists()).toBe(true);
    expect(wrapper.findAll('.flex-wrap.gap-3 > span').length).toBe(0);
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

    expect(wrapper.get('[data-testid="trend-range-180"]').attributes('aria-pressed')).toBe('true');
    expect(wrapper.get('[data-testid="trend-range-30"]').attributes('aria-pressed')).toBe('false');
  });

  it('keeps flow information out of a separate legend', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: { data: [point('2026-05-01'), point('2026-05-02')], days: 90, loading: false },
    });
    expect(wrapper.find('[data-slot="trend-flow-legend"]').exists()).toBe(false);

    await wrapper.setProps({
      data: [
        point('2026-05-01', { entered_attention: 1, left_attention: 0 }),
        point('2026-05-02', { entered_attention: 0, left_attention: 2 }),
      ],
    });

    expect(wrapper.find('[data-slot="trend-flow-legend"]').exists()).toBe(false);
  });

  it('marks the baseline day only when it falls inside the range', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [point('2026-05-01'), point('2026-05-02'), point('2026-05-03')],
        days: 90,
        loading: false,
        baselineDate: '2026-05-02',
      },
    });
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="trend-baseline-marker"]').exists()).toBe(true);

    await wrapper.setProps({ baselineDate: '2026-01-01' });
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-testid="trend-baseline-marker"]').exists()).toBe(false);
  });

  it('groups flow by the selected interval and offers keyboard inspection of daily counts', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [
          point('2026-05-04', { left_attention: 2, entered_attention: 1, reconstructed: true }),
          point('2026-05-05', { left_attention: 3, entered_attention: 2, reconstructed: true }),
        ],
        days: 180,
      },
    });
    expect(wrapper.findAll('rect[data-direction="1"]')).toHaveLength(1);
    expect(wrapper.get('rect[data-direction="1"]').attributes('data-count')).toBe('5');
    expect(wrapper.get('rect[data-direction="-1"]').attributes('data-count')).toBe('3');
    expect(wrapper.find('[data-slot="trend-reconstruction-legend"]').exists()).toBe(true);
    expect(wrapper.find('mask').exists()).toBe(false);
    expect(wrapper.findAll('path[class^=fill-]').map(path => path.attributes('class'))).toEqual([
      'success', 'info', 'attention', 'danger', 'neutral',
    ].map(color => `fill-[color-mix(in_oklab,var(--status-${color})_70%,var(--status-${color}-surface))]`));
    expect(wrapper.find('[data-testid="trend-count-axis"]').exists()).toBe(true);
    const inspector = wrapper.get('[data-testid="trend-inspector"]');
    await inspector.trigger('focus');
    expect(document.querySelector('[data-slot=trend-tooltip]')?.textContent).toContain('2026-05-04');
    await inspector.trigger('keydown', { key: 'ArrowRight' });
    expect(document.querySelector('[data-slot=trend-tooltip]')?.textContent).toContain('2026-05-05');
  });
  it('portals inspection outside the chart and dismisses it on scroll', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: { data: [point('2026-05-01'), point('2026-05-02')], days: 90 },
      attachTo: document.body,
    });
    await wrapper.get('[data-testid="trend-inspector"]').trigger('focus');
    expect(document.body.querySelector('[data-slot="trend-tooltip"]')?.textContent).toContain('2026-05-01');
    expect(wrapper.find('[data-slot="trend-tooltip"]').exists()).toBe(false);
    window.dispatchEvent(new Event('scroll'));
    await wrapper.vm.$nextTick();
    expect(document.body.querySelector('[data-slot="trend-tooltip"]')).toBeNull();
  });

  it('places the reference label on the left at the right boundary', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: { data: [point('2026-05-01'), point('2026-05-02')], days: 90, baselineDate: '2026-05-02' },
    });
    await wrapper.vm.$nextTick();
    const label = wrapper.findAll('text').find(element => element.text() === 'metrics.history.baseline_end');
    expect(label?.attributes('text-anchor')).toBe('end');
    expect(Number(label?.attributes('y'))).toBeLessThan(Number(wrapper.get('[data-testid=trend-baseline-marker]').attributes('y1')));
  });

  it('starts keyboard inspection on available data and skips missing days', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [point('2026-05-01'), point('2026-05-03')], days: 30,
        missingDates: ['2026-04-30', '2026-05-02'],
      },
    });
    const inspector = wrapper.get('[data-testid="trend-inspector"]');
    await inspector.trigger('focus');
    expect(document.querySelector('[data-slot=trend-tooltip]')?.textContent).toContain('2026-05-01');
    await inspector.trigger('keydown', { key: 'ArrowRight' });
    expect(document.querySelector('[data-slot=trend-tooltip]')?.textContent).toContain('2026-05-03');
  });

  it('redraws flow buckets when the range changes and keeps partial buckets the same width', async () => {
    wrapper = mount(InstitutionStatusTrendChart, {
      props: {
        data: [
          point('2026-05-01', { left_attention: 1 }),
          point('2026-05-02', { left_attention: 2 }),
          point('2026-05-03', { left_attention: 3 }),
          point('2026-05-04', { left_attention: 4 }),
          point('2026-05-05', { left_attention: 5 }),
        ], days: 180,
      },
    });
    let bars = wrapper.findAll('rect[data-direction="1"]');
    expect(bars.map(bar => bar.attributes('data-count'))).toEqual(['3', '7', '5']);
    expect(bars.map(bar => bar.attributes('data-until'))).toEqual(['2026-05-02', '2026-05-04', '2026-05-05']);
    expect(new Set(bars.map(bar => bar.attributes('width'))).size).toBe(1);
    expect(bars.every(bar => bar.attributes('width') === '4')).toBe(true);
    await wrapper.setProps({ days: 90 });
    bars = wrapper.findAll('rect[data-direction="1"]');
    expect(bars.map(bar => bar.attributes('data-count'))).toEqual(['1', '2', '3', '4', '5']);
  });
});
