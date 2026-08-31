import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';

import { formatRelativeTime } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

export type EventTone = 'live' | 'past' | 'upcoming';

/**
 * Shared status derivation for calendar events.
 *
 * Deliberately exposes only states that change what a visitor can do — the event
 * is running, or it already happened. Upcoming events get `relativeLabel`
 * ("rytoj", "po 2 dienų") from `Intl.RelativeTimeFormat` instead of a badge, so
 * the page states a fact rather than manufacturing urgency.
 */
export function useEventStatus(
  event: MaybeRefOrGetter<{ date: string; end_date?: string | null }>,
  /** The event stands for a meeting, so the past-tense label names one. */
  isMeeting: MaybeRefOrGetter<boolean> = false,
) {
  const page = usePage();
  const locale = computed(() => (page.props.app?.locale ?? LocaleEnum.LT) as LocaleEnum);

  const startsAt = computed(() => new Date(toValue(event).date));
  const endsAt = computed(() => {
    const { end_date: endDate } = toValue(event);
    return endDate ? new Date(endDate) : startsAt.value;
  });

  const isPast = computed(() => endsAt.value.getTime() < Date.now());
  const isLive = computed(() => {
    const now = Date.now();
    return startsAt.value.getTime() <= now && endsAt.value.getTime() >= now;
  });
  const isUpcoming = computed(() => startsAt.value.getTime() > Date.now());

  const tone = computed<EventTone>(() => {
    if (isLive.value) return 'live';
    if (isPast.value) return 'past';
    return 'upcoming';
  });

  const statusLabel = computed(() => {
    if (isLive.value) return $t('Vyksta dabar');
    if (isPast.value) return toValue(isMeeting) ? $t('Posėdis įvyko') : $t('Renginys įvyko');
    return null;
  });

  const relativeLabel = computed(() => {
    if (!isUpcoming.value) return null;
    return formatRelativeTime(startsAt.value, { numeric: 'auto' }, locale.value);
  });

  return { isPast, isLive, isUpcoming, tone, statusLabel, relativeLabel, startsAt, endsAt };
}
