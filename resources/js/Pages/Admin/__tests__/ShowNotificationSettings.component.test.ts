import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

import ShowNotificationSettings from '../ShowNotificationSettings.vue';

import { Switch } from '@/Components/ui/switch';
import { commonStubs } from '@/tests/stubs';

const patchMock = vi.fn();
vi.mock('@inertiajs/vue3', async () => {
  const actual = await import('@/mocks/inertia.mock');
  return {
    ...actual,
    useForm: (initialData: Record<string, unknown>) => {
      const form = actual.useForm(initialData);
      form.patch = patchMock;
      return form;
    },
  };
});

vi.mock('@/Composables/usePWA', () => ({
  usePWA: () => ({
    pushSupported: { value: true },
    pushPermission: { value: 'granted' },
    canSubscribeToPush: { value: false },
    hasPushSubscription: { value: true },
    hasAnyPushSubscription: { value: true },
    isSubscribingToPush: { value: false },
    isUnsubscribingFromPush: { value: false },
    isRefreshingSubscriptionStatus: { value: false },
    subscribeToPush: vi.fn(),
    unsubscribeFromPush: vi.fn(),
    removeSubscriptionById: vi.fn(),
    fetchPushSubscriptions: vi.fn().mockResolvedValue([]),
    refreshSubscriptionStatus: vi.fn(),
  }),
}));

const props = {
  notificationPreferences: {
    channels: {
      comment: { in_app: true, email_digest: true, push: false },
      task: { in_app: true, email_digest: false, push: true },
    },
    digest_frequency_hours: 4,
    digest_emails: ['test@example.com'],
    muted_until: null,
    muted_threads: {},
    reminder_settings: {
      task_reminder_days: [7, 3, 1],
      meeting_reminder_hours: [24, 1],
    },
  },
  notificationCategories: {
    comment: { value: 'comment', modelEnumKey: 'COMMENT', color: 'neutral' },
    task: { value: 'task', modelEnumKey: 'TASK', color: 'blue' },
  },
  notificationChannels: {
    in_app: { value: 'in_app', enabledByDefault: true },
    email_digest: { value: 'email_digest', enabledByDefault: true },
    push: { value: 'push', enabledByDefault: true },
  },
  availableDigestEmails: [
    { email: 'test@example.com', label: 'Test Email', type: 'user' as const },
    { email: 'duty@example.com', label: 'Duty Email', type: 'duty' as const },
  ],
};

afterEach(() => {
  window.history.replaceState({}, '', '/');
  patchMock.mockClear();
});

describe('ShowNotificationSettings', () => {
  it('renders FormPage with two-column layout sections and aside panels', () => {
    const wrapper = mount(ShowNotificationSettings, {
      props,
      global: {
        stubs: {
          ...commonStubs,
          PushDeviceManagement: { template: '<div data-testid="push-device-management" />' },
        },
      },
    });

    // Form page renders
    expect(wrapper.find('[data-slot="form-page"]').exists()).toBe(true);

    // Form sections render (Category & reminders, Digest)
    const sections = wrapper.findAll('[data-slot="form-section"]');
    expect(sections.length).toBe(2);
    expect(wrapper.text()).toContain('Kokie pranešimai tave pasiekia?');
    expect(wrapper.text()).toContain('El. pašto suvestinė');
    expect(wrapper.text()).not.toContain('Kada priminti apie terminus ir posėdžius?');
    expect(wrapper.text()).not.toContain('Pasirink, kokiomis temomis ir kanalais nori gauti pranešimus.');

    // Aside slot renders with panels
    const aside = wrapper.find('[data-testid="form-page-aside"]');
    expect(aside.exists()).toBe(true);

    const panels = aside.findAll('[data-slot="form-panel"]');
    expect(panels.length).toBeGreaterThanOrEqual(2);

    // Push device management is present in aside
    expect(aside.find('[data-testid="push-device-management"]').exists()).toBe(true);
  });

  it('renders category rows in category matrix table', () => {
    const wrapper = mount(ShowNotificationSettings, {
      props,
      global: {
        stubs: {
          ...commonStubs,
          PushDeviceManagement: true,
        },
      },
    });

    const rows = wrapper.findAll('tbody tr');
    expect(rows.length).toBe(2);
  });

  it('renders active muted banner when muted_until is present', () => {
    const wrapper = mount(ShowNotificationSettings, {
      props: {
        ...props,
        notificationPreferences: {
          ...props.notificationPreferences,
          muted_until: '2026-09-25T12:00:00Z',
        },
      },
      global: {
        stubs: {
          ...commonStubs,
          PushDeviceManagement: true,
        },
      },
    });

    const aside = wrapper.find('[data-testid="form-page-aside"]');
    expect(aside.text()).toContain('notifications.preferences.muted_until');
    expect(aside.text()).toContain('notifications.preferences.unmute');
  });

  it('submits the form using form.patch on FormPage submit', async () => {
    const wrapper = mount(ShowNotificationSettings, {
      props,
      global: {
        stubs: {
          ...commonStubs,
          PushDeviceManagement: true,
        },
      },
    });

    // Form element submit
    const form = wrapper.find('form');
    await form.trigger('submit');

    expect(patchMock).toHaveBeenCalledTimes(1);
  });

  it('shows followed-institution push on until it was saved off', () => {
    const mountWith = (followed?: { push: boolean }) => mount(ShowNotificationSettings, {
      props: { ...props, notificationPreferences: { ...props.notificationPreferences, followed_institutions: followed } },
      global: { stubs: { ...commonStubs, PushDeviceManagement: true } },
    });

    expect(mountWith().findComponent(Switch).props('modelValue')).toBe(true);
    expect(mountWith({ push: false }).findComponent(Switch).props('modelValue')).toBe(false);
    expect(mountWith({ push: false }).text()).toContain('Tik pranešimų centre ir suvestinėje');
  });
});
