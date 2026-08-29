<template>
  <ActionWindowScreen :title="$t('action_window.meeting.time.title')" :subtitle="dayLabel">
    <ActionChoiceList v-if="!custom">
      <ActionChoiceButton
        v-for="option in options"
        :key="option.time"
        :title="option.time"
        :description="option.detail"
        :icon="Clock"
        :gradient="TIME_TINT"
        :selected="option.time === selectedTime"
        :show-chevron="false"
        @click="pick(option.time)"
      />
      <ActionChoiceButton
        :title="$t('action_window.meeting.time.custom')"
        :icon="Clock4"
        @click="custom = true"
      />
    </ActionChoiceList>

    <div v-else class="flex flex-col items-center gap-4 pt-2">
      <TimePicker v-model="pickedTime" :hour-range="[6, 23]" :minute-step="5" />
      <Button variant="ghost" size="sm" @click="custom = false">
        <ChevronLeft class="mr-1 size-4" />
        {{ $t('action_window.common.back') }}
      </Button>
    </div>

    <template v-if="custom" #footer>
      <Button class="w-full" size="lg" :disabled="!pickedTime" @click="confirmCustom">
        {{ $t('action_window.common.continue') }}
      </Button>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, Clock, Clock4 } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData } from '@/Composables/useActionWindowData';
import { toLocalDateTime } from '@/Composables/useMeetingCreation';
import { Button } from '@/Components/ui/button';
import { TimePicker } from '@/Components/ui/time-picker';
import type { TimeValue } from '@/Components/ui/time-picker/types';

const TIME_TINT = 'from-sky-500/15 to-indigo-500/15 dark:from-sky-400/12 dark:to-indigo-400/12';

/** Student bodies meet after lectures; these are the hours the archive is thickest at. */
const COMMON_TIMES = ['12:00', '15:00', '17:00', '18:00', '19:00'];

const { draft, advance, updateMeeting } = useActionWindow();
const { institutions } = useActionWindowData();
const dates = useWindowDates();

const chosenDay = computed(() => draft.meeting.start_time ? new Date(draft.meeting.start_time) : new Date());

const dayLabel = computed(() => dates.dayWithWeekday(chosenDay.value));

const selectedTime = computed(() => {
  const date = chosenDay.value;
  return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
});

const usualTime = computed(() =>
  institutions.value.find(institution => institution.id === draft.institution?.id)?.meeting_pattern?.time ?? null,
);

/** The body's own usual hour leads, then the common ones it does not already cover. */
const options = computed(() => {
  const usual = usualTime.value;
  const rest = COMMON_TIMES.filter(time => time !== usual);

  return [
    ...(usual ? [{ time: usual, detail: $t('action_window.meeting.time.usual') }] : []),
    ...rest.map(time => ({ time, detail: undefined })),
  ];
});

const custom = ref(false);
const pickedTime = ref<TimeValue>({ hour: chosenDay.value.getHours(), minute: chosenDay.value.getMinutes() });

const applyTime = (hour: number, minute: number) => {
  const date = new Date(chosenDay.value);
  date.setHours(hour, minute, 0, 0);

  updateMeeting({ start_time: toLocalDateTime(date) });
  advance('meeting.agenda');
};

const pick = (time: string) => {
  const [hour, minute] = time.split(':').map(Number);
  applyTime(hour ?? 18, minute ?? 0);
};

const confirmCustom = () => applyTime(pickedTime.value.hour, pickedTime.value.minute);
</script>
