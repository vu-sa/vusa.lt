import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { Notification } from '@/Composables/useNotificationFormatting';

/** The one unread count every notification entry point (bell, phone bottom bar) shows. */
export function useUnreadNotificationCount() {
  const page = usePage<PageProps>();

  // The shared list is capped (newest first), so the server's count is authoritative.
  return computed(() => page.props.auth?.user?.unreadNotificationsCount
    ?? ((page.props.auth?.user?.unreadNotifications ?? []) as Notification[])
      .filter(notification => !notification.read_at)
      .length);
}
