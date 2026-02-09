import { slugify, translitLithuanian } from './String';

import type { ModelEnum } from '@/Types/enums';

export function getCalendarEvent2Route(calendarEvent, lang: ModelEnum): string {
  return route('calendar.event.2', {
    year: new Date(calendarEvent.date).getFullYear(),
    month: new Date(calendarEvent.date).getMonth() + 1,
    day: new Date(calendarEvent.date).getDate(),
    slug: slugify(translitLithuanian(calendarEvent.title)),
    lang,
  });
}
