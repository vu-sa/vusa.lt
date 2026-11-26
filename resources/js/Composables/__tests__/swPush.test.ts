import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

import { describe, expect, it, vi } from 'vitest';

const worker = readFileSync('public/sw-push.js', 'utf8');
const origin = 'https://www.vusa.test';

interface MockClient {
  url: string;
  focus: ReturnType<typeof vi.fn>;
  navigate?: ReturnType<typeof vi.fn>;
}

function setup(options: { clients?: MockClient[]; withBadgeApi?: boolean } = {}) {
  const listeners = new EventTarget();
  const focus = vi.fn().mockResolvedValue(undefined);
  const navigate = vi.fn(async (nextUrl: string) => ({ url: nextUrl, focus }));
  const openWindow = vi.fn().mockResolvedValue(undefined);
  const setAppBadge = vi.fn().mockResolvedValue(undefined);
  const clearAppBadge = vi.fn().mockResolvedValue(undefined);
  const showNotification = vi.fn().mockResolvedValue(undefined);
  const clientList = options.clients ?? [{ url: `${origin}/mano/tasks/123`, focus, navigate }];
  runInNewContext(worker, {
    URL,
    Promise,
    self: {
      location: { origin },
      navigator: options.withBadgeApi === false ? {} : { setAppBadge, clearAppBadge },
      registration: { showNotification },
      addEventListener: listeners.addEventListener.bind(listeners),
    },
    clients: { matchAll: async () => clientList, openWindow },
  });
  async function dispatch(type: string, fields: object) {
    const waitUntil = vi.fn();
    listeners.dispatchEvent(Object.assign(new Event(type), fields, { waitUntil }));
    await waitUntil.mock.calls[0]![0];
  }
  return { dispatch, focus, navigate, openWindow, setAppBadge, clearAppBadge, showNotification };
}

describe('installed app notifications', () => {
  it('focuses an exact open destination without navigating it again', async () => {
    const app = setup();
    await app.dispatch('notificationclick', { notification: { close: vi.fn(), data: { url: '/mano/tasks/123' } } });
    expect(app.focus).toHaveBeenCalledOnce();
    expect(app.navigate).not.toHaveBeenCalled();
    expect(app.openWindow).not.toHaveBeenCalled();
  });

  it('navigates and focuses an open Mano window instead of matching a prefix or opening a duplicate window', async () => {
    const app = setup();
    await app.dispatch('notificationclick', { notification: { close: vi.fn(), data: { url: '/mano/tasks/12' } } });
    expect(app.navigate).toHaveBeenCalledWith(`${origin}/mano/tasks/12`);
    expect(app.focus).toHaveBeenCalledOnce();
    expect(app.openWindow).not.toHaveBeenCalled();
  });

  it('opens a new window when only a public page is open', async () => {
    const publicFocus = vi.fn();
    const publicNavigate = vi.fn();
    const app = setup({ clients: [{ url: `${origin}/lt/naujienos`, focus: publicFocus, navigate: publicNavigate }] });
    await app.dispatch('notificationclick', { notification: { close: vi.fn(), data: { url: '/mano/tasks/12' } } });
    expect(app.openWindow).toHaveBeenCalledWith(`${origin}/mano/tasks/12`);
    expect(publicFocus).not.toHaveBeenCalled();
    expect(publicNavigate).not.toHaveBeenCalled();
  });

  it('falls back to /mano for cross-origin notification URLs', async () => {
    const app = setup({ clients: [] });
    await app.dispatch('notificationclick', { notification: { close: vi.fn(), data: { url: 'https://evil.example/phish' } } });
    expect(app.openWindow).toHaveBeenCalledWith(`${origin}/mano`);
  });

  it('updates or clears the app badge from the unread count delivered with a push', async () => {
    const app = setup();
    await app.dispatch('push', { data: { json: () => ({ title: 'Mano', data: { unreadCount: 3 } }) } });
    expect(app.setAppBadge).toHaveBeenCalledWith(3);
    expect(app.showNotification).toHaveBeenCalledOnce();

    await app.dispatch('push', { data: { json: () => ({ title: 'Mano', data: { unreadCount: 0 } }) } });
    expect(app.clearAppBadge).toHaveBeenCalledOnce();
  });

  it('handles push events cleanly when the Badging API is unavailable', async () => {
    const app = setup({ withBadgeApi: false });
    await app.dispatch('push', { data: { json: () => ({ title: 'Mano', data: { unreadCount: 2 } }) } });
    expect(app.showNotification).toHaveBeenCalledOnce();
  });
});
