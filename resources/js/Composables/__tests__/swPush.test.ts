import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { describe, expect, it, vi } from 'vitest';

const worker = readFileSync('public/sw-push.js', 'utf8');
const origin = 'https://www.vusa.test';

function setup() {
  const listeners = new EventTarget();
  const focus = vi.fn();
  const openWindow = vi.fn();
  const setAppBadge = vi.fn().mockResolvedValue(undefined);
  const showNotification = vi.fn();
  runInNewContext(worker, {
    URL,
    self: {
      location: { origin },
      navigator: { setAppBadge },
      registration: { showNotification },
      addEventListener: listeners.addEventListener.bind(listeners),
    },
    clients: { matchAll: async () => [{ url: origin + '/mano/tasks/123', focus }], openWindow },
  });
  async function dispatch(type: string, fields: object) {
    const waitUntil = vi.fn();
    listeners.dispatchEvent(Object.assign(new Event(type), fields, { waitUntil }));
    await waitUntil.mock.calls[0]![0];
  }
  return { dispatch, focus, openWindow, setAppBadge, showNotification };
}

describe('installed app notifications', () => {
  it.each(['/mano/tasks/12', '/mano/tasks/123'])('opens the exact destination %s', async (url) => {
    const app = setup();
    await app.dispatch('notificationclick', { notification: { close: vi.fn(), data: { url } } });
    if (url.endsWith('/123')) {
      expect(app.focus).toHaveBeenCalledOnce();
    }
    else {
      expect(app.openWindow).toHaveBeenCalledWith(origin + url);
      expect(app.focus).not.toHaveBeenCalled();
    }
  });

  it('updates the app badge from the unread count delivered with a push', async () => {
    const app = setup();
    await app.dispatch('push', { data: { json: () => ({ title: 'Mano', data: { unreadCount: 3 } }) } });
    expect(app.setAppBadge).toHaveBeenCalledWith(3);
    expect(app.showNotification).toHaveBeenCalledOnce();
  });
});
