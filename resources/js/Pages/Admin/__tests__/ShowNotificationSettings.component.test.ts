import { mount } from '@vue/test-utils';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ShowNotificationSettings from '../ShowNotificationSettings.vue';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
// The real composable imports `virtual:pwa-register`; this reads the same shared prop it does.
vi.mock('@/Composables/usePWA', async () => {
  const { computed } = await import('vue');
  const inertia = await import('@inertiajs/vue3');
  return {
    usePWA: () => ({
      hasAnyPushSubscription: computed(() => Boolean((inertia.usePage().props.pwa as { hasPushSubscription?: boolean } | undefined)?.hasPushSubscription)),
    }),
  };
});

const type = (value: string, section: string, overrides: Record<string, unknown> = {}) => ({
  value,
  section,
  sectionColor: 'neutral',
  sectionModelEnumKey: 'TASK',
  lockedEmail: null,
  email: 'digest',
  push: false,
  ...overrides,
});

const props = {
  notificationTypes: [
    type('task_assigned', 'task'),
    type('task_reminder', 'task', { email: 'immediate', push: true }),
    type('member_registration', 'registration', { lockedEmail: 'immediate', email: 'immediate' }),
  ],
  notificationPreferences: {
    digest_frequency_hours: 4,
    emails: [],
    muted_until: null,
    reminder_settings: { task_reminder_days: [7, 3, 1], meeting_reminder_hours: [24, 1] },
  },
  availableEmails: [
    { email: 'test@example.com', label: 'Test', type: 'user' as const },
    { email: 'duty@vusa.lt', label: 'Duty', type: 'duty' as const },
  ],
  defaultEmail: 'duty@vusa.lt',
};

// Menu contents render in a portal; inline stand-ins keep the section shortcut reachable.
const menuStubs = {
  DropdownMenu: { template: '<div><slot /></div>' },
  DropdownMenuTrigger: { template: '<div><slot /></div>' },
  DropdownMenuContent: { template: '<div><slot /></div>' },
  DropdownMenuLabel: { template: '<div><slot /></div>' },
  DropdownMenuSeparator: { template: '<hr />' },
  DropdownMenuItem: { emits: ['select'], template: '<button type="button" @click="$emit(\'select\')"><slot /></button>' },
};

const mountPage = (overrides: Partial<typeof props> = {}) => mount(ShowNotificationSettings, {
  props: { ...props, ...overrides },
  global: { stubs: { ...commonStubs, ...menuStubs, PushDeviceManagement: true } },
});

const lastForm = () => vi.mocked(useForm).mock.results.at(-1)!.value;

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage({ pwa: { hasPushSubscription: true } }));
});

afterEach(() => {
  vi.clearAllMocks();
});

describe('ShowNotificationSettings', () => {
  it('renders one panel per section with a row per type', () => {
    const wrapper = mountPage();

    expect(wrapper.findAll('[data-slot="notification-section"]')).toHaveLength(2);
    expect(wrapper.findAll('[data-slot="notification-type-row"]').map(row => row.attributes('data-type')))
      .toEqual(['task_assigned', 'task_reminder', 'member_registration']);
  });

  it('sets every editable type of a section from the section menu', async () => {
    const wrapper = mountPage();

    await wrapper.find('[data-section-email="off"]').trigger('click');

    expect(lastForm().types.task_assigned.email).toBe('off');
    expect(lastForm().types.task_reminder.email).toBe('off');
    expect(lastForm().types.member_registration.email).toBe('immediate');
  });

  it('disables push choices and says why when no device is connected', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ pwa: { hasPushSubscription: false } }));
    const wrapper = mountPage();

    expect(wrapper.find('[data-testid="push-unavailable"]').exists()).toBe(true);
    expect(wrapper.find('[data-type="task_reminder"] [data-testid="push-toggle"]').attributes('disabled')).toBeDefined();
  });

  it('mutes immediately through its own endpoint', async () => {
    const wrapper = mountPage();

    await wrapper.find('[data-testid="mute-4"]').trigger('click');

    expect(router.patch).toHaveBeenCalledWith(route('profile.muteNotifications'), { hours: 4 }, expect.any(Object));
  });

  it('unmutes without submitting the settings form, which would cancel the request', async () => {
    const wrapper = mountPage({
      notificationPreferences: { ...props.notificationPreferences, muted_until: '2099-01-01T12:00:00+00:00' },
    });

    expect(wrapper.find('[data-testid="muted-banner"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="unmute"]').attributes('type')).toBe('button');

    await wrapper.find('[data-testid="unmute"]').trigger('click');

    expect(router.patch).toHaveBeenCalledWith(route('profile.muteNotifications'), { hours: null }, expect.any(Object));
    expect(lastForm().patch).not.toHaveBeenCalled();
  });

  it('restores the defaults after confirming', async () => {
    const wrapper = mountPage();

    await wrapper.find('[data-testid="reset-defaults"]').trigger('click');
    wrapper.findComponent({ name: 'ConfirmDialog' }).vm.$emit('confirm');

    expect(router.delete).toHaveBeenCalledWith(route('profile.resetNotificationPreferences'), expect.objectContaining({ preserveState: false }));
  });

  it('submits the form with form.patch', async () => {
    const wrapper = mountPage();

    await wrapper.find('form').trigger('submit');

    expect(lastForm().patch).toHaveBeenCalledWith(route('profile.updateNotificationPreferences'), expect.any(Object));
  });
});
