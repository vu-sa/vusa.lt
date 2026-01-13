<script lang="ts" setup>
import { cn } from '@/Utils/Shadcn/utils'
import { CalendarRoot, type CalendarRootEmits, type CalendarRootProps, useForwardPropsEmits } from 'reka-ui'
import { CalendarDate, getLocalTimeZone, today } from '@internationalized/date'
import { computed, ref, onMounted, type HTMLAttributes } from 'vue'
import { CalendarCell, CalendarCellTrigger, CalendarGrid, CalendarGridBody, CalendarGridHead, CalendarGridRow, CalendarHeadCell, CalendarHeader, CalendarHeading, CalendarNextButton, CalendarPrevButton } from '.'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select"

interface CalendarPropsExtended extends CalendarRootProps {
  class?: HTMLAttributes['class']
  yearRange?: [number, number]
}

const props = withDefaults(defineProps<CalendarPropsExtended>(), {
  yearRange: () => [1989, new Date().getFullYear() + 1]
})
const emits = defineEmits<CalendarRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, yearRange: __, ...delegated } = props
  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)

// Month names for the dropdown
const monthNames = [
  'Sausis', 'Vasaris', 'Kovas', 'Balandis', 'Gegužė', 'Birželis',
  'Liepa', 'Rugpjūtis', 'Rugsėjis', 'Spalis', 'Lapkritis', 'Gruodis'
]

// Generate years array from range
const years = computed(() => {
  const [start, end] = props.yearRange
  return Array.from({ length: end - start + 1 }, (_, i) => start + i).reverse()
})

// Initialize with today's date or from model value
const getInitialPlaceholder = (): CalendarDate => {
  // Check if there's a modelValue or placeholder prop
  if (props.modelValue) {
    const mv = props.modelValue as any
    if (mv.year && mv.month) {
      return new CalendarDate(mv.year, mv.month, mv.day || 1)
    }
  }
  if (props.placeholder) {
    const ph = props.placeholder as any
    if (ph.year && ph.month) {
      return new CalendarDate(ph.year, ph.month, ph.day || 1)
    }
  }
  // Default to today
  return today(getLocalTimeZone())
}

// Track the current placeholder value for programmatic navigation
const currentPlaceholder = ref<CalendarDate>(getInitialPlaceholder())

// Update placeholder when it changes from CalendarRoot
const handlePlaceholderChange = (value: CalendarDate) => {
  if (value) {
    currentPlaceholder.value = value
  }
}

// Handle month selection
const handleMonthSelect = (monthIndex: string) => {
  const newPlaceholder = new CalendarDate(
    currentPlaceholder.value.year,
    parseInt(monthIndex),
    1
  )
  currentPlaceholder.value = newPlaceholder
}

// Handle year selection
const handleYearSelect = (year: string) => {
  const newPlaceholder = new CalendarDate(
    parseInt(year),
    currentPlaceholder.value.month,
    1
  )
  currentPlaceholder.value = newPlaceholder
}
</script>

<template>
  <CalendarRoot
    v-slot="{ grid, weekDays }"
    data-slot="calendar"
    :class="cn('p-3', props.class)"
    :placeholder="currentPlaceholder"
    v-bind="forwarded"
    @update:placeholder="handlePlaceholderChange"
  >
    <CalendarHeader class="w-full justify-between">
      <CalendarHeading>
        <div class="flex items-center gap-1">
          <Select :model-value="String(currentPlaceholder.month)" @update:model-value="handleMonthSelect">
            <SelectTrigger class="h-7 w-auto gap-0.5 border-none px-1.5 text-sm font-medium hover:bg-accent focus:ring-0 focus:ring-offset-0">
              <SelectValue>
                {{ monthNames[currentPlaceholder.month - 1] }}
              </SelectValue>
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="(month, index) in monthNames" :key="index" :value="String(index + 1)">
                {{ month }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Select :model-value="String(currentPlaceholder.year)" @update:model-value="handleYearSelect">
            <SelectTrigger class="h-7 w-auto gap-0.5 border-none px-1.5 text-sm font-medium hover:bg-accent focus:ring-0 focus:ring-offset-0">
              <SelectValue>
                {{ currentPlaceholder.year }}
              </SelectValue>
            </SelectTrigger>
            <SelectContent class="max-h-60">
              <SelectItem v-for="year in years" :key="year" :value="String(year)">
                {{ year }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CalendarHeading>

      <div class="flex items-center">
        <CalendarPrevButton />
        <CalendarNextButton />
      </div>
    </CalendarHeader>

    <div class="flex flex-col gap-y-4 mt-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
      <CalendarGrid v-for="month in grid" :key="month.value.toString()">
        <CalendarGridHead>
          <CalendarGridRow>
            <CalendarHeadCell
              v-for="day in weekDays" :key="day"
            >
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>
        <CalendarGridBody>
          <CalendarGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`" class="mt-2 w-full">
            <CalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
            >
              <CalendarCellTrigger
                :day="weekDate"
                :month="month.value"
              />
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </div>
  </CalendarRoot>
</template>
