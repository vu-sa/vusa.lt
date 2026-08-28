<template>
  <div class="flex items-center gap-1">
    <Input
      :id="id"
      :model-value="modelValue ?? ''"
      :disabled="disabled"
      :aria-label="label"
      placeholder="YYYY-MM-DD"
      class="h-8 font-mono text-xs"
      data-slot="timeline-date-input"
      @change="onText"
    />

    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button type="button" size="icon-xs" variant="outline" :disabled="disabled" :aria-label="label">
          <CalendarIcon class="size-3.5" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-0" align="start">
        <Calendar :model-value="calendarValue" initial-focus @update:model-value="onCalendar" />
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { CalendarDate, type DateValue } from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

/**
 * One control per date, not two.
 *
 * The inspector used to stack a `DatePicker` and a text `Input` on the same value, which
 * doubled the handlers and left two widgets disagreeing mid-edit. Here the text field is
 * the value and the calendar is a way of filling it in.
 */
const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

const props = defineProps<{
  id?: string;
  label: string;
  modelValue: string | null;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
}>();

const open = ref(false);

/**
 * `CalendarDate` is timezone-free, which is the whole reason it is used here: building a
 * `Date` would drag the browser's offset into a value that is only ever a calendar day.
 */
const calendarValue = computed<DateValue | undefined>(() => {
  if (!props.modelValue || !DATE_PATTERN.test(props.modelValue)) return undefined;

  const [year, month, day] = props.modelValue.split('-').map(Number);

  return new CalendarDate(year, month, day);
});

function onCalendar(value: unknown): void {
  if (value === undefined || value === null) return;

  const date = value as DateValue;
  emit('update:modelValue', `${date.year}-${pad(date.month)}-${pad(date.day)}`);
  open.value = false;
}

/**
 * Typed dates commit on blur/Enter only, and only when complete — otherwise every
 * keystroke of "2025-05-18" would stage a different half-formed year.
 */
function onText(event: Event): void {
  const value = (event.target as HTMLInputElement).value.trim();

  if (value === '') {
    emit('update:modelValue', null);

    return;
  }

  if (DATE_PATTERN.test(value)) emit('update:modelValue', value);
}

function pad(value: number): string {
  return String(value).padStart(2, '0');
}
</script>
