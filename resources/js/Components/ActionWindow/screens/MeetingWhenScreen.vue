<template>
  <ActionWindowScreen :title="$t('action_window.meeting.when.title')" :subtitle>
    <div v-if="loading" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full" />
    </div>

    <!-- Today and yesterday lead: most meetings are filed the day of or the day after,
         and a rep should not need a calendar for either. -->
    <ActionChoiceList v-else>
      <ActionChoiceButton
        v-for="preset in presets"
        :key="preset.key"
        :title="preset.label"
        :description="preset.detail"
        :icon="preset.icon"
        :show-chevron="false"
        @click="preset.pick()"
      />
      <ActionChoiceButton
        :title="$t('action_window.meeting.when.custom')"
        :icon="CalendarSearch"
        @click="goTo('meeting.date', { returnTo: current.params?.returnTo })"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, CalendarSearch, History, type LucideIcon } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData } from '@/Composables/useActionWindowData';
import { toLocalDateTime } from '@/Composables/useMeetingCreation';
import { Skeleton } from '@/Components/ui/skeleton';
import { isDateOnlyMeetingType } from '@/Types/MeetingType';

const DEFAULT_TIME = '18:00';

const { draft, current, advance, goTo, updateMeeting } = useActionWindow();
const { institutions, isLoading, load } = useActionWindowData();
const dates = useWindowDates();

onMounted(load);

const loading = computed(() => isLoading.value);

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

const daysFromToday = (offset: number): Date => {
  const date = new Date();
  date.setDate(date.getDate() + offset);

  return date;
};

/**
 * The hour a picked *day* carries: whatever was already chosen (so changing only the day
 * from the review keeps the hour), then this body's usual hour, then a student's evening.
 * The time screen right after lets the user correct it.
 */
const carriedTime = (): [number, number] => {
  const existing = draft.meeting.start_time ? new Date(draft.meeting.start_time) : null;

  if (existing && !Number.isNaN(existing.getTime())) {
    return [existing.getHours(), existing.getMinutes()];
  }

  const [hour, minute] = (pattern.value?.time ?? DEFAULT_TIME).split(':').map(Number);

  return [hour ?? 18, minute ?? 0];
};

const pickDay = (day: Date) => {
  if (isDateOnly.value) {
    day.setHours(23, 59, 59, 0);
    updateMeeting({ start_time: toLocalDateTime(day) });
    advance('meeting.agenda');
    return;
  }

  const [hour, minute] = carriedTime();
  day.setHours(hour, minute, 0, 0);
  updateMeeting({ start_time: toLocalDateTime(day) });
  advance('meeting.time');
};

const pickMoment = (date: Date) => {
  updateMeeting({ start_time: toLocalDateTime(date) });
  advance('meeting.agenda');
};

interface WhenPreset {
  key: string;
  label: string;
  detail?: string;
  icon: LucideIcon;
  pick: () => void;
}

const presets = computed<WhenPreset[]>(() => {
  const dayPresets: WhenPreset[] = [
    { key: 'today', offset: 0, icon: CalendarDays },
    { key: 'yesterday', offset: -1, icon: History },
  ].map(({ key, offset, icon }) => ({
    key,
    label: $t(`action_window.meeting.when.${key}`),
    detail: dates.dayWithWeekday(daysFromToday(offset)),
    icon,
    pick: () => pickDay(daysFromToday(offset)),
  }));

  if (!pattern.value) {
    return dayPresets;
  }

  const { weekday, time } = pattern.value;

  const upcoming = [0, 1].map<WhenPreset>((weeksAhead) => {
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
      icon: CalendarDays,
      pick: () => pickMoment(date),
    };
  });

  return [...dayPresets, ...upcoming];
});

const subtitle = computed(() => pattern.value ? $t('action_window.meeting.when.subtitle') : undefined);
</script>
