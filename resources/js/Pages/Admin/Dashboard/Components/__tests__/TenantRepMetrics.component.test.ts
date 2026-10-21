import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import TenantRepMetrics from '../TenantRepMetrics.vue';
import type { TenantRepMetrics as TenantRepMetricsData } from '../../types';

const data: TenantRepMetricsData = {
  currentPeriod: { cadenceId: 'current', label: '2026–2027', from: '2026-07-01', until: '2026-10-20' },
  baselinePeriod: { cadenceId: 'previous', label: '2025–2026', from: '2025-07-01', until: '2026-06-30' },
  metrics: [
    { key: 'recorded_within_week', now: 96, part: 24, whole: 25, baseline: 91.9, baselinePart: 23, baselineWhole: 25 },
    { key: 'task_completion', now: 60, part: 6, whole: 10, baseline: 67.2, baselinePart: 67, baselineWhole: 100 },
    { key: 'agenda_completion', now: null, part: 0, whole: 0, baseline: 27, baselinePart: 27, baselineWhole: 100 },
  ],
};

describe('TenantRepMetrics', () => {
  it('shows a row per metric with the counted figures and a worded status', () => {
    const wrapper = mount(TenantRepMetrics, { props: { data, loading: false } });

    expect(wrapper.findAll('tbody tr')).toHaveLength(3);
    expect(wrapper.findAll('thead th')).toHaveLength(3);
    expect(wrapper.get('thead').text()).toContain('2026–27');
    expect(wrapper.get('thead').text()).toContain('2025–26');
    expect(wrapper.get('details').attributes('open')).toBeUndefined();
    expect(wrapper.get('[data-slot=tenant-rep-metrics-periods]').text()).toContain('2025–2026');
    expect(wrapper.get('[data-metric=recorded_within_week] [data-slot=baseline-counted]').text()).toBe('23 / 25');
    expect(wrapper.find('[data-slot=supplementary-metrics]').exists()).toBe(false);
    expect(wrapper.find('svg').exists()).toBe(false);
    expect(wrapper.get('[data-metric="recorded_within_week"]').text()).toContain('24 / 25');
    expect(wrapper.get('[data-metric="recorded_within_week"] [data-slot="metric-status"]').text()).toContain('metrics.status.improved');
    expect(wrapper.get('[data-metric="task_completion"] [data-slot="metric-status"]').text()).toContain('metrics.status.behind');
  });

  it('shows a dash and no counts when a metric had nothing to measure', () => {
    const wrapper = mount(TenantRepMetrics, { props: { data, loading: false } });

    const row = wrapper.get('[data-metric="agenda_completion"]');
    expect(row.text()).toContain('—');
    expect(row.find('[data-slot="metric-counted"]').exists()).toBe(false);
  });

  it('shows a skeleton while loading and a message when nothing arrived', async () => {
    const wrapper = mount(TenantRepMetrics, { props: { data: null, loading: true } });
    expect(wrapper.find('[data-slot="tenant-rep-metrics-loading"]').exists()).toBe(true);

    await wrapper.setProps({ loading: false });

    expect(wrapper.text()).toContain('visak.tenant_overview.rep_metrics_empty');
  });

  it('shows the 14-day arrow against the same cadence on the reference day, and none without one', () => {
    const withTrend: TenantRepMetricsData = {
      ...data,
      trendSince: '2026-10-06',
      metrics: [
        { ...data.metrics[0], now: 96, previous: 90 },
        { ...data.metrics[1], now: 60, previous: 66.5 },
        { ...data.metrics[2], now: 40, previous: 39.8 },
      ],
    };
    const wrapper = mount(TenantRepMetrics, { props: { data: withTrend, loading: false } });

    expect(wrapper.get('[data-metric="recorded_within_week"] [data-slot="metric-trend"]').attributes('data-direction')).toBe('up');
    expect(wrapper.get('[data-metric="recorded_within_week"] [data-slot="metric-trend"]').text()).toContain('metrics.trend.points');
    expect(wrapper.get('[data-metric="task_completion"] [data-slot="metric-trend"]').attributes('data-direction')).toBe('down');
    expect(wrapper.get('[data-metric="agenda_completion"] [data-slot="metric-trend"]').attributes('data-direction')).toBe('flat');

    const young = mount(TenantRepMetrics, { props: { data: { ...withTrend, trendSince: null }, loading: false } });
    expect(young.find('[data-slot="metric-trend"]').exists()).toBe(false);
  });
});
