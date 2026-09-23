<template>
  <!-- Single trigger Popover mode (ideal for sidebars and compact forms) -->
  <div v-if="variant === 'popover'" :class="cn('relative w-full', props.class)">
    <Popover v-model:open="isOpen" @close="emit('blur')">
      <PopoverTrigger as-child>
        <button
          type="button"
          :disabled
          :class="[
            'flex min-h-11 w-full items-center justify-between border border-border bg-background px-3 text-sm text-left transition-colors focus:border-brand focus:outline-none',
            disabled && 'cursor-not-allowed opacity-50',
            !modelDate && 'text-muted-foreground',
          ]"
        >
          <span class="flex items-center gap-2 truncate">
            <CalendarIcon class="size-4 shrink-0 text-brand" />
            <span v-if="formattedDisplay" class="font-medium text-foreground">{{ formattedDisplay }}</span>
            <span v-else>{{ placeholder || $t('Pasirinkti datą ir laiką...') }}</span>
          </span>
          <span
            v-if="clearable && modelDate && !disabled"
            role="button"
            tabindex="0"
            class="ml-2 text-muted-foreground hover:text-foreground p-0.5"
            :aria-label="$t('Išvalyti')"
            @click.stop="clear"
            @keydown.enter.stop="clear"
          >
            <X class="size-4" />
          </span>
        </button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-0" align="start">
        <Calendar
          :model-value="calendarValue"
          initial-focus
          :min-date
          :max-date
          @update:model-value="updateFromCalendar"
        />
        <div class="flex flex-col border-t border-border bg-secondary/20">
          <div class="flex items-center justify-between gap-3 border-b border-border px-3 py-2">
            <span class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
              <Clock class="size-3.5 text-brand" />
              {{ $t('Laikas') }}
            </span>
            <div class="w-28">
              <TimePicker
                :model-value="timeValue"
                :minute-step
                :hour-range
                :disabled
                class="h-8 text-xs"
                @update:model-value="updateTime"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 divide-x divide-border">
            <button
              type="button"
              class="h-9 text-xs font-bold uppercase tracking-wide text-muted-foreground transition-colors hover:bg-secondary/60 hover:text-foreground"
              @click="setNow"
            >
              {{ $t('Dabar') }}
            </button>
            <button
              type="button"
              class="h-9 text-xs font-bold uppercase tracking-wide text-brand transition-colors hover:bg-secondary/60"
              @click="isOpen = false"
            >
              {{ $t('Gerai') }}
            </button>
          </div>
        </div>
      </PopoverContent>
    </Popover>
  </div>

  <!-- Inline 2-column mode (traditional wide form layout) -->
  <div v-else :class="cn('grid gap-2 sm:grid-cols-2', props.class)">
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
import { Calendar as CalendarIcon, Clock, X } from 'lucide-vue-next';
import { computed, ref, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import DatePicker from './DatePicker.vue';

import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { TimePicker, type TimeValue } from '@/Components/ui/time-picker';
import { formatDateTime } from '@/Utils/dateTime';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  modelValue?: Date | string | null;
  minDate?: DateValue;
  maxDate?: DateValue;
  placeholder?: string;
  disabled?: boolean;
  clearable?: boolean;
  variant?: 'inline' | 'popover';
  hourRange?: [number, number];
  minuteStep?: number;
  class?: HTMLAttributes['class'];
}>(), {
  hourRange: () => [0, 23],
  minuteStep: 5,
  variant: 'inline',
  clearable: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | null): void;
  (e: 'change', value: Date | null): void;
  (e: 'blur', event?: Event): void;
}>();

const isOpen = ref(false);

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

const formattedDisplay = computed(() => {
  if (!modelDate.value) return '';
  return formatDateTime(modelDate.value, { format: 'iso' });
});

const calendarValue = computed<CalendarDate | undefined>(() => {
  const value = modelDate.value;
  return value ? new CalendarDate(value.getFullYear(), value.getMonth() + 1, value.getDate()) : undefined;
});

const dateValue = computed(() => calendarValue.value);

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
  const date = new Date(value.getFullYear(), value.getMonth(), value.getDate(), current?.getHours() ?? 12, current?.getMinutes() ?? 0);
  emitValue(date);
}

function updateFromCalendar(date: DateValue | undefined): void {
  if (!date) {
    emitValue(null);
    return;
  }

  const current = modelDate.value;
  const jsDate = new Date(date.year, date.month - 1, date.day, current?.getHours() ?? 12, current?.getMinutes() ?? 0);
  emitValue(jsDate);
}

function updateTime(value: TimeValue | undefined): void {
  if (!value) {
    emitValue(null);
    return;
  }

  const current = modelDate.value ?? new Date();
  const date = new Date(current);
  date.setHours(value.hour, value.minute, 0, 0);
  emitValue(date);
}

function setNow(): void {
  emitValue(new Date());
}

function clear(): void {
  emitValue(null);
}
</script>
