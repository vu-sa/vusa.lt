import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ref } from 'vue';

import ShowSvetaine from '../ShowSvetaine.vue';

import { commonStubs } from '@/tests/stubs';
import type { AnalyticsOverviewData } from '@/Types/api.d';

const mockController = {
  data: ref<AnalyticsOverviewData | null>(null),
  isFetching: ref(false),
  error: ref<string | null>(null),
  isFinished: ref(true),
  isSuccess: ref(true),
  execute: vi.fn(),
  abort: vi.fn(),
};

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn(() => mockController),
  useApiMutation: vi.fn(),
}));

// Observable Plot needs real layout APIs that jsdom lacks; the chart itself is not what
// these tests are about.
vi.mock('@observablehq/plot', () => ({
  plot: () => document.createElement('svg'),
  areaY: vi.fn(),
  line: vi.fn(),
  ruleY: vi.fn(),
}));

vi.stubGlobal('route', (name?: string, params: Record<string, unknown> = {}) => {
  if (name === undefined) {
    return { current: () => false };
  }

  const query = Object.entries(params).map(([key, value]) => `${key}=${encodeURIComponent(String(value))}`).join('&');

  return `/mocked/${name}${query ? `?${query}` : ''}`;
});

const providedTenant = {
  id: 1,
  shortname: 'VU SA MIF',
} as unknown as App.Entities.Tenant;

const availableOverview: AnalyticsOverviewData = {
  available: true,
  period: '30d',
  hostname: 'mif.vusa.lt',
  totals: { pageviews: 120, visitors: 45, visits: 60, bounces: 12 },
  series: [
    { date: '2026-07-25 00:00:00', pageviews: 30, visitors: 10 },
    { date: '2026-07-26 00:00:00', pageviews: 90, visitors: 35 },
  ],
  topPages: [{ path: '/lt', views: 80 }],
};

const counts = { newsDrafts: 2, calendarDrafts: 0, news: 14, pages: 9 };

const mountPage = (overrides: Partial<{ counts: typeof counts }> = {}) => mount(ShowSvetaine, {
  props: {
    tenants: [{ id: 1, shortname: 'VU SA MIF' }] as unknown as App.Entities.Tenant[],
    providedTenant,
    counts,
    ...overrides,
  },
  global: { stubs: commonStubs },
});

describe('ShowSvetaine traffic section', () => {
  beforeEach(() => {
    mockController.data.value = null;
    mockController.isFetching.value = false;
  });

  it('shows skeletons while the statistics are loading', () => {
    mockController.isFetching.value = true;

    const wrapper = mountPage();

    expect(wrapper.findAll('[data-slot="skeleton"], .animate-pulse').length).toBeGreaterThan(0);
  });

  it('renders a text summary of the chart and the top pages once data arrives', () => {
    mockController.data.value = availableOverview;

    const wrapper = mountPage();

    expect(wrapper.find('[data-testid="chart-summary"]').text()).toContain('svetaine.overview.traffic.summary');
    expect(wrapper.find('[data-testid="chart-summary"]').text()).toContain('svetaine.overview.traffic.up');
    expect(wrapper.text()).toContain('/lt');
    expect(wrapper.text()).toContain('80');
    // The hostname hint is only rendered once a hostname came back. Its placeholder is not
    // interpolated here because translations are not loaded in the test environment.
    expect(wrapper.text()).toContain('analytics.hostname_hint');
  });

  it('always states when data collection started, whatever the fetch state', () => {
    mockController.isFetching.value = true;

    expect(mountPage().text()).toContain('analytics.since_notice');

    mockController.isFetching.value = false;
    mockController.data.value = availableOverview;

    expect(mountPage().text()).toContain('analytics.since_notice');
  });

  it('shows an unavailable state instead of breaking when umami is unreachable', () => {
    mockController.data.value = {
      ...availableOverview,
      available: false,
      totals: null,
      series: [],
      topPages: [],
    };

    const wrapper = mountPage();

    expect(wrapper.text()).toContain('analytics.unavailable_title');
    // The rest of the page must still render.
    expect(wrapper.find('[data-slot="overview-numbers"]').exists()).toBe(true);
  });

  it('shows an empty state when the tenant has no page views yet', () => {
    mockController.data.value = {
      ...availableOverview,
      totals: { pageviews: 0, visitors: 0, visits: 0, bounces: 0 },
      series: [],
      topPages: [],
    };

    const wrapper = mountPage();

    expect(wrapper.text()).toContain('analytics.empty_title');
  });
});

describe('ShowSvetaine numbers', () => {
  beforeEach(() => {
    mockController.data.value = null;
    mockController.isFetching.value = false;
  });

  it('links every number to the list it counts, narrowed to the selected unit', () => {
    const wrapper = mountPage();
    const link = (key: string) => decodeURIComponent(wrapper.find(`[data-number="${key}"]`).attributes('href') ?? '');

    expect(link('news_drafts')).toContain('news.index');
    expect(link('news_drafts')).toContain('"draft":true');
    expect(link('news_drafts')).toContain('"tenant_id":1');
    expect(link('calendar_drafts')).toContain('"is_draft":true');
    expect(link('pages')).toContain('pages.index');
  });

  it('leaves out a number the user may not open', () => {
    const wrapper = mountPage({ counts: { ...counts, newsDrafts: null, news: null } });

    expect(wrapper.find('[data-number="news_drafts"]').exists()).toBe(false);
    expect(wrapper.find('[data-number="news"]').exists()).toBe(false);
    expect(wrapper.find('[data-number="pages"]').exists()).toBe(true);
  });
});
