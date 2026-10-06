import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import NotificationsIndicator from '@/Components/NotificationsIndicator.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const popoverStubs = {
  Popover: {
    props: ['open'],
    emits: ['update:open'],
    template: '<div><slot /></div>',
  },
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: {
    template: '<div data-slot="popover-content" v-bind="$attrs"><slot /></div>',
  },
  Link: {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
  },
};

describe('NotificationsIndicator', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('route', vi.fn((name: string, id?: string) => id ? `/routes/${name}/${id}` : `/routes/${name}`));
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
  });

  it('renders indicator with unread count badge when there are unread notifications', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          id: 'user-1',
          name: 'Test Rep',
          unreadNotifications: [
            {
              id: 'notif-1',
              type: 'App\\Notifications\\TaskAssigned',
              data: {
                title: 'New Task Assigned',
                category: 'task',
              },
              read_at: null,
              created_at: '2026-09-24T12:00:00Z',
            },
          ],
        },
      },
    }));

    const wrapper = mount(NotificationsIndicator, {
      global: { stubs: popoverStubs },
    });

    expect(wrapper.text()).toContain('1');
    expect(wrapper.find('[data-tour="notifications-indicator"]').exists()).toBe(true);
  });

  it('renders empty state when there are no unread notifications', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          id: 'user-1',
          name: 'Test Rep',
          unreadNotifications: [],
        },
      },
    }));

    const wrapper = mount(NotificationsIndicator, {
      global: { stubs: popoverStubs },
    });

    expect(wrapper.text()).toContain('Nėra naujų pranešimų');
  });

  it('renders notification items and scroll container inside popover content', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          id: 'user-1',
          name: 'Test Rep',
          unreadNotifications: [
            {
              id: 'notif-1',
              type: 'App\\Notifications\\CommentPosted',
              data: {
                title: 'Komentaras apie posėdį',
                category: 'comment',
              },
              read_at: null,
              created_at: '2026-09-24T12:00:00Z',
            },
            {
              id: 'notif-2',
              type: 'App\\Notifications\\TaskAssigned',
              data: {
                title: 'Užduotis: paruošti ataskaitą',
                category: 'task',
              },
              read_at: null,
              created_at: '2026-09-24T11:00:00Z',
            },
          ],
        },
      },
    }));

    const wrapper = mount(NotificationsIndicator, {
      global: { stubs: popoverStubs },
    });

    const popoverContent = wrapper.find('[data-slot="popover-content"]');
    expect(popoverContent.exists()).toBe(true);
    expect(popoverContent.classes()).toContain('overflow-hidden');
    expect(popoverContent.classes()).toContain('flex-col');

    const scrollContainer = popoverContent.find('.overflow-y-auto');
    expect(scrollContainer.exists()).toBe(true);
    expect(scrollContainer.classes()).toContain('min-h-0');
    expect(scrollContainer.classes()).toContain('flex-1');

    expect(wrapper.text()).toContain('Komentaras apie posėdį');
    expect(wrapper.text()).toContain('Užduotis: paruošti ataskaitą');
  });

  it('marks single notification as read when clicking mark-as-read check button', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          id: 'user-1',
          name: 'Test Rep',
          unreadNotifications: [
            {
              id: 'notif-123',
              type: 'App\\Notifications\\TaskAssigned',
              data: {
                title: 'Užduotis',
                category: 'task',
              },
              read_at: null,
              created_at: '2026-09-24T12:00:00Z',
            },
          ],
        },
      },
    }));

    const wrapper = mount(NotificationsIndicator, {
      global: { stubs: popoverStubs },
    });

    const markReadBtn = wrapper.find('button[title="Pažymėti kaip skaitytą"]');
    expect(markReadBtn.exists()).toBe(true);

    await markReadBtn.trigger('click');
    await nextTick();

    expect(router.post).toHaveBeenCalledWith(
      '/routes/notifications.markAsRead/notif-123',
      {},
      expect.objectContaining({ preserveState: true }),
    );
  });

  it('places an accessible icon action beside the read control and visits its own destination', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          id: 'user-1',
          name: 'Test Rep',
          unreadNotifications: [{
            id: 'notif-action',
            type: 'App\\Notifications\\TaskAssignedNotification',
            data: {
              category: 'task',
              title: 'Užduotis',
              url: '/record',
              primaryAction: { label: 'Peržiūrėti užduotis', url: '/tasks' },
            },
            read_at: null,
            created_at: '2026-09-24T12:00:00Z',
          }],
        },
      },
    }));
    const wrapper = mount(NotificationsIndicator, { global: { stubs: popoverStubs } });
    const controls = wrapper.get('[data-slot="notification-row-actions"]');
    const action = controls.get('[data-slot="notification-primary-action"]');

    expect(action.text()).toBe('');
    expect(action.attributes('title')).toBe('Peržiūrėti užduotis');
    expect(action.attributes('aria-label')).toBe('Peržiūrėti užduotis');
    expect(action.find('svg').classes()).toContain('lucide-list-todo');
    expect(controls.find('button[title="Pažymėti kaip skaitytą"]').exists()).toBe(true);
    await action.trigger('click');

    expect(router.visit).toHaveBeenCalledTimes(1);
    expect(router.visit).toHaveBeenCalledWith('/tasks');
    expect(router.post).toHaveBeenCalledWith('/routes/notifications.markAsRead/notif-action', {}, expect.any(Object));
  });

  it('provides a prominent link to all notifications page in footer', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          id: 'user-1',
          name: 'Test Rep',
          unreadNotifications: [],
        },
      },
    }));

    const wrapper = mount(NotificationsIndicator, {
      global: { stubs: popoverStubs },
    });

    const viewAllLink = wrapper.find('a[href*="notifications.index"]');
    expect(viewAllLink.exists()).toBe(true);
  });

  it('keeps notification settings accessible without a spotlight', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage());

    const wrapper = mount(NotificationsIndicator, {
      global: { stubs: popoverStubs },
    });

    expect(wrapper.find('a[href*="profile.notifications"]').exists()).toBe(true);
    expect(wrapper.findComponent({ name: 'SpotlightPopover' }).exists()).toBe(false);
  });
});
