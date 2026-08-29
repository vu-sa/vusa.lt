<template>
  <ActionWindowScreen :title="$t('action_window.meeting.when.title')" :subtitle="subtitle">
    <ActionChoiceList>
      <ActionChoiceButton
        v-for="suggestion in suggestions"
        :key="suggestion.key"
        :title="suggestion.label"
        :description="suggestion.detail"
        :icon="CalendarDays"
        :gradient="SUGGESTION_TINT"
        :show-chevron="false"
        @click="pick(suggestion.date)"
      />
      <ActionChoiceButton
        :title="$t('action_window.meeting.when.custom')"
        :icon="CalendarSearch"
        @click="goTo('meeting.date')"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, CalendarSearch } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData } from '@/Composables/useActionWindowData';
import { toLocalDateTime } from '@/Composables/useMeetingCreation';
import { isDateOnlyMeetingType } from '@/Types/MeetingType';

const SUGGESTION_TINT = 'from-amber-500/15 to-orange-500/15 dark:from-amber-400/12 dark:to-orange-400/12';

const { draft, advance, goTo, replace, updateMeeting } = useActionWindow();
const { institutions, isLoading, load } = useActionWindowData();
const dates = useWindowDates();

onMounted(load);

/**
 * Only ever suggested from this body's own history — a generic "tomorrow at 18:00" is
 * wrong for almost every institution, and a wrong default is worse than none.
 */
const pattern = computed(() =>
  institutions.value.find(institution => institution.id === draft.institution?.id)?.meeting_pattern ?? null,
);

/**
 * An email meeting is a deadline, so the day is the whole answer. The suggested weekday
 * still helps — it comes from when this body actually acts — but the hour behind it is
 * an in-person one and would be a promise the meeting does not make.
 */
const isDateOnly = computed(() => isDateOnlyMeetingType(draft.meeting.type ?? null));

/** The next `weekday` on or after tomorrow, at `HH:mm`. */
const nextOccurrence = (weekday: number, time: string, weeksAhead: number): Date => {
  const [hour, minute] = time.split(':').map(Number);
  const date = new Date();
  date.setHours(hour ?? 18, minute ?? 0, 0, 0);

  const currentIsoDay = date.getDay() === 0 ? 7 : date.getDay();
  const daysAhead = ((weekday - currentIsoDay + 7) % 7) || 7;

  date.setDate(date.getDate() + daysAhead + weeksAhead * 7);

  return date;
};

const suggestions = computed(() => {
  if (!pattern.value) {
    return [];
  }

  const { weekday, time } = pattern.value;

  return [0, 1].map((weeksAhead) => {
    const date = nextOccurrence(weekday, time, weeksAhead);

    return {
      key: `week-${weeksAhead}`,
      // The time is part of the answer, so it belongs in the label, not hidden in it.
      label: isDateOnly.value
        ? dates.dayWithWeekday(date)
        : `${dates.dayWithWeekday(date)}, ${time}`,
      detail: weeksAhead === 0
        ? $t('action_window.meeting.when.usual_hint')
        : $t('action_window.meeting.when.week_after_hint'),
      date,
    };
  });
});

const subtitle = computed(() => pattern.value ? $t('action_window.meeting.when.subtitle') : undefined);

/**
 * With no history there is nothing to suggest, so a screen offering one "pick a date"
 * button would be a wasted tap — go straight to the calendar.
 */
const skipWhenNothingToSuggest = () => {
  if (!isLoading.value && !pattern.value) {
    replace('meeting.date');
  }
};

onMounted(skipWhenNothingToSuggest);
watch(isLoading, skipWhenNothingToSuggest);

const pick = (date: Date) => {
  updateMeeting({ start_time: toLocalDateTime(date) });
  advance('meeting.agenda');
};
</script>
