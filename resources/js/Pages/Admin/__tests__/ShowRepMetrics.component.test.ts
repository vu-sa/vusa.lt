import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShowRepMetrics from '@/Pages/Admin/ShowRepMetrics.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// Observable Plot needs real layout APIs that jsdom lacks; the figure's text is what is asserted.
vi.mock('@observablehq/plot', () => ({
  plot: () => document.createElement('svg'),
  line: vi.fn(),
  dot: vi.fn(),
  ruleY: vi.fn(),
}));

const trend = (values: (number | null)[]) => values.map((value, index) => ({ month: `2026-0${index + 7}`, value }));

const report = {
  months: ['2026-07', '2026-08', '2026-09'],
  metrics: [
    { key: 'recorded_within_week', now: 96, baseline: 91.9, target: 95, trend: trend([90, 93, 96]) },
    { key: 'vote_information', now: 40, baseline: 35.1, target: 50, trend: trend([35, 38, 40]) },
    { key: 'task_completion', now: 60, baseline: 67.2, target: 80, trend: trend([70, 65, 60]) },
    { key: 'periodicity_gap', now: 52, baseline: 44.9, target: 65, trend: trend([null, 45, 52]) },
    { key: 'active_reps', now: null, baseline: 27, target: 50, trend: null },
    { key: 'own_recording', now: 30, baseline: null, target: null, trend: trend([20, 25, 30]) },
  ],
  tasks: [
    { actionType: 'periodicity_gap', total: 10, completed: 5, rate: 50, medianDays: 12.5 },
    { actionType: 'agenda_completion', total: 4, completed: 4, rate: 100, medianDays: null },
  ],
};

const mountPage = (props: Record<string, unknown> = { report }) => mount(ShowRepMetrics, { props: props as never });

beforeEach(() => {
  vi.clearAllMocks();
});

describe('ShowRepMetrics', () => {
  it('lists every metric against its baseline and target', () => {
    const wrapper = mountPage();
    const row = wrapper.get('[data-metric="recorded_within_week"]');

    expect(row.text()).toContain('96 %');
    expect(row.text()).toContain('91.9 %');
    expect(row.text()).toContain('95 %');
    expect(wrapper.findAll('[data-testid="metrics-table"] tbody tr')).toHaveLength(6);
  });

  it('gives every status a word, so colour is never the only signal', () => {
    const wrapper = mountPage();
    const status = (key: string) => wrapper.get(`[data-metric="${key}"] [data-slot="metric-status"]`);

    expect(status('recorded_within_week').text()).toBe('metrics.status.reached');
    expect(status('vote_information').text()).toBe('metrics.status.improved');
    expect(status('task_completion').text()).toBe('metrics.status.behind');
    expect(status('active_reps').text()).toBe('metrics.status.no_data');
    expect(status('own_recording').text()).toBe('metrics.status.measured');
  });

  it('says plainly that a metric with no history is only a snapshot', () => {
    const wrapper = mountPage();

    expect(wrapper.get('[data-metric="active_reps"]').text()).toContain('metrics.table.snapshot');
    expect(wrapper.get('[data-metric="periodicity_gap"]').text()).not.toContain('metrics.table.snapshot');
  });

  it('carries the chart\'s message as text, and offers only metrics that have a trend', () => {
    const wrapper = mountPage();

    expect(wrapper.get('[data-testid="chart-summary"]').text()).toContain('metrics.chart.summary');
    expect(wrapper.findAll('[role="radio"]')).toHaveLength(5);
  });

  it('shows how each task type fared', () => {
    const wrapper = mountPage();

    expect(wrapper.findAll('[data-testid="task-table"] tbody tr')).toHaveLength(2);
    expect(wrapper.get('[data-task-type="periodicity_gap"]').text()).toContain('50 %');
    expect(wrapper.get('[data-task-type="periodicity_gap"]').text()).toContain('12.5');
    expect(wrapper.get('[data-task-type="agenda_completion"]').text()).toContain('—');
  });

  it('shows a skeleton while the deferred report has not arrived', () => {
    const wrapper = mount(ShowRepMetrics, {
      props: {} as never,
      global: { stubs: { InertiaDeferred: { template: '<div><slot name="fallback" /></div>' } } },
    });

    expect(wrapper.find('[data-slot="metrics-loading"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="metrics-table"]').exists()).toBe(false);
  });
});
