import { usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Check, Trash2 } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { getNotificationPrimaryAction, getNotificationPrimaryActionIcon, getNotificationSecondaryAction, getNotificationUrl, type Notification } from '@/Composables/useNotificationFormatting';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

export function notificationRowActions(notification: Notification): AdminRecordAction[] {
  const primary = getNotificationPrimaryAction(notification);
  const url = getNotificationUrl(notification);
  const secondary = getNotificationSecondaryAction(notification);
  const external = (href: string) => {
    const page = usePage();
    const current = import.meta.env.SSR
      ? new URL(page.url, page.props.app.url)
      : new URL(window.location.href);
    const destination = new URL(href, current);
    return destination.origin !== current.origin || !(destination.pathname === '/mano' || destination.pathname.startsWith('/mano/'));
  };
  return [
    ...(primary ? [{ key: 'primary', label: primary.label, icon: getNotificationPrimaryActionIcon(notification), primary: true, href: primary.url, external: external(primary.url) }] : []),
    ...(url && url !== primary?.url ? [{ key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open' as const, href: url, external: external(url) }] : []),
    ...(secondary ? [{ key: 'secondary', label: secondary.label, href: secondary.url, external: external(secondary.url) }] : []),
    ...(!notification.read_at ? [{ key: 'read', label: $t('Pažymėti kaip skaitytą'), icon: Check }] : []),
    { key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true },
  ];
}
