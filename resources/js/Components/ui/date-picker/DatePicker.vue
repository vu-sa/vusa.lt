<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'
import { Button } from '@/Components/ui/button'

import { Calendar } from '@/Components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone,
  today,
} from '@internationalized/date'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { ref, computed, watch, useAttrs } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

// Define component props
const props = defineProps<{
  modelValue?: Date | DateValue
  minDate?: DateValue
  maxDate?: DateValue
  placeholder?: string
  disabled?: boolean
}>()

// Define component events
const emit = defineEmits<{
  (e: 'update:modelValue', value: Date): void
  (e: 'change', value: Date): void
  (e: 'blur'): void
}>()

// Get any additional attributes for form field integration
const attrs = useAttrs()

// Format dates based on current locale
const formatter = new DateFormatter(document.documentElement.lang || 'lt', {
  dateStyle: 'long',
})

// Internal value management
const internalValue = computed({
  get: () => {
    // If we have a model value that's a Date, convert it to DateValue
    if (props.modelValue instanceof Date) {
      return today(getLocalTimeZone()).set({
        year: props.modelValue.getFullYear(),
        month: props.modelValue.getMonth() + 1,
        day: props.modelValue.getDate(),
      })
    }
    return props.modelValue as DateValue | undefined
  },
  set: (value: DateValue | undefined) => {
    if (value) {
      const dateObject = value.toDate(getLocalTimeZone())
      emit('update:modelValue', dateObject)
      emit('change', dateObject)
    } else {
      emit('update:modelValue', undefined as any)
      emit('change', undefined as any)
    }
  }
})

// Display text for the date button
const displayText = computed(() => {
  if (internalValue.value) {
    return formatter.format(internalValue.value.toDate(getLocalTimeZone()))
  }
  return props.placeholder || $t('Pick a date')
})

// For vee-validate integration - set value from field binding
const setValueFromField = (fieldValue: any) => {
  if (fieldValue instanceof Date) {
    internalValue.value = today(getLocalTimeZone()).set({
      year: fieldValue.getFullYear(),
      month: fieldValue.getMonth() + 1,
      day: fieldValue.getDate(),
    })
  } else if (typeof fieldValue === 'string' && fieldValue) {
    // Handle ISO string format
    const date = new Date(fieldValue)
    if (!isNaN(date.getTime())) {
      internalValue.value = today(getLocalTimeZone()).set({
        year: date.getFullYear(),
        month: date.getMonth() + 1,
        day: date.getDate(),
      })
    }
  }
}

// Watch for changes from vee-validate field
watch(() => attrs.value, (newValue) => {
  if (newValue) {
    setValueFromField(newValue)
  }
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
          !internalValue && 'text-muted-foreground',
        )"
        :disabled="disabled"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayText }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Calendar 
        v-model="internalValue" 
        initial-focus 
        :min-date="minDate"
        :max-date="maxDate"
      />
    </PopoverContent>
  </Popover>
</template>