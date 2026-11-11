import { trans as $t } from 'laravel-vue-i18n';

import { formatDate, formatNearDate } from '@/Utils/dateTime';

import type { RepRow } from './types';

export function attentionText(rep: RepRow): string {
  if (!rep.attention) return '';

  return $t(`representatives.attention.reasons.${rep.attention.reason}`, { institution: rep.attention.institution ?? '' });
}

export function lastLoginText(rep: RepRow): string {
  return rep.lastAction ? $t('representatives.list.last_login', { date: formatNearDate(rep.lastAction) }) : $t('representatives.list.never');
}

export function startsText(rep: RepRow): string {
  return rep.termStart ? $t('representatives.list.starts', { date: formatDate(rep.termStart) }) : '';
}

export function institutionNames(rep: RepRow): string {
  return rep.institutions.map(institution => institution.name).join(' · ');
}
