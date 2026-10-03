<template>
  <form method="POST" :action="action" class="mt-8 space-y-3">
    <input type="hidden" name="_token" :value="csrf">
    <input type="hidden" name="answer" :value="selected">
    <template v-for="choice in choices" :key="choice.value">
      <Button type="button" variant="outline" voice="sentence" class="min-h-14 w-full justify-between whitespace-normal px-4 text-left"
        :class="selected === choice.value ? 'border-brand bg-secondary' : ''" :aria-expanded="selected === choice.value"
        :aria-controls="`answer-${choice.value}`" @click="selected = choice.value">
        <span>{{ choice.label }}</span><span aria-hidden="true">{{ selected === choice.value ? '✓ −' : '+' }}</span>
      </Button>
      <div v-if="selected === choice.value" :id="`answer-${choice.value}`" class="space-y-4 border-x border-b border-border p-4">
        <template v-if="choice.value === 'met'">
          <p class="text-sm text-muted-foreground">{{ text.met_hint }}</p>
          <p v-if="knownDates.length" class="text-sm">{{ text.known_meetings }}: {{ knownDates.join(', ') }}</p>
          <fieldset v-for="(meeting, index) in meetings" :key="meeting.key" class="space-y-3 border-b border-border pb-4">
            <legend class="sr-only">{{ text.meeting_date }} {{ index + 1 }}</legend>
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label :for="`date-${meeting.key}`" class="text-sm">{{ text.meeting_date }}</label>
                <DatePicker :id="`date-${meeting.key}`" :model-value="meeting.date" :min-date="parseDate(start)" :max-date="parseDate(end)" :locale="locale"
                  @update:model-value="meeting.date = $event?.toISOString().slice(0, 10) ?? ''" />
                <input type="hidden" :name="`meetings[${index}][date]`" :value="meeting.date">
                <p v-if="errors[`meetings.${index}.date`]" class="text-sm text-destructive">{{ errors[`meetings.${index}.date`] }}</p>
              </div>
              <div v-if="meeting.type !== 'email'">
                <label :for="`time-${meeting.key}`" class="text-sm">{{ text.meeting_time }}</label>
                <TimePicker :id="`time-${meeting.key}`" :model-value="timeValue(meeting.time)" placeholder="HH:mm" :minute-step="1" required
                  @update:model-value="meeting.time = $event ? `${String($event.hour).padStart(2, '0')}:${String($event.minute).padStart(2, '0')}` : ''" />
                <input type="hidden" :name="`meetings[${index}][time]`" :value="meeting.time">
                <p v-if="errors[`meetings.${index}.time`]" class="text-sm text-destructive">{{ errors[`meetings.${index}.time`] }}</p>
              </div>
            </div>
            <label :for="`type-${meeting.key}`" class="block text-sm">{{ text.meeting_type }}</label>
            <select :id="`type-${meeting.key}`" v-model="meeting.type" :name="`meetings[${index}][type]`" class="min-h-11 w-full border border-border bg-background px-3">
              <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
            <p v-if="errors[`meetings.${index}.type`]" class="text-sm text-destructive">{{ errors[`meetings.${index}.type`] }}</p>
            <Button v-if="meetings.length > 1" type="button" variant="ghost" voice="sentence" @click="meetings.splice(index, 1)">{{ text.remove_meeting }}</Button>
          </fieldset>
          <p class="text-xs text-muted-foreground">{{ text.date_hint }} {{ text.meeting_time_hint }}</p>
          <Button type="button" variant="outline" voice="sentence" :disabled="meetings.length >= 50" @click="addMeeting">{{ text.add_meeting }}</Button>
        </template>
        <p v-else class="text-sm text-muted-foreground">{{ choice.hint }}</p>
        <p v-if="errors.answer" class="text-sm text-destructive">{{ errors.answer }}</p>
        <Button type="submit" class="min-h-11 w-full">{{ choice.submit }}</Button>
      </div>
    </template>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { parseDate } from '@internationalized/date';

import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { TimePicker } from '@/Components/ui/time-picker';

const props = defineProps<{
  action: string; csrf: string; start: string; end: string; locale: string; chosen: string | null;
  choices: Array<{ value: string; label: string; hint: string; submit: string }>;
  types: Array<{ value: string; label: string }>; text: Record<string, string>; errors: Record<string, string>;
  knownDates: string[]; oldMeetings: Array<{ date?: string; time?: string; type?: string }>;
}>();
const selected = ref(props.chosen);
let nextKey = 0;
const meetings = ref((props.oldMeetings.length ? props.oldMeetings : [{}]).map(row => ({ key: nextKey++, date: row.date ?? '', time: row.time ?? '', type: row.type ?? 'in-person' })));
const addMeeting = () => meetings.value.push({ key: nextKey++, date: '', time: '', type: 'in-person' });
const timeValue = (value: string) => /^\d{2}:\d{2}$/.test(value) ? { hour: Number(value.slice(0, 2)), minute: Number(value.slice(3)) } : undefined;
</script>
