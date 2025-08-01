<template>
  <div class="space-y-4">
    <!-- Preset Buttons -->
    <div class="space-y-2">
      <div class="grid grid-cols-2 gap-1.5">
        <Button v-for="preset in datePresets" :key="preset.key"
          :variant="dateRange.preset === preset.key ? 'default' : 'outline'" size="sm"
          class="h-7 px-2 text-xs font-medium" @click="selectPreset(preset.key)">
          <span class="mr-1">{{ preset.icon }}</span>
          <span>{{ preset.label }}</span>
        </Button>
      </div>
    </div>

    <!-- Custom Date Range -->
    <div v-if="dateRange.preset === 'custom'" class="space-y-3 p-3 border rounded-lg bg-muted/20">
      <div class="flex items-center gap-2 text-sm font-medium">
        <CalendarDays class="w-4 h-4" />
        <span>Pasirinkti datų intervalą</span>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <!-- From Date -->
        <div class="space-y-2">
          <label class="text-xs font-medium text-muted-foreground">Nuo datos</label>
          <Popover v-model:open="fromDateOpen">
            <PopoverTrigger as-child>
              <Button variant="outline" :class="[
                'w-full justify-start text-left font-normal',
                !dateRange.from && 'text-muted-foreground'
              ]">
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ dateRange.from ? formatDate(dateRange.from) : 'Pasirinkti datą' }}
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0 z-50" align="start">
              <Calendar :model-value="dateToCalendarDate(dateRange.from)" :is-date-unavailable="getFromDisabledDates"
                @update:model-value="handleFromDateChange" />
            </PopoverContent>
          </Popover>
        </div>

        <!-- To Date -->
        <div class="space-y-2">
          <label class="text-xs font-medium text-muted-foreground">Iki datos</label>
          <Popover v-model:open="toDateOpen">
            <PopoverTrigger as-child>
              <Button variant="outline" :class="[
                'w-full justify-start text-left font-normal',
                !dateRange.to && 'text-muted-foreground'
              ]">
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ dateRange.to ? formatDate(dateRange.to) : 'Pasirinkti datą' }}
              </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0 z-50" align="start">
              <Calendar :model-value="dateToCalendarDate(dateRange.to)" :is-date-unavailable="getToDisabledDates"
                @update:model-value="handleToDateChange" />
            </PopoverContent>
          </Popover>
        </div>

        <!-- Clear Custom Range -->
        <Button v-if="dateRange.from || dateRange.to" variant="ghost" size="sm" class="w-full h-6 text-xs"
          @click="clearCustomRange">
          <RotateCcw class="w-3 h-3 mr-1" />
          Išvalyti intervalą
        </Button>
      </div>
    </div>

    <!-- Year Range Slider -->
    <div v-if="dateRange.preset === 'year-range'" class="space-y-4 p-3 border rounded-lg bg-muted/20">
      <div class="flex items-center gap-2 text-sm font-medium">
        <CalendarDays class="w-4 h-4" />
        <span>Pasirinkti metų intervalą</span>
      </div>

      <div class="space-y-4">
        <!-- Year Range Display -->
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted-foreground">Nuo: <strong>{{ yearRangeValues[0] }}</strong></span>
          <span class="text-muted-foreground">Iki: <strong>{{ yearRangeValues[1] }}</strong></span>
        </div>

        <!-- Slider -->
        <div class="px-2">
          <Slider :model-value="yearRangeValues" :min="oldestYear" :max="currentYear" :step="1" class="w-full"
            @update:model-value="handleYearRangeChange" />
        </div>

        <!-- Year Labels -->
        <div class="flex justify-between text-xs text-muted-foreground px-2">
          <span>{{ oldestYear }}</span>
          <span>{{ currentYear }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex gap-1 pt-2 border-t border-border/50">
      <Button variant="ghost" size="sm" class="h-6 px-1.5 text-xs" :disabled="!hasActiveDateFilter"
        @click="clearDateFilter">
        <RotateCcw class="w-3 h-3 mr-1" />
        Išvalyti
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { format } from 'date-fns'
import { lt } from 'date-fns/locale'
import { CalendarDate, today, getLocalTimeZone, type DateValue } from '@internationalized/date'

// ShadcnVue components
import { Button } from '@/Components/ui/button'
import { Calendar } from '@/Components/ui/calendar'
import { Slider } from '@/Components/ui/slider'
import {
  Popover,
  PopoverContent,
  PopoverTrigger
} from '@/Components/ui/popover'

// Icons
import {
  Calendar as CalendarIcon,
  CalendarDays,
  CalendarCheck,
  X,
  RotateCcw
} from 'lucide-vue-next'

// Types
interface DateRange {
  from?: Date
  to?: Date
  preset?: 'recent' | '3months' | '6months' | '1year' | 'year-range' | 'custom'
}

interface Props {
  dateRange: DateRange
}

interface Emits {
  (e: 'update:dateRange', range: DateRange): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local state
const fromDateOpen = ref(false)
const toDateOpen = ref(false)

// Date presets configuration
const datePresets = [
  {
    key: 'recent',
    label: 'Pastarieji',
    description: '3 mėnesiai',
    icon: '🕒'
  },
  {
    key: '3months',
    label: '3 mėnesiai',
    description: 'Paskutiniai 90 dienų',
    icon: '📅'
  },
  {
    key: '6months',
    label: '6 mėnesiai',
    description: 'Pusmetis atgal',
    icon: '📅'
  },
  {
    key: '1year',
    label: 'Metai',
    description: 'Paskutiniai 12 mėn.',
    icon: '🗓️'
  },
  {
    key: 'year-range',
    label: 'Metų intervalas',
    description: 'Pasirinkti metų intervalą',
    icon: '📊'
  },
  {
    key: 'custom',
    label: 'Pasirinkti',
    description: 'Konkretus intervalas',
    icon: '🎯'
  }
]

// Helper functions
const formatDate = (date: Date | string | null | undefined): string => {
  if (!date) return ''

  const dateObj = date instanceof Date ? date : new Date(date)
  if (isNaN(dateObj.getTime())) return ''

  return format(dateObj, 'yyyy-MM-dd', { locale: lt })
}

// Convert Date to CalendarDate for the Calendar component
const dateToCalendarDate = (date: Date | undefined): CalendarDate | undefined => {
  if (!date) return undefined
  return new CalendarDate(date.getFullYear(), date.getMonth() + 1, date.getDate())
}

// Convert CalendarDate to Date
const calendarDateToDate = (calendarDate: CalendarDate | undefined): Date | undefined => {
  if (!calendarDate) return undefined
  return new Date(calendarDate.year, calendarDate.month - 1, calendarDate.day)
}

// Computed properties
const hasActiveDateFilter = computed(() => {
  // Only show active filter if it's not the default 'recent' preset or has custom dates
  return (props.dateRange.preset && props.dateRange.preset !== 'recent') ||
    props.dateRange.from ||
    props.dateRange.to
})

// Year range slider computed properties
const oldestYear = computed(() => {
  return 1989 // Hardcoded start year for VU SA documents
})

const currentYear = computed(() => {
  return new Date().getFullYear()
})

const yearRangeValues = computed(() => {
  if (props.dateRange.preset === 'year-range' && props.dateRange.from && props.dateRange.to) {
    const fromYear = new Date(props.dateRange.from).getFullYear()
    const toYear = new Date(props.dateRange.to).getFullYear()
    return [fromYear, toYear]
  }
  return [oldestYear.value, currentYear.value]
})

// Date validation functions
const getFromDisabledDates = (date: DateValue): boolean => {
  // Disable future dates and dates after 'to' date
  const todayDate = today(getLocalTimeZone())

  if (date.compare(todayDate) > 0) return true
  if (props.dateRange.to) {
    const toCalendarDate = dateToCalendarDate(props.dateRange.to)
    if (toCalendarDate && date.compare(toCalendarDate) > 0) return true
  }

  return false
}

const getToDisabledDates = (date: DateValue): boolean => {
  // Disable future dates and dates before 'from' date
  const todayDate = today(getLocalTimeZone())

  if (date.compare(todayDate) > 0) return true
  if (props.dateRange.from) {
    const fromCalendarDate = dateToCalendarDate(props.dateRange.from)
    if (fromCalendarDate && date.compare(fromCalendarDate) < 0) return true
  }

  return false
}

// Event handlers
const selectPreset = (preset: string) => {
  let newRange: DateRange = { preset: preset as any }

  if (preset !== 'custom') {
    // Clear custom dates when selecting preset
    newRange.from = undefined
    newRange.to = undefined
  }

  emit('update:dateRange', newRange)
}

const handleFromDateChange = (date: DateValue | undefined) => {
  const newRange: DateRange = {
    ...props.dateRange,
    from: calendarDateToDate(date as CalendarDate),
    preset: 'custom'
  }

  emit('update:dateRange', newRange)
  fromDateOpen.value = false
}

const handleToDateChange = (date: DateValue | undefined) => {
  const newRange: DateRange = {
    ...props.dateRange,
    to: calendarDateToDate(date as CalendarDate),
    preset: 'custom'
  }

  emit('update:dateRange', newRange)
  toDateOpen.value = false
}

const clearCustomRange = () => {
  const newRange: DateRange = {
    preset: 'custom',
    from: undefined,
    to: undefined
  }

  emit('update:dateRange', newRange)
}

const clearDateFilter = () => {
  const newRange: DateRange = {
    preset: 'recent'
  }

  emit('update:dateRange', newRange)
}

const handleYearRangeChange = (value: number[] | undefined) => {
  if (value && value.length === 2) {
    const [fromYear, toYear] = value
    if (typeof fromYear === 'number' && typeof toYear === 'number') {
      const newRange: DateRange = {
        preset: 'year-range',
        from: new Date(fromYear, 0, 1), // January 1st of from year
        to: new Date(toYear, 11, 31)    // December 31st of to year
      }

      emit('update:dateRange', newRange)
    }
  }
}


const getActiveDateRangeDescription = (): string => {
  // Only show description for non-default presets
  if (props.dateRange.preset && props.dateRange.preset !== 'recent' && props.dateRange.preset !== 'custom') {
    const preset = datePresets.find(p => p.key === props.dateRange.preset)
    return preset ? `${preset.label} (${preset.description})` : ''
  }

  if (props.dateRange.from && props.dateRange.to) {
    return `${formatDate(props.dateRange.from)} - ${formatDate(props.dateRange.to)}`
  }

  if (props.dateRange.from) {
    return `Nuo ${formatDate(props.dateRange.from)}`
  }

  if (props.dateRange.to) {
    return `Iki ${formatDate(props.dateRange.to)}`
  }

  return ''
}
</script>
