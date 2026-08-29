<template>
  <ActionWindowScreen
    :title="$t('action_window.meeting.date.title')"
    :subtitle="$t('action_window.meeting.date.subtitle')"
  >
    <div class="flex justify-center">
      <Calendar v-model="picked" class="rounded-2xl border border-border/70 p-2" />
    </div>

    <template #footer>
      <Button class="w-full" size="lg" :disabled="!picked" @click="confirm">
        {{ $t('action_window.common.continue') }}
      </Button>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { CalendarDate, getLocalTimeZone, today } from '@internationalized/date';

import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { toLocalDateTime } from '@/Composables/useMeetingCreation';
import { isDateOnlyMeetingType } from '@/Types/MeetingType';

const { draft, advance, goTo, updateMeeting } = useActionWindow();

const existing = draft.meeting.start_time ? new Date(draft.meeting.start_time) : null;

const picked = ref<CalendarDate | undefined>(
  existing && !Number.isNaN(existing.getTime())
    ? new CalendarDate(existing.getFullYear(), existing.getMonth() + 1, existing.getDate())
    : today(getLocalTimeZone()),
);

// An email meeting is a deadline: the day is the whole answer, so there is no clock step.
const isDateOnly = computed(() => isDateOnlyMeetingType(draft.meeting.type ?? null));

const confirm = () => {
  if (!picked.value) {
    return;
  }

  const date = picked.value.toDate(getLocalTimeZone());

  if (isDateOnly.value) {
    date.setHours(23, 59, 59, 0);
    updateMeeting({ start_time: toLocalDateTime(date) });
    advance('meeting.agenda');
    return;
  }

  // Carry the day over; the clock screen fills in the rest.
  date.setHours(existing?.getHours() ?? 18, existing?.getMinutes() ?? 0, 0, 0);
  updateMeeting({ start_time: toLocalDateTime(date) });
  goTo('meeting.time');
};
</script>
