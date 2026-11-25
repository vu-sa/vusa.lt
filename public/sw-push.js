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
    } catch (e) {
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

  event.waitUntil(
    Promise.all([
      self.registration.showNotification(data.title, options),
      Number.isSafeInteger(options.data.unreadCount) && self.navigator.setAppBadge?.(options.data.unreadCount).catch(() => {}),
    ])
  );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  
  event.notification.close();

  let url = new URL('/mano', self.location.origin);
  try {
    const target = new URL(event.notification.data?.url || '/mano', self.location.origin);
    if (target.origin === url.origin) url = target;
  } catch { /* Malformed notification links open the dashboard. */ }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // Check if there's already a window open with this URL
      for (const client of clientList) {
        if (client.url === url.href && 'focus' in client) {
          return client.focus();
        }
      }
      // If no window is open, open a new one
      if (clients.openWindow) {
        return clients.openWindow(url.href);
      }
    })
  );
});

// Handle notification close
self.addEventListener('notificationclose', (event) => {
  // You can perform actions when the notification is dismissed if needed
});
