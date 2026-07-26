import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ref } from 'vue';

import ContentAnalyticsCard from '../ContentAnalyticsCard.vue';

import { commonStubs } from '@/tests/stubs';
import type { ContentAnalyticsData } from '@/Types/api.d';

const mockController = {
  data: ref<ContentAnalyticsData | null>(null),
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

const withViews: ContentAnalyticsData = {
  available: true,
  path: '/lt/naujiena/testine',
  totals: { pageviews: 340, visitors: 210, visits: 260 },
  dataSince: '2026-07-26',
};

const mountCard = (props: Record<string, unknown> = {}) => mount(ContentAnalyticsCard, {
  props: { type: 'news', id: 1, ...props },
  global: { stubs: commonStubs },
});

describe('ContentAnalyticsCard', () => {
  beforeEach(() => {
    mockController.data.value = null;
    mockController.isFetching.value = false;
  });

  it('shows the view and visitor counts', () => {
    mockController.data.value = withViews;

    const wrapper = mountCard({ contentDate: '2026-08-01T10:00:00Z' });

    expect(wrapper.text()).toContain('340');
    expect(wrapper.text()).toContain('210');
  });

  it('warns when the content predates the start of tracking', () => {
    mockController.data.value = withViews;

    // Published in March, tracking began 26 July — the total is a floor, not a lifetime figure.
    const wrapper = mountCard({ contentDate: '2026-03-01T10:00:00Z' });

    expect(wrapper.text()).toContain('analytics.partial_tooltip');
  });

  it('does not warn for content published after tracking began', () => {
    mockController.data.value = withViews;

    const wrapper = mountCard({ contentDate: '2026-08-01T10:00:00Z' });

    expect(wrapper.text()).not.toContain('analytics.partial_tooltip');
  });

  it('does not warn when the content has no date', () => {
    mockController.data.value = withViews;

    const wrapper = mountCard({ contentDate: null });

    expect(wrapper.text()).not.toContain('analytics.partial_tooltip');
  });

  it('shows a skeleton while loading', () => {
    mockController.isFetching.value = true;

    const wrapper = mountCard();

    expect(wrapper.findAll('[data-slot="skeleton"], .animate-pulse').length).toBeGreaterThan(0);
  });

  it('degrades quietly when umami is unreachable', () => {
    mockController.data.value = { available: false, path: null, totals: null, dataSince: '2026-07-26' };

    const wrapper = mountCard({ contentDate: '2026-08-01T10:00:00Z' });

    expect(wrapper.text()).toContain('analytics.unavailable_title');
  });
});
