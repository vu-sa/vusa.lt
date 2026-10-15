import { trans as $t } from 'laravel-vue-i18n';

import type { SupportRequestItem } from '@/Types/supportRequests';

export function supportRequestVisibility(item: Pick<SupportRequestItem, 'visibility'>): string {
  return typeof item.visibility === 'object' ? item.visibility.value : item.visibility;
}

export function supportRequestVisibilityLabel(item: Pick<SupportRequestItem, 'visibility'>): string {
  const labels: Record<string, string> = {
    private: $t('Privatu'),
    roles: $t('Pasirinktoms rolėms'),
    public: $t('Visiems prisijungusiems'),
  };

  return labels[supportRequestVisibility(item)] ?? '—';
}
