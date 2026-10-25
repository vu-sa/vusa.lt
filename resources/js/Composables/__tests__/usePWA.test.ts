import { beforeEach, describe, expect, test, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { activateWaitingWorker, migrateLegacyRegistration, usePWA } from '@/Composables/usePWA';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('vue-sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

const VAPID_KEY = 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U';

function fakeSubscription(endpoint: string) {
  return {
    endpoint,
    unsubscribe: vi.fn().mockResolvedValue(true),
    toJSON: () => ({ endpoint, keys: { p256dh: 'p256dh', auth: 'auth' } }),
  };
}

function fakeRegistration(scope: string, subscription: ReturnType<typeof fakeSubscription> | null = null) {
  return {
    scope,
    active: {},
    unregister: vi.fn().mockResolvedValue(true),
    pushManager: {
      getSubscription: vi.fn().mockResolvedValue(subscription),
      subscribe: vi.fn(),
    },
  };
}

function stubRegistrations(registrations: ReturnType<typeof fakeRegistration>[]) {
  Object.defineProperty(navigator, 'serviceWorker', {
    value: { getRegistrations: vi.fn().mockResolvedValue(registrations) },
    configurable: true,
  });
}

describe('activateWaitingWorker', () => {
  test('reloads when no new worker is waiting', async () => {
    const reload = vi.fn();

    await activateWaitingWorker(reload);

    expect(reload).toHaveBeenCalledOnce();
  });
});

describe('migrateLegacyRegistration', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ csrf_token: 'csrf', pwa: { vapidPublicKey: VAPID_KEY } }));
    vi.stubGlobal('Notification', { permission: 'granted' });
    vi.stubGlobal('PushManager', { supportedContentEncodings: ['aes128gcm'] });
    mockFetch.mockResolvedValue({ ok: true, status: 200 });
  });

  test('moves the push subscription from the /build/ worker to the current one', async () => {
    const legacySubscription = fakeSubscription('https://push.example/old');
    const legacy = fakeRegistration('https://www.vusa.test/build/', legacySubscription);
    const current = fakeRegistration('https://www.vusa.test/mano');
    current.pushManager.subscribe.mockResolvedValue(fakeSubscription('https://push.example/new'));
    stubRegistrations([legacy, current]);

    await migrateLegacyRegistration(current as unknown as ServiceWorkerRegistration);

    expect(current.pushManager.subscribe).toHaveBeenCalledOnce();
    expect(mockFetch).toHaveBeenCalledWith('/mocked-route/api.v1.admin.pushSubscriptions.store', expect.objectContaining({
      method: 'POST',
      headers: expect.objectContaining({ Accept: 'application/json', 'X-CSRF-TOKEN': 'csrf' }),
      body: expect.any(String),
    }));
    expect(mockFetch).toHaveBeenCalledWith('/mocked-route/api.v1.admin.pushSubscriptions.destroy', expect.objectContaining({
      method: 'DELETE',
      body: JSON.stringify({ endpoint: 'https://push.example/old' }),
    }));
    expect(legacySubscription.unsubscribe).toHaveBeenCalledOnce();
    expect(legacy.unregister).toHaveBeenCalledOnce();
    expect(usePWA().currentBrowserEndpoint.value).toBe('https://push.example/new');
  });

  test('only unregisters a /build/ worker that has no subscription', async () => {
    const legacy = fakeRegistration('https://www.vusa.test/build/');
    const current = fakeRegistration('https://www.vusa.test/mano');
    stubRegistrations([legacy, current]);

    await migrateLegacyRegistration(current as unknown as ServiceWorkerRegistration);

    expect(current.pushManager.subscribe).not.toHaveBeenCalled();
    expect(mockFetch).not.toHaveBeenCalled();
    expect(legacy.unregister).toHaveBeenCalledOnce();
  });

  test('drops the old subscription without re-subscribing when notifications are no longer allowed', async () => {
    vi.stubGlobal('Notification', { permission: 'denied' });
    const legacySubscription = fakeSubscription('https://push.example/old');
    const legacy = fakeRegistration('https://www.vusa.test/build/', legacySubscription);
    const current = fakeRegistration('https://www.vusa.test/mano');
    stubRegistrations([legacy, current]);

    await migrateLegacyRegistration(current as unknown as ServiceWorkerRegistration);

    expect(current.pushManager.subscribe).not.toHaveBeenCalled();
    expect(mockFetch).toHaveBeenCalledWith('/mocked-route/api.v1.admin.pushSubscriptions.destroy', expect.objectContaining({ method: 'DELETE' }));
    expect(legacy.unregister).toHaveBeenCalledOnce();
  });

  test('keeps the /build/ worker when the new subscription fails, so the next load retries', async () => {
    const legacy = fakeRegistration('https://www.vusa.test/build/', fakeSubscription('https://push.example/old'));
    const current = fakeRegistration('https://www.vusa.test/mano');
    current.pushManager.subscribe.mockRejectedValue(new Error('push service unavailable'));
    stubRegistrations([legacy, current]);
    vi.spyOn(console, 'warn').mockImplementation(() => undefined);

    await migrateLegacyRegistration(current as unknown as ServiceWorkerRegistration);

    expect(legacy.unregister).not.toHaveBeenCalled();
  });

  test('keeps the legacy subscription when the API rejects the new subscription', async () => {
    const legacySubscription = fakeSubscription('https://push.example/old');
    const legacy = fakeRegistration('https://www.vusa.test/build/', legacySubscription);
    const current = fakeRegistration('https://www.vusa.test/mano');
    current.pushManager.subscribe.mockResolvedValue(fakeSubscription('https://push.example/new'));
    mockFetch.mockResolvedValueOnce({ ok: false, status: 403 });
    stubRegistrations([legacy, current]);
    vi.spyOn(console, 'warn').mockImplementation(() => undefined);

    await migrateLegacyRegistration(current as unknown as ServiceWorkerRegistration);

    expect(mockFetch).toHaveBeenCalledOnce();
    expect(legacySubscription.unsubscribe).not.toHaveBeenCalled();
    expect(legacy.unregister).not.toHaveBeenCalled();
  });

  test('reads device subscriptions from the API response envelope', async () => {
    const device = { id: 1, endpoint: 'https://push.example/device', device_name: 'Phone' };
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ success: true, data: [device] }),
    });

    const subscriptions = await usePWA().fetchPushSubscriptions();

    expect(mockFetch).toHaveBeenCalledWith('/mocked-route/api.v1.admin.pushSubscriptions.index', expect.objectContaining({
      headers: expect.objectContaining({ Accept: 'application/json' }),
    }));
    expect(subscriptions).toEqual([expect.objectContaining(device)]);
  });

});
