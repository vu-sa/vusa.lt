import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import { router } from '@inertiajs/vue3';

import { ConfirmDialog } from '@/Components/Patterns';
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

  it('hides maintenance when the user may not run it', () => {
    const wrapper = mount(SystemStatus, { props: { ...props, maintenanceActions: [] } });

    expect(wrapper.find('[data-testid="maintenance-actions"]').exists()).toBe(false);
  });

  it('posts the confirmed maintenance action', async () => {
    const wrapper = mount(SystemStatus, {
      props: {
        ...props,
        maintenanceActions: [
          { action: 'refresh-public-content', queued: false, disruptive: false },
          { action: 'reindex-search', queued: true, disruptive: true },
        ],
      },
    });

    const rows = wrapper.findAll('[data-testid="maintenance-actions"] li');
    expect(rows).toHaveLength(2);

    await rows[1].get('button').trigger('click');
    const dialog = wrapper.findComponent(ConfirmDialog);
    expect(dialog.props('open')).toBe(true);
    expect(dialog.props('destructive')).toBe(true);

    dialog.vm.$emit('confirm');

    expect(router.post).toHaveBeenCalledWith(
      expect.anything(),
      { action: 'reindex-search' },
      expect.objectContaining({ preserveScroll: true }),
    );
  });
});
