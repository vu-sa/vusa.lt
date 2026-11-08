import { Link, router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import type { Notification } from '@/Composables/useNotificationFormatting';

import NotificationToast from '../NotificationToast.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('NotificationToast', () => {
  it.each(['body', 'text', 'message'] as const)('renders a stored notification %s as text', (field) => {
    const body = '<strong>Ataskaita</strong> & aptarimas';
    const notification: Notification = {
      id: 'text-body', type: 'App\\Notifications\\TaskOverdueNotification',
      data: { title: 'Užduotis', category: 'task', [field]: body },
      created_at: '2026-11-06T10:00:00Z', read_at: null,
    };
    const wrapper = mount(NotificationToast, { props: { notification } });
    expect(wrapper.text()).toContain(body);
    expect(wrapper.find('strong').exists()).toBe(false);
  });
  it.each([
    ['/atsakymas/1?answer=met&signature=abc', false],
    ['/lt/naujiena/sveiki', false],
    ['https://other.example/mano/meetings/1', false],
    ['/mano/meetings/1', true],
  ])('uses the correct navigation for %s', (url, inertia) => {
    vi.mocked(router.visit).mockClear();
    const notification: Notification = {
      id: 'n1',
      type: 'App\\Notifications\\InstitutionActivityNotification',
      data: { title: 'Ar vyko posėdis?', category: 'meeting', url },
      created_at: '2026-10-08T10:00:00Z',
      read_at: null,
    };
    const wrapper = mount(NotificationToast, { props: { notification } });

    expect(wrapper.findComponent(Link).exists()).toBe(inertia);
    expect(wrapper.get('a').attributes('href')).toBe(url);
    expect(wrapper.get('a').attributes('aria-label')).toBe('Peržiūrėti');
    expect(router.visit).not.toHaveBeenCalled();
  });

  it('has no navigation action without a destination', () => {
    expect(mount(NotificationToast).find('a').exists()).toBe(false);
  });
});
