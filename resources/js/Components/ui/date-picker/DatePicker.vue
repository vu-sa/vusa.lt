<template>
  <Input
    v-if="isCoarsePointer"
    :model-value="dateValue"
    type="date"
    :min="minDateValue"
    :max="maxDateValue"
    :size
    :disabled
    :class="props.class"
    @update:model-value="updateFromInput"
    @blur="emit('blur')"
  />
  <div v-else :class="cn('flex w-full items-center gap-2', props.class)">
    <Input
      :model-value="dateValue"
      inputmode="numeric"
      :placeholder="placeholder ?? 'YYYY-MM-DD'"
      :size
      :disabled
      @update:model-value="updateFromInput"
      @blur="emit('blur')"
    />
    <Popover @close="emit('blur')">
      <PopoverTrigger as-child>
        <Button type="button" variant="outline" :size="buttonSize" class="shrink-0" :disabled :aria-label="$t('Pasirinkti datą')">
          <CalendarIcon class="size-4" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-auto p-0" align="end">
        <Calendar
          :model-value="calendarValue"
          initial-focus
          :min-date
          :max-date
          @update:model-value="updateFromCalendar"
        />
      </PopoverContent>
    </Popover>
    <Button
      v-if="clearable && calendarValue"
      type="button"
      variant="ghost"
      :size="buttonSize"
      class="shrink-0"
      :disabled
      :aria-label="$t('Išvalyti')"
      @click="clear"
    >
      <X class="size-4" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { CalendarDate, type DateValue } from '@internationalized/date';
import { Calendar as CalendarIcon, X } from 'lucide-vue-next';
import { computed, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { useCoarsePointer } from '@/Composables/useCoarsePointer';

// Define component props
const props = defineProps<{
  modelValue?: Date | DateValue | string | null;
  minDate?: DateValue;
  maxDate?: DateValue;
  placeholder?: string;
  disabled?: boolean;
  clearable?: boolean;
  /** Matches `ui/input` sizes; the calendar and clear buttons follow so the row stays one height. */
  size?: 'default' | 'sm';
  class?: HTMLAttributes['class'];
}>();

// Define component events
const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | undefined): void;
  (e: 'change', value: Date | undefined): void;
  (e: 'blur'): void;
}>();

const isCoarsePointer = useCoarsePointer();

const calendarValue = computed<CalendarDate | undefined>(() => {
  if (props.modelValue instanceof Date) {
    return new CalendarDate(props.modelValue.getUTCFullYear(), props.modelValue.getUTCMonth() + 1, props.modelValue.getUTCDate());
  }

  if (typeof props.modelValue === 'string') {
    return parseDate(props.modelValue);
  }

  if (props.modelValue && typeof props.modelValue.toDate === 'function') {
    return new CalendarDate(props.modelValue.year, props.modelValue.month, props.modelValue.day);
  }

  return undefined;
});

const dateValue = computed(() => calendarValue.value ? formatDate(calendarValue.value) : '');
const minDateValue = computed(() => props.minDate ? formatDate(props.minDate) : undefined);
const maxDateValue = computed(() => props.maxDate ? formatDate(props.maxDate) : undefined);

function parseDate(value: string): CalendarDate | undefined {
  const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!match) {
    return undefined;
  }

  const year = Number(match[1]);
  const month = Number(match[2]);
  const day = Number(match[3]);
  const date = new Date(Date.UTC(year, month - 1, day));

  if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) {
    return undefined;
  }

  return new CalendarDate(year, month, day);
}

function formatDate(value: DateValue): string {
  return `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`;
}

function emitDate(value: CalendarDate | undefined): void {
  const date = value ? new Date(Date.UTC(value.year, value.month - 1, value.day, 12)) : undefined;
  emit('update:modelValue', date);
  emit('change', date);
}

function updateFromInput(value: string | number): void {
  emitDate(parseDate(String(value)));
}

function updateFromCalendar(value: DateValue | undefined): void {
  emitDate(value ? new CalendarDate(value.year, value.month, value.day) : undefined);
}

function clear(): void {
  emitDate(undefined);
}

const buttonSize = computed(() => (props.size === 'sm' ? 'icon' : 'icon-lg'));
</script>
