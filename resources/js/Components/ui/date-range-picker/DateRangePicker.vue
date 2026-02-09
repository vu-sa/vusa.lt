<template>
  <Popover @close="onClose">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !internalValue?.start && 'text-muted-foreground',
        )"
        :disabled
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayText }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <RangeCalendar
        v-model="internalValue"
        initial-focus
        :number-of-months="numberOfMonths ?? 2"
        :min-value="minDate"
        :max-value="maxDate"
      />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  CalendarDate,
  CalendarDateTime,
} from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { ref, computed, watch, useAttrs } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import type { DateRange } from 'reka-ui';

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { RangeCalendar } from '@/Components/ui/range-calendar';
import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';

// Define component props
const props = defineProps<{
  modelValue?: DateRange;
  minDate?: DateValue;
  maxDate?: DateValue;
  placeholder?: string;
  disabled?: boolean;
  numberOfMonths?: number;
  includeTime?: boolean;
}>();

// Define component events
const emit = defineEmits<{
  (e: 'update:modelValue', value: DateRange): void;
  (e: 'change', value: DateRange): void;
  (e: 'blur'): void;
}>();

// Get any additional attributes for form field integration
const attrs = useAttrs();

// Format dates based on current locale
const formatter = new DateFormatter(document.documentElement.lang || 'lt', {
  dateStyle: 'medium',
});

const dateTimeFormatter = new DateFormatter(document.documentElement.lang || 'lt', {
  dateStyle: 'medium',
  timeStyle: 'short',
});

// Internal value management
const internalValue = computed({
  get: () => props.modelValue,
  set: (value: DateRange | undefined) => {
    if (value) {
      emit('update:modelValue', value);
      emit('change', value);
    }
  },
});

// Display text for the date button
const displayText = computed(() => {
  if (internalValue.value?.start) {
    const formatFn = props.includeTime ? dateTimeFormatter : formatter;
    if (internalValue.value.end) {
      return `${formatFn.format(internalValue.value.start.toDate(getLocalTimeZone()))} - ${formatFn.format(internalValue.value.end.toDate(getLocalTimeZone()))}`;
    }
    return formatFn.format(internalValue.value.start.toDate(getLocalTimeZone()));
  }
  return props.placeholder || $t('Pick a date range');
});

// Handle closing popover (for blur event)
const onClose = () => {
  emit('blur');
};
</script>
