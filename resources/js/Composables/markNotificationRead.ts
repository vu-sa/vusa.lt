/**
 * Marks a notification read alongside the click that opens it. Not an Inertia visit: a second
 * visit cancels the link's own, and `keepalive` lets the request outlive a full-page navigation.
 */
export function markNotificationRead(id: string): void {
  const token = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length);

  void fetch(route('notifications.markAsRead', id), {
    method: 'POST',
    credentials: 'same-origin',
    keepalive: true,
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {}),
    },
  }).catch(() => {
    // The notification simply stays unread; the user is already on their way.
  });
}
