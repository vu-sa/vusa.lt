<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'
import { Button } from '@/Components/ui/button'
import { Calendar } from '@/Components/ui/calendar'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  today,
  CalendarDate,
} from '@internationalized/date'
import { Calendar as CalendarIcon, Clock as ClockIcon } from 'lucide-vue-next'
import { ref, computed, watch, useAttrs } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// Define component props
const props = defineProps<{
  modelValue?: Date | string | null
  minDate?: DateValue
  maxDate?: DateValue
  placeholder?: string
  disabled?: boolean
}>()

// Define component events
const emit = defineEmits<{
  (e: 'update:modelValue', value: Date | string | null): void
  (e: 'change', value: Date | string | null): void
  (e: 'blur'): void
}>()

// Get any additional attributes for form field integration
const attrs = useAttrs()

// Format dates based on current locale
const dateFormatter = new DateFormatter(document.documentElement.lang || 'lt', {
  dateStyle: 'long',
})

const timeFormatter = new DateFormatter(document.documentElement.lang || 'lt', {
  timeStyle: 'short',
})

// Internal state for hours and minutes
const hours = ref('12')
const minutes = ref('00')

// Parse the model value into date and time components
const parseModelValue = (value: Date | string | null | undefined): { date: CalendarDate | undefined, hours: string, minutes: string } => {
  if (!value) {
    return { date: undefined, hours: '12', minutes: '00' }
  }
  
  let dateObj: Date
  if (value instanceof Date) {
    dateObj = value
  } else if (typeof value === 'string') {
    dateObj = new Date(value)
    if (isNaN(dateObj.getTime())) {
      return { date: undefined, hours: '12', minutes: '00' }
    }
  } else {
    return { date: undefined, hours: '12', minutes: '00' }
  }
  
  return {
    date: new CalendarDate(
      dateObj.getFullYear(),
      dateObj.getMonth() + 1,
      dateObj.getDate(),
    ),
    hours: dateObj.getHours().toString().padStart(2, '0'),
    minutes: dateObj.getMinutes().toString().padStart(2, '0'),
  }
}

// Initialize from model value
const initialParsed = parseModelValue(props.modelValue)
const calendarValue = ref<CalendarDate | undefined>(initialParsed.date)
hours.value = initialParsed.hours
minutes.value = initialParsed.minutes

// Build the combined Date from calendar + time inputs
const buildDateTime = (): Date | null => {
  if (!calendarValue.value) return null
  
  const date = calendarValue.value.toDate(getLocalTimeZone())
  const h = parseInt(hours.value) || 0
  const m = parseInt(minutes.value) || 0
  
  date.setHours(Math.min(23, Math.max(0, h)))
  date.setMinutes(Math.min(59, Math.max(0, m)))
  date.setSeconds(0)
  date.setMilliseconds(0)
  
  return date
}

// Emit combined value when calendar or time changes
const emitValue = () => {
  const dateTime = buildDateTime()
  if (dateTime) {
    emit('update:modelValue', dateTime)
    emit('change', dateTime)
  }
}

// Handle calendar value change
const onCalendarChange = (value: any) => {
  if (value) {
    calendarValue.value = new CalendarDate(value.year, value.month, value.day)
  } else {
    calendarValue.value = undefined
  }
}

// Watch calendar value changes
watch(calendarValue, () => {
  emitValue()
})

// Watch time input changes
watch([hours, minutes], () => {
  // Validate and clamp values
  const h = parseInt(hours.value)
  const m = parseInt(minutes.value)
  
  if (!isNaN(h) && !isNaN(m)) {
    emitValue()
  }
})

// Watch for external model value changes
watch(() => props.modelValue, (newValue) => {
  const parsed = parseModelValue(newValue)
  calendarValue.value = parsed.date
  hours.value = parsed.hours
  minutes.value = parsed.minutes
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

// Handle time input changes with validation
const onHoursChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  let value = parseInt(target.value)
  if (isNaN(value)) {
    hours.value = '00'
  } else {
    value = Math.min(23, Math.max(0, value))
    hours.value = value.toString().padStart(2, '0')
  }
}

const onMinutesChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  let value = parseInt(target.value)
  if (isNaN(value)) {
    minutes.value = '00'
  } else {
    value = Math.min(59, Math.max(0, value))
    minutes.value = value.toString().padStart(2, '0')
  }
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
        <div class="flex items-center gap-2">
          <ClockIcon class="h-4 w-4 text-muted-foreground" />
          <Label class="text-sm text-muted-foreground">{{ $t('Time') }}</Label>
        </div>
        <div class="mt-2 flex items-center gap-1">
          <Input
            type="number"
            :model-value="hours"
            min="0"
            max="23"
            class="w-16 text-center"
            @change="onHoursChange"
            @blur="onHoursChange"
          />
          <span class="text-lg font-medium">:</span>
          <Input
            type="number"
            :model-value="minutes"
            min="0"
            max="59"
            class="w-16 text-center"
            @change="onMinutesChange"
            @blur="onMinutesChange"
          />
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
