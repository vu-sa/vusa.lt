/**
 * Custom push notification handler for service worker
 * This file is imported by the main service worker
 */

// Handle push notifications
self.addEventListener('push', (event) => {
  let data = {
    title: 'VU SA',
    body: 'Gavote naują pranešimą',
    icon: '/images/icons/favicons/favicon-196x196.png',
    // No default badge: Android draws it as a monochrome mask, so a colour logo becomes a white square.
    badge: undefined,
    url: '/mano',
  };

  // Try to parse the push data
  if (event.data) {
    try {
      const payload = event.data.json();

      data = {
        title: payload.title || data.title,
        body: payload.body || data.body,
        icon: payload.icon || data.icon,
        badge: payload.badge || data.badge,
        url: payload.url || payload.data?.url || data.url,
        tag: payload.tag,
        data: payload.data || payload,
      };
    }
    catch {
      // If not JSON, use the text directly
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body,
    icon: data.icon,
    badge: data.badge,
    tag: data.tag,
    data: { url: data.url, ...data.data },
    vibrate: [100, 50, 100],
    requireInteraction: true,
  };

  const { unreadCount } = options.data;
  const badgeUpdate = Number.isSafeInteger(unreadCount) && unreadCount >= 0
    ? Promise.resolve(
        unreadCount > 0
          ? self.navigator?.setAppBadge?.(unreadCount)
          : self.navigator?.clearAppBadge?.(),
      ).catch(() => {})
    : Promise.resolve();

  event.waitUntil(
    Promise.all([
      self.registration.showNotification(data.title, options),
      badgeUpdate,
    ]),
  );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  let url = new URL('/mano', self.location.origin);
  try {
    const target = new URL(event.notification.data?.url || '/mano', self.location.origin);
    if (target.origin === url.origin) url = target;
  }
  catch { /* Malformed notification links open the dashboard. */ }

  const isAdminUrl = (candidate) => {
    try {
      const parsed = new URL(candidate, self.location.origin);
      return parsed.origin === url.origin && (parsed.pathname === '/mano' || parsed.pathname.startsWith('/mano/'));
    }
    catch {
      return false;
    }
  };

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // Exact match: bring the already-open destination straight to the front.
      for (const client of clientList) {
        if (client.url === url.href && 'focus' in client) {
          return client.focus();
        }
      }
      // Reuse an open Mano window (e.g. an installed phone PWA) instead of spawning duplicate windows.
      for (const client of clientList) {
        if (isAdminUrl(client.url) && 'navigate' in client && 'focus' in client) {
          return Promise.resolve(client.navigate(url.href))
            .then(navigated => (navigated || client).focus())
            .catch(() => clients.openWindow?.(url.href));
        }
      }
      // If no Mano window can be reused, open a new one.
      if (clients.openWindow) {
        return clients.openWindow(url.href);
      }
    }),
  );
});

// Handle notification close
self.addEventListener('notificationclose', () => {
  // You can perform actions when the notification is dismissed if needed
});
