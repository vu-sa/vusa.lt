import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { Notification, NotificationData } from '@/Composables/useNotificationFormatting';
import NotificationCard from '@/Features/Admin/Notifications/NotificationCard.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const make = (data: NotificationData, readAt: string | null = null): Notification => ({
  id: 'n1',
  type: 'App\\Notifications\\MeetingReminderNotification',
  data: { category: 'meeting', title: 'Posėdis netrukus', body: 'Senatas', url: '/meetings/1', ...data },
  created_at: '2026-09-20T10:00:00Z',
  read_at: readAt,
});

const mountCard = (notification: Notification) => mount(NotificationCard, {
  props: { notification },
  global: { stubs: commonStubs },
});

describe('NotificationCard', () => {
  beforeEach(() => {
    vi.mocked(router.visit).mockClear();
  });

  it('renders context rows as label/value pairs', () => {
    const wrapper = mountCard(make({
      context: [
        { label: 'Institucija', value: 'VU Senatas' },
        { label: 'Laikas', value: '2026-09-21 10:00' },
      ],
    }));

    const context = wrapper.find('[data-slot="notification-context"]');

    expect(context.exists()).toBe(true);
    expect(context.findAll('dt').map(el => el.text())).toEqual(['Institucija', 'Laikas']);
    expect(context.findAll('dd').map(el => el.text())).toEqual(['VU Senatas', '2026-09-21 10:00']);
  });

  it('renders no context block and no actions when the notification only reports something', () => {
    const wrapper = mountCard(make({}));

    expect(wrapper.find('[data-slot="notification-context"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="notification-actions"]').exists()).toBe(false);
  });

  it('renders the primary action and, for a binary answer, the secondary one', () => {
    const wrapper = mountCard(make({
      primaryAction: { label: 'Registruoti posėdį', url: '/register' },
      secondaryAction: { label: 'Pranešti apie veiklą', url: '/report' },
    }));

    expect(wrapper.find('[data-slot="notification-actions"]').findAll('button').map(el => el.text()))
      .toEqual(['Registruoti posėdį', 'Pranešti apie veiklą']);
  });

  it('shows the action of a row stored before the contract existed', () => {
    const wrapper = mountCard(make({ actions: [{ label: 'Peržiūrėti', url: '/legacy' }] }));

    expect(wrapper.find('[data-slot="notification-actions"] button').text()).toBe('Peržiūrėti');
  });

  it('visits the action url, not the card url, and marks an unread card read', async () => {
    const wrapper = mountCard(make({ primaryAction: { label: 'Registruoti posėdį', url: '/register' } }));

    await wrapper.find('[data-slot="notification-actions"] button').trigger('click');

    expect(router.visit).toHaveBeenCalledTimes(1);
    expect(router.visit).toHaveBeenCalledWith('/register');
    expect(wrapper.emitted('markAsRead')).toEqual([['n1']]);
  });

  it('does not re-mark an already read card when its action is used', async () => {
    const wrapper = mountCard(make({ primaryAction: { label: 'Atidaryti', url: '/x' } }, '2026-09-20T11:00:00Z'));

    await wrapper.find('[data-slot="notification-actions"] button').trigger('click');

    expect(wrapper.emitted('markAsRead')).toBeUndefined();
    expect(router.visit).toHaveBeenCalledWith('/x');
  });
});
