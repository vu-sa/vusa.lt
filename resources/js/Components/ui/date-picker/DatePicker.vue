<template>
  <Popover @close="onClose">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !internalValue && 'text-muted-foreground',
        )"
        :disabled
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayText }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Calendar
        v-model="internalValue"
        initial-focus
        :min-date
        :max-date
      />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  today,
} from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { ref, computed, watch, useAttrs } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

// Define component props
const props = defineProps<{
  modelValue?: Date | DateValue | string;
  minDate?: DateValue;
  maxDate?: DateValue;
  placeholder?: string;
  disabled?: boolean;
}>();

// Define component events
const emit = defineEmits<{
  (e: 'update:modelValue', value: Date): void;
  (e: 'change', value: Date): void;
  (e: 'blur'): void;
}>();

// Get any additional attributes for form field integration
const attrs = useAttrs();

// Format dates based on current locale
const formatter = new DateFormatter(document.documentElement.lang || 'lt', {
  dateStyle: 'long',
});

// Internal value management
const internalValue = computed({
  get: () => {
    if (props.modelValue instanceof Date) {
      return today(getLocalTimeZone()).set({
        year: props.modelValue.getFullYear(),
        month: props.modelValue.getMonth() + 1,
        day: props.modelValue.getDate(),
      });
    }
    // Handle date strings from backend (e.g. "2024-06-15" or "2024-06-15 00:00:00")
    if (typeof props.modelValue === 'string') {
      const match = props.modelValue.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (match) {
        return today(getLocalTimeZone()).set({
          year: parseInt(match[1]),
          month: parseInt(match[2]),
          day: parseInt(match[3]),
        });
      }
      return undefined;
    }
    // DateValue - verify it has the expected interface before returning
    if (props.modelValue && typeof (props.modelValue as DateValue).toDate === 'function') {
      return props.modelValue as DateValue;
    }
    return undefined;
  },
  set: (value: DateValue | undefined) => {
    if (value) {
      // Use noon UTC to prevent date shift during JSON serialization
      const dateObject = new Date(Date.UTC(value.year, value.month - 1, value.day, 12, 0, 0));
      emit('update:modelValue', dateObject);
      emit('change', dateObject);
    }
    else {
      emit('update:modelValue', undefined as any);
      emit('change', undefined as any);
    }
  },
});

// Display text for the date button
const displayText = computed(() => {
  if (internalValue.value) {
    return formatter.format(internalValue.value.toDate(getLocalTimeZone()));
  }
  return props.placeholder || $t('Pick a date');
});

// For vee-validate integration - set value from field binding
const setValueFromField = (fieldValue: any) => {
  if (fieldValue instanceof Date) {
    internalValue.value = today(getLocalTimeZone()).set({
      year: fieldValue.getFullYear(),
      month: fieldValue.getMonth() + 1,
      day: fieldValue.getDate(),
    });
  }
  else if (typeof fieldValue === 'string' && fieldValue) {
    const match = fieldValue.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
      internalValue.value = today(getLocalTimeZone()).set({
        year: parseInt(match[1]),
        month: parseInt(match[2]),
        day: parseInt(match[3]),
      });
    }
  }
};

// Watch for changes from vee-validate field
watch(() => attrs.value, (newValue) => {
  if (newValue) {
    setValueFromField(newValue);
  }
});

// Handle closing popover (for blur event)
const onClose = () => {
  emit('blur');
};
</script>
