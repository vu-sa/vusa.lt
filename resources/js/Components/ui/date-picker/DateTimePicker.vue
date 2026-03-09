<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'
import { Button } from '@/Components/ui/button'
import { Calendar } from '@/Components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { TimePicker, type TimeValue } from '@/Components/ui/time-picker'
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  CalendarDate,
} from '@internationalized/date'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// Define component props
const props = withDefaults(defineProps<{
  modelValue?: Date | string | null
  minDate?: DateValue
  maxDate?: DateValue
  placeholder?: string
  disabled?: boolean
  hourRange?: [number, number]
  minuteStep?: number
}>(), {
  hourRange: () => [0, 23],
  minuteStep: 5
})

// Define component events
const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | null): void
  (e: 'change', value: Date | null): void
  (e: 'blur', event?: Event): void
}>()

// Format dates based on current locale
const dateFormatter = new DateFormatter(document.documentElement.lang || 'lt', {
  dateStyle: 'long',
})

const timeFormatter = new DateFormatter(document.documentElement.lang || 'lt', {
  timeStyle: 'short',
})

// Internal state for time
const timeValue = ref<TimeValue>({ hour: 12, minute: 0 })

// Parse the model value into date and time components
const parseModelValue = (value: Date | string | null | undefined): { date: CalendarDate | undefined, time: TimeValue } => {
  if (!value) {
    return { date: undefined, time: { hour: 12, minute: 0 } }
  }
  
  let dateObj: Date
  if (value instanceof Date) {
    dateObj = value
  } else if (typeof value === 'string') {
    dateObj = new Date(value)
    if (isNaN(dateObj.getTime())) {
      return { date: undefined, time: { hour: 12, minute: 0 } }
    }
  } else {
    return { date: undefined, time: { hour: 12, minute: 0 } }
  }
  
  return {
    date: new CalendarDate(
      dateObj.getFullYear(),
      dateObj.getMonth() + 1,
      dateObj.getDate(),
    ),
    time: {
      hour: dateObj.getHours(),
      minute: dateObj.getMinutes()
    },
  }
}

// Initialize from model value
const initialParsed = parseModelValue(props.modelValue)
const calendarValue = ref<CalendarDate | undefined>(initialParsed.date)
timeValue.value = initialParsed.time

// Build the combined Date from calendar + time inputs
const buildDateTime = (): Date | null => {
  if (!calendarValue.value) return null
  
  const date = calendarValue.value.toDate(getLocalTimeZone())
  date.setHours(timeValue.value.hour)
  date.setMinutes(timeValue.value.minute)
  date.setSeconds(0)
  date.setMilliseconds(0)
  
  return date
}

// Emit combined value when calendar or time changes
const emitValue = () => {
  const dateTime = buildDateTime()
  emit('update:modelValue', dateTime)
  emit('change', dateTime)
}

// Handle calendar value change
const onCalendarChange = (value: any) => {
  if (value) {
    calendarValue.value = new CalendarDate(value.year, value.month, value.day)
  } else {
    calendarValue.value = undefined
  }
  emitValue()
}

// Handle time change
const onTimeChange = (value: TimeValue) => {
  timeValue.value = value
  emitValue()
}

// Watch for external model value changes
watch(() => props.modelValue, (newValue) => {
  const parsed = parseModelValue(newValue)
  calendarValue.value = parsed.date
  timeValue.value = parsed.time
}, { immediate: false })

// Display text for the button
const displayText = computed(() => {
  if (calendarValue.value) {
    const date = buildDateTime()
    if (date) {
      return `${dateFormatter.format(date)} ${timeFormatter.format(date)}`
    }
    return dateFormatter.format(calendarValue.value.toDate(getLocalTimeZone()))
  }
  return props.placeholder || $t('Pick a date and time')
})

// Handle closing popover (for blur event)
const onClose = () => {
  emit('blur')
}
</script>

<template>
  <Popover @close="onClose">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !calendarValue && 'text-muted-foreground',
        )"
        :disabled="disabled"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayText }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <Calendar 
        :model-value="calendarValue as any" 
        @update:model-value="onCalendarChange"
        initial-focus 
        :min-date="minDate"
        :max-date="maxDate"
      />
      <div class="border-t p-3">
        <TimePicker
          :model-value="timeValue"
          :hour-range="hourRange"
          :minute-step="minuteStep"
          class="w-full"
          @update:model-value="onTimeChange"
        />
      </div>
    </PopoverContent>
  </Popover>
</template>
