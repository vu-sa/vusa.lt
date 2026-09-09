import type { ModelEnum } from '@/Types/enums';

export function getCalendarEvent2Route(calendarEvent, lang: ModelEnum): string {
  if (calendarEvent.public_url) {
    return calendarEvent.public_url;
  }

  return route('calendar.event', { calendar: calendarEvent.id, lang });
}
