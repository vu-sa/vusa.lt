import { router, usePage } from '@inertiajs/vue3';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { ref } from 'vue';
import { mount, flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import BrowserNotificationSetup from '../BrowserNotificationSetup.vue';

const pwa = vi.hoisted(() => ({ subscribeToPush: vi.fn() }));
const state = {
  isPWA: ref(false), pushSupported: ref(true), pushPermission: ref<NotificationPermission>('default'),
  hasPushSubscription: ref(false), canSubscribeToPush: ref(true), serviceWorkerReady: ref(true), isSubscribingToPush: ref(false),
};
vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ ...state, ...pwa }) }));

beforeEach(() => {
  state.isPWA.value = false;
  state.pushSupported.value = true;
  state.pushPermission.value = 'default';
  state.hasPushSubscription.value = false;
  state.canSubscribeToPush.value = true;
  state.serviceWorkerReady.value = true;
  pwa.subscribeToPush.mockReset().mockResolvedValue(true);
  vi.mocked(router.reload).mockClear();
});

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));

describe('BrowserNotificationSetup', () => {
  it('requests permission only after a user action', async () => {
    const wrapper = mount(BrowserNotificationSetup);
    expect(pwa.subscribeToPush).not.toHaveBeenCalled();
    await wrapper.find('[data-testid="enable-browser-notifications"]').trigger('click');
    expect(pwa.subscribeToPush).toHaveBeenCalledOnce();
    await flushPromises();
    expect(router.reload).toHaveBeenCalledWith({ only: ['path', 'step'] });
  });

  it('offers recovery for denied permission and an explanation for unsupported browsers', () => {
    state.pushPermission.value = 'denied';
    state.canSubscribeToPush.value = false;
    const blocked = mount(BrowserNotificationSetup);
    expect(blocked.text()).toContain('learning.path.push.unblock');
    expect(blocked.find('[data-testid="enable-browser-notifications"]').exists()).toBe(false);
    state.pushSupported.value = false;
    expect(mount(BrowserNotificationSetup).text()).toContain('learning.path.push.unsupported_help');
  });

  it('reports current-device status without requesting another subscription', () => {
    state.hasPushSubscription.value = true;
    const wrapper = mount(BrowserNotificationSetup);
    expect(wrapper.text()).toContain('learning.path.push.enabled_here');
    expect(wrapper.find('[data-testid="enable-browser-notifications"]').exists()).toBe(false);
  });

  it('reports activation failure without claiming that notifications are enabled', async () => {
    pwa.subscribeToPush.mockResolvedValue(false);
    const wrapper = mount(BrowserNotificationSetup);
    await wrapper.find('[data-testid="enable-browser-notifications"]').trigger('click');
    await flushPromises();
    expect(wrapper.find('[role="alert"]').text()).toBe('learning.path.push.failed');
    expect(wrapper.find('[role="status"]').text()).toBe('learning.path.push.not_enabled_here');
  });

  it('allows progress to be saved again when subscription succeeded but saving the step failed', async () => {
    state.hasPushSubscription.value = true;
    const wrapper = mount(BrowserNotificationSetup, { props: { needsConfirmation: true } });
    await wrapper.find('[data-testid="confirm-browser-notifications"]').trigger('click');
    expect(wrapper.emitted('confirm')).toHaveLength(1);
    expect(pwa.subscribeToPush).not.toHaveBeenCalled();
  });
});
