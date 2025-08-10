<template>
  <div class="space-y-2">
    <!-- Date/Time Information -->
    <div v-if="showDateTime"
      class="flex items-start gap-2.5 p-3 rounded-md border border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/50">
      <div
        class="flex-shrink-0 w-6 h-6 rounded-full bg-vusa-red/10 dark:bg-vusa-red/20 flex items-center justify-center">
        <IFluentCalendarLtr16Regular class="w-3.5 h-3.5 text-vusa-red dark:text-vusa-red" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
          {{ $t('Renginys') }}
        </div>
        <div class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
          {{ formattedDateTime }}
        </div>
        <!-- Date range indicator for multi-day events -->
        <div v-if="endDate && !isSameDay(startDate, endDate)" class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
          {{ $t("iki") }} {{ formattedEndDate }}
        </div>
      </div>
    </div>

    <!-- Location -->
    <div v-if="location"
      class="flex items-start gap-2.5 p-3 rounded-md border border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/50">
      <div
        class="flex-shrink-0 w-6 h-6 rounded-full bg-vusa-red/10 dark:bg-vusa-red/20 flex items-center justify-center">
        <IFluentLocation16Regular class="w-3.5 h-3.5 text-vusa-red dark:text-vusa-red" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
          {{ $t('Vieta') }}
        </div>
        <a v-if="enableLocationLink"
          class="text-xs text-zinc-700 block dark:text-zinc-300 hover:text-vusa-red dark:hover:text-vusa-red hover:underline transition-colors font-medium"
          target="_blank" :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location)}`">
          {{ location }}
        </a>
        <div v-else class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
          {{ location }}
        </div>
      </div>
    </div>

    <!-- Organizer -->
    <div v-if="organizer"
      class="flex items-start gap-2.5 p-3 rounded-md border border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/50">
      <div
        class="flex-shrink-0 w-6 h-6 rounded-full bg-vusa-red/10 dark:bg-vusa-red/20 flex items-center justify-center">
        <IFluentPeopleTeam16Regular class="w-3.5 h-3.5 text-vusa-red dark:text-vusa-red" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
          {{ showOrganizerLabel ? $t("Organizuoja") : $t("Organizatorius") }}
        </div>
        <div class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
          {{ organizer }}
        </div>
      </div>
    </div>

    <!-- Tenant/Department -->
    <div v-if="tenant && showTenant"
      class="flex items-start gap-2.5 p-3 rounded-md border border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/50">
      <div
        class="flex-shrink-0 w-6 h-6 rounded-full bg-vusa-red/10 dark:bg-vusa-red/20 flex items-center justify-center">
        <IFluentBuilding16Regular class="w-3.5 h-3.5 text-vusa-red dark:text-vusa-red" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
          {{ $t('Padalinys') }}
        </div>
        <div class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
          {{ tenant.shortname || tenant.fullname }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { usePage } from '@inertiajs/vue3'

import { formatStaticTime, isSameDay } from '@/Utils/IntlTime'

interface Props {
  /** Start date of the event */
  date: Date | string
  /** End date of the event (optional) */
  endDate?: Date | string | null
  /** Event location */
  location?: string
  /** Event organizer */
  organizer?: string
  /** Tenant/department information */
  tenant?: {
    id: number
    shortname?: string
    fullname?: string
  }
  /** Style variant for different contexts */
  variant?: 'upcoming' | 'past' | 'neutral'
  /** Whether to show date/time info */
  showDateTime?: boolean
  /** Whether to show tenant information */
  showTenant?: boolean
  /** Whether to show "Organizuoja:" label */
  showOrganizerLabel?: boolean
  /** Whether to make location clickable */
  enableLocationLink?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'neutral',
  showDateTime: true,
  showTenant: true,
  showOrganizerLabel: true,
  enableLocationLink: false
})

const page = usePage()
const locale = computed(() => page.props.app.locale)

// Convert to Date objects
const startDate = computed(() => new Date(props.date))
const endDate = computed(() => props.endDate ? new Date(props.endDate) : null)

// Icon color classes based on variant
const iconColorClass = computed(() => {
  switch (props.variant) {
    case 'upcoming':
      return 'text-red-600/80 dark:text-red-400/80'
    case 'past':
      return 'text-zinc-500 dark:text-zinc-400'
    default:
      return 'text-red-500 dark:text-red-400'
  }
})

// Formatted date/time
const formattedDateTime = computed(() => {
  if (endDate.value && isSameDay(startDate.value, endDate.value)) {
    // Same day - show date with time range
    const startTime = formatStaticTime(startDate.value, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric'
    }, locale.value)
    const endTime = formatStaticTime(endDate.value, {
      hour: 'numeric',
      minute: 'numeric'
    }, locale.value)
    return `${startTime} - ${endTime}`
  } else {
    // Single time or multi-day event start
    return formatStaticTime(startDate.value, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: 'numeric'
    }, locale.value)
  }
})

// Formatted end date for multi-day events
const formattedEndDate = computed(() => {
  if (!endDate.value) return ''

  return formatStaticTime(endDate.value, {
    month: 'short',
    day: 'numeric'
  }, locale.value)
})
</script>
