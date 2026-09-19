<template>
  <ActionWindowScreen
    :title="$t('action_window.meeting.date.title')"
    :subtitle="$t('action_window.meeting.date.subtitle')"
  >
    <div class="space-y-2">
      <Label :for="fieldId">{{ $t('action_window.meeting.date.label') }}</Label>
      <DatePicker :id="fieldId" v-model="picked" class="w-full" />
    </div>

    <template #footer>
      <ActionWindowPrimaryButton :disabled="!picked" @click="confirm">
        {{ $t('action_window.common.continue') }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref, useId } from 'vue';

import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { toPickerDate } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { DatePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';
import { toLocalDateTime } from '@/Composables/useMeetingCreation';
import { isDateOnlyMeetingType } from '@/Types/MeetingType';

const { draft, current, advance, goTo, updateMeeting } = useActionWindow();

const fieldId = useId();

const existing = draft.meeting.start_time ? new Date(draft.meeting.start_time) : null;

const picked = ref<Date | undefined>(
  toPickerDate(existing && !Number.isNaN(existing.getTime()) ? existing : new Date()),
);

// An email meeting is a deadline: the day is the whole answer, so there is no clock step.
const isDateOnly = computed(() => isDateOnlyMeetingType(draft.meeting.type ?? null));

const confirm = () => {
  if (!picked.value) {
    return;
  }

  // The picker's UTC-noon date, read back as the local day it stands for.
  const date = new Date(picked.value.getUTCFullYear(), picked.value.getUTCMonth(), picked.value.getUTCDate());

  if (isDateOnly.value) {
    date.setHours(23, 59, 59, 0);
    updateMeeting({ start_time: toLocalDateTime(date) });
    advance('meeting.agenda');
    return;
  }

  // Carry the day over; the clock screen fills in the rest.
  date.setHours(existing?.getHours() ?? 18, existing?.getMinutes() ?? 0, 0, 0);
  updateMeeting({ start_time: toLocalDateTime(date) });
  goTo('meeting.time', { returnTo: current.value.params?.returnTo });
};
</script>
