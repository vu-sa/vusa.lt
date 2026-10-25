import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';

import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { CalendarSearchResult } from '@/Shared/Search/types';
import { formatDateTime } from '@/Utils/dateTime';

/** An event as its preview shows it, whether it came from search or from the database. */
export interface CalendarEventPreviewModel {
  id: string;
  title: string;
  startsAt: string | null;
  endsAt: string | null;
  tenantShortname?: string;
  location?: string;
  eventType?: string;
  /** Unknown for search hits: the admin index does not carry it. */
  isDraft?: boolean;
}

export function calendarEventPreviewFromSearch(event: CalendarSearchResult): CalendarEventPreviewModel {
  const title = getActiveLanguage() === 'en' && event.title_en ? event.title_en : (event.title_lt || event.title);

  // Search documents store Unix seconds.
  return {
    id: event.id,
    title: title || $t('Be pavadinimo'),
    startsAt: event.date ? formatDateTime(event.date * 1000) : null,
    endsAt: event.end_date ? formatDateTime(event.end_date * 1000) : null,
    tenantShortname: event.tenant_name,
  };
}

export function calendarEventPreviewFromRecord(event: App.Entities.Calendar): CalendarEventPreviewModel {
  return {
    id: String(event.id),
    title: getTranslatedValue(event.title) || $t('Be pavadinimo'),
    startsAt: event.date ? formatDateTime(event.date) : null,
    endsAt: event.end_date ? formatDateTime(event.end_date) : null,
    tenantShortname: event.tenant?.shortname,
    location: getTranslatedValue(event.location) || undefined,
    eventType: event.event_type ? getTranslatedValue(event.event_type.name) : undefined,
    isDraft: event.is_draft,
  };
}
