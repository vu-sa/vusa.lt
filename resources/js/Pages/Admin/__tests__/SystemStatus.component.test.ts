import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import SystemStatus from '@/Pages/Admin/SystemStatus.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const props = {
  lastUpdated: '2026-09-23T10:00:00Z',
  status: {
    redis: { status: 'healthy', connected: true, version: '7.2' },
    typesense: { status: 'error', error: 'Connection refused' },
  },
  deviceMetrics: {
    records: [
      { date: '2026-09-22', phone_logins: 5, tablet_logins: 1, desktop_logins: 9, pwa_launches: 2, total_logins: 15 },
    ],
    summary: {
      days: 30,
      total_logins: 15,
      total_phone: 5,
      total_tablet: 1,
      total_desktop: 9,
      total_pwa_launches: 2,
      phone_percentage: 33.3,
      tablet_percentage: 6.7,
      desktop_percentage: 60,
    },
  },
};

describe('SystemStatus', () => {
  it('renders every check from the eagerly loaded status prop', () => {
    const wrapper = mount(SystemStatus, { props });

    expect(wrapper.text()).toContain('Redis');
    expect(wrapper.text()).toContain('Connection refused');
    expect(wrapper.findAll('article')).toHaveLength(2);
  });

  it('shows the device split with a row per day', () => {
    const wrapper = mount(SystemStatus, { props });
    const section = wrapper.get('[data-testid="device-metrics"]');

    expect(section.text()).toContain('60%');
    expect(section.findAll('tbody tr')).toHaveLength(1);
  });
});
