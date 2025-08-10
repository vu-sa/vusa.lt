<template>
  <div class="flex-shrink-0 rounded-md border px-3 py-2 text-center" :class="[
    sizeClasses,
    variant === 'upcoming'
      ? 'border-red-100/70 bg-red-50/70 dark:border-red-900/50 dark:bg-red-950/30'
      : 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800'
  ]">
    <!-- Month -->
    <div class="text-xs font-semibold uppercase" :class="variant === 'upcoming'
      ? 'text-red-600/80 dark:text-red-400/80'
      : 'text-zinc-500 dark:text-zinc-400'">
      {{ monthText }}
    </div>

    <!-- Day -->
    <div class="text-2xl font-bold" :class="variant === 'upcoming'
      ? 'text-red-700/90 dark:text-red-300/90'
      : 'text-zinc-700 dark:text-zinc-300'">
      {{ dayText }}
    </div>

    <!-- Year for past events -->
    <div v-if="showYear" class="mb-1 text-xs font-medium text-zinc-600 dark:text-zinc-400">
      {{ yearText }}
    </div>

    <!-- Separator when showing year -->
    <div v-if="showYear" class="my-1 h-px w-full bg-zinc-200 dark:bg-zinc-700" />

    <!-- Time display -->
    <div class="text-xs flex flex-col" :class="variant === 'upcoming'
      ? 'text-red-600/80 dark:text-red-400/80'
      : 'text-zinc-600 dark:text-zinc-400'">
      <span>{{ startTimeText }}</span>

      <!-- End time for same-day events -->
      <template v-if="endDate && isSameDay(date, endDate)">
        <div class="flex items-center justify-center">
          <svg class="w-3 h-3 my-px" viewBox="0 0 24 24" fill="currentColor">
            <path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z" />
          </svg>
        </div>
        <span>{{ endTimeText }}</span>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { formatEventDate, formatEventTime, formatEventYear, isSameDay } from '@/Utils/IntlTime'

interface Props {
  date: Date | string
  endDate?: Date | string | null
  variant?: 'upcoming' | 'past'
  size?: 'sm' | 'md'
  showYear?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'upcoming',
  size: 'md',
  showYear: false
})

const page = usePage()
const locale = computed(() => page.props.app.locale)

// Convert to Date objects for consistent handling
const date = computed(() => new Date(props.date))
const endDate = computed(() => props.endDate ? new Date(props.endDate) : null)

// Size classes
const sizeClasses = computed(() => {
  return props.size === 'sm' ? 'w-16 px-2 py-1' : 'w-18 px-3 py-2'
})

// Formatted text values
const monthText = computed(() => {
  return formatEventDate(date.value, locale.value).split(' ')[0]
})

const dayText = computed(() => {
  return formatEventDate(date.value, locale.value).split(' ')[1]
})

const yearText = computed(() => {
  return formatEventYear(date.value, locale.value)
})

const startTimeText = computed(() => {
  return formatEventTime(date.value, locale.value)
})

const endTimeText = computed(() => {
  return endDate.value ? formatEventTime(endDate.value, locale.value) : ''
})
</script>
