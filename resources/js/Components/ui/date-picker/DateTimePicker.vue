<template>
  <div :class="cn('grid gap-2 sm:grid-cols-2', props.class)">
    <DatePicker
      :model-value="dateValue"
      :min-date
      :max-date
      :disabled
      :placeholder
      clearable
      :aria-label="$t('Pasirinkti datą')"
      @update:model-value="updateDate"
      @blur="emit('blur')"
    />
    <TimePicker
      :model-value="timeValue"
      :hour-range
      :minute-step
      :disabled
      clearable
      :aria-label="$t('Pasirinkti laiką')"
      @update:model-value="updateTime"
    />
  </div>
</template>

<script setup lang="ts">
import { CalendarDate, type DateValue } from '@internationalized/date';
import { computed, type HTMLAttributes } from 'vue';

import DatePicker from './DatePicker.vue';

import { TimePicker, type TimeValue } from '@/Components/ui/time-picker';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  modelValue?: Date | string | null;
  minDate?: DateValue;
  maxDate?: DateValue;
  placeholder?: string;
  disabled?: boolean;
  hourRange?: [number, number];
  minuteStep?: number;
  class?: HTMLAttributes['class'];
}>(), {
  hourRange: () => [0, 23],
  minuteStep: 5,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | null): void;
  (e: 'change', value: Date | null): void;
  (e: 'blur', event?: Event): void;
}>();

const modelDate = computed(() => {
  if (props.modelValue instanceof Date) {
    return props.modelValue;
  }

  if (typeof props.modelValue === 'string') {
    const value = new Date(props.modelValue);
    return Number.isNaN(value.getTime()) ? null : value;
  }

  return null;
});

const dateValue = computed(() => {
  const value = modelDate.value;
  return value ? new CalendarDate(value.getFullYear(), value.getMonth() + 1, value.getDate()) : undefined;
});

const timeValue = computed<TimeValue | undefined>(() => {
  const value = modelDate.value;
  return value ? { hour: value.getHours(), minute: value.getMinutes() } : undefined;
});

function emitValue(date: Date | null): void {
  emit('update:modelValue', date);
  emit('change', date);
}

function updateDate(value: Date | undefined): void {
  if (!value) {
    emitValue(null);
    return;
  }

  const current = modelDate.value;
  const date = new Date(value.getUTCFullYear(), value.getUTCMonth(), value.getUTCDate(), current?.getHours() ?? 12, current?.getMinutes() ?? 0);
  emitValue(date);
}

function updateTime(value: TimeValue | undefined): void {
  const current = modelDate.value;
  if (!current || !value) {
    emitValue(null);
    return;
  }

  const date = new Date(current);
  date.setHours(value.hour, value.minute, 0, 0);
  emitValue(date);
}
</script>
