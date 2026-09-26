import type { Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Inbox, MailX, Send } from 'lucide-vue-next';

/** Mirrors App\Enums\EmailDelivery. */
export type EmailDeliveryValue = 'immediate' | 'digest' | 'off';

export const emailDeliveryOptions = (): { value: EmailDeliveryValue; label: string; icon: Component }[] => [
  { value: 'immediate', label: $t('notifications.preferences.email_immediate'), icon: Send },
  { value: 'digest', label: $t('notifications.preferences.email_digest'), icon: Inbox },
  { value: 'off', label: $t('notifications.preferences.email_off'), icon: MailX },
];
