import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { Notification } from '@/Composables/useNotificationFormatting';

/** The one unread count every notification entry point (bell, phone bottom bar) shows. */
export function useUnreadNotificationCount() {
  const page = usePage<PageProps>();

  return computed(() => ((page.props.auth?.user?.unreadNotifications ?? []) as Notification[])
    .filter(notification => !notification.read_at)
    .length);
}
