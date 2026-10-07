import { Link, router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { Notification, NotificationData } from '@/Composables/useNotificationFormatting';
import NotificationCard from '@/Features/Admin/Notifications/NotificationCard.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const make = (data: NotificationData, readAt: string | null = null): Notification => ({
  id: 'n1',
  type: 'App\\Notifications\\MeetingReminderNotification',
  data: { category: 'meeting', title: 'Posėdis netrukus', body: 'Senatas', url: '/mano/meetings/1', ...data },
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
    expect(wrapper.find('[data-slot="notification-primary-action"]').exists()).toBe(false);
  });

  it('renders the primary action and, for a binary answer, the secondary one', () => {
    const wrapper = mountCard(make({
      primaryAction: { label: 'Registruoti posėdį', url: '/register' },
      secondaryAction: { label: 'Pranešti apie veiklą', url: '/report' },
    }));

    const action = wrapper.get('[data-slot="notification-primary-action"]');
    expect(action.text()).toBe('');
    expect(action.attributes('aria-label')).toBe('Registruoti posėdį');
    expect(action.attributes('title')).toBe('Registruoti posėdį');
    expect(wrapper.get('[data-slot="notification-row-actions"]').find('button[title="Pažymėti kaip skaitytą"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Pranešti apie veiklą');
  });

  it('shows the action of a row stored before the contract existed', () => {
    const wrapper = mountCard(make({ actions: [{ label: 'Peržiūrėti', url: '/legacy' }] }));

    expect(wrapper.find('[data-slot="notification-primary-action"]').attributes('aria-label')).toBe('Peržiūrėti');
  });

  it('links to the action url, not the card url, and marks an unread card read', async () => {
    const wrapper = mountCard(make({ primaryAction: { label: 'Registruoti posėdį', url: '/mano/register' } }));

    const action = wrapper.get('[data-slot="notification-primary-action"]');
    await action.trigger('click');

    expect(action.attributes('href')).toBe('/mano/register');
    expect(action.findComponent(Link).exists()).toBe(true);
    expect(wrapper.emitted('markAsRead')).toEqual([['n1']]);
  });

  it('omits the open arrow when it duplicates the primary action destination', () => {
    const wrapper = mountCard(make({ primaryAction: { label: 'Peržiūrėti', url: '/mano/meetings/1' } }));

    expect(wrapper.find('a[title="Atidaryti"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="notification-primary-action"]').exists()).toBe(true);
  });

  it('keeps the open arrow when the primary action leads elsewhere', () => {
    const wrapper = mountCard(make({ primaryAction: { label: 'Registruoti', url: '/register' } }));

    expect(wrapper.find('a[title="Atidaryti"]').attributes('href')).toBe('/mano/meetings/1');
  });

  it('does not re-mark an already read card when its action is used', async () => {
    const wrapper = mountCard(make({ primaryAction: { label: 'Atidaryti', url: '/mano/x' } }, '2026-09-20T11:00:00Z'));

    await wrapper.find('[data-slot="notification-primary-action"]').trigger('click');

    expect(wrapper.emitted('markAsRead')).toBeUndefined();
    expect(wrapper.get('[data-slot="notification-primary-action"]').attributes('href')).toBe('/mano/x');
  });

  it.each([
    '/atsakymas/1?expires=123&signature=abc',
    `${window.location.origin}/atsakymas/1?expires=123&signature=abc`,
    '/lt/naujiena/sveiki',
    'https://other.example/mano/meetings/1',
  ])('uses native links for all notification destinations at %s', async (url) => {
    const wrapper = mountCard(make({
      title: 'Ar vyko posėdis?',
      url,
      primaryAction: { label: 'Registruoti posėdį', url: `${url}&answer=met` },
      secondaryAction: { label: 'Pranešti apie veiklą', url: `${url}&answer=not_met` },
    }));

    expect(wrapper.findComponent(Link).exists()).toBe(false);
    expect(wrapper.get('h4 a').attributes('href')).toBe(url);
    expect(wrapper.get('[data-slot="notification-primary-action"]').attributes('href')).toBe(`${url}&answer=met`);
    expect(wrapper.get('a[title="Atidaryti"]').attributes('href')).toBe(url);
    expect(wrapper.findAll('a').map(link => link.attributes('href'))).toContain(`${url}&answer=not_met`);

    const action = wrapper.get('[data-slot="notification-primary-action"]');
    action.element.addEventListener('click', event => event.preventDefault());
    await action.trigger('click');

    expect(wrapper.emitted('markAsRead')).toEqual([['n1']]);
    expect(router.visit).not.toHaveBeenCalled();
  });
});
