import { apiRequest } from '@/Composables/apiRequest';

/** Keepalive lets the read request outlive the navigation opened by the same click. */
export function markNotificationRead(id: string): void {
  void apiRequest(route('notifications.markAsRead', id), {
    method: 'POST',
    keepalive: true,
  }).catch(() => {});
}
