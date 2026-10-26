import { trans as $t } from 'laravel-vue-i18n';

/** Mirrors Meeting::publicVisibility(). */
export interface MeetingPublicVisibility {
  mode: 'public_page' | 'calendar_event' | 'calendar_draft' | 'internal';
  url: string | null;
}

export const isShownPublicly = (visibility: MeetingPublicVisibility): boolean =>
  visibility.mode === 'public_page' || visibility.mode === 'calendar_event';

export function meetingVisibilityLabel(visibility: MeetingPublicVisibility): string {
  switch (visibility.mode) {
    case 'public_page':
      return $t('meetings.record.visible_public');
    case 'calendar_event':
      return $t('meetings.record.visible_event');
    case 'calendar_draft':
      return `${$t('meetings.record.internal_only')} · ${$t('meetings.record.event_draft')}`;
    default:
      return $t('meetings.record.internal_only');
  }
}
