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

vi.stubGlobal('route', (name?: string) => {
  if (name === undefined) {
    return { current: () => false };
  }

  return `/mocked/${name}`;
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
  series: [{ date: '2026-07-25 00:00:00', pageviews: 70, visitors: 25 }],
  topPages: [{ path: '/lt', views: 80 }],
};

const mountPage = () => mount(ShowSvetaine, {
  props: {
    tenants: [{ id: 1, shortname: 'VU SA MIF' }] as unknown as App.Entities.Tenant[],
    providedTenant,
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

  it('renders totals and top pages once data arrives', () => {
    mockController.data.value = availableOverview;

    const wrapper = mountPage();

    expect(wrapper.text()).toContain('120');
    expect(wrapper.text()).toContain('45');
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
    expect(wrapper.text()).toContain('Pasirinkti padalinį');
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
