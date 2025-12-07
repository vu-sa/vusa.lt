<template>
  <div
    class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white/80 dark:bg-zinc-700/50 transition-all duration-200 hover:shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-600/50">
    <!-- Left: Institution Info -->
    <div class="flex-1 min-w-0 mr-3">
      <div class="flex items-center gap-2.5 mb-1.5">
        <!-- Status dot -->
        <div class="w-3 h-3 rounded-full shrink-0 ring-2 ring-white dark:ring-zinc-700" :class="statusDotClass" />

        <!-- Institution name -->
        <InertiaLink :href="route('institutions.show', institution.id)"
          class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate">
        {{ institution.name }}
        </InertiaLink>
        <!-- Public meetings indicator -->
        <TooltipProvider v-if="institution.has_public_meetings">
          <Tooltip>
            <TooltipTrigger as-child>
              <Globe class="h-3.5 w-3.5 text-green-600 dark:text-green-400 shrink-0" />
            </TooltipTrigger>
            <TooltipContent>{{ $t('Vieši posėdžiai') }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>

      <!-- Status row -->
      <div class="flex items-center gap-2 ml-5">
        <!-- Active check-in badge -->
        <span v-if="institution.active_check_in"
          class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-full border font-medium bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700/50">
          <div class="w-2 h-2 rounded-full bg-current opacity-75" />
          {{ $t('Pranešta apie nebuvimą') }}
          <span v-if="institution.active_check_in?.end_date" class="opacity-75">
            {{ $t('iki') }} {{ formatDate(institution.active_check_in.end_date) }}
          </span>
          <!-- Info icon with tooltip for check-in note -->
          <TooltipProvider v-if="institution.active_check_in?.note">
            <Tooltip>
              <TooltipTrigger as-child>
                <Info class="h-3.5 w-3.5 opacity-70 hover:opacity-100 cursor-help" />
              </TooltipTrigger>
              <TooltipContent side="top" class="max-w-xs">
                <p class="text-sm">{{ institution.active_check_in.note }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </span>

        <!-- Fallback status badges -->
        <!-- Upcoming meeting badge -->
        <span v-else-if="nextMeetingDate"
          class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-full border font-medium bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-700/50">
          <div class="w-2 h-2 rounded-full bg-current opacity-75" />
          {{ $t('Suplanuotas susitikimas') }}
          <span class="opacity-75">
            {{ formatDate(nextMeetingDate) }}
          </span>
        </span>
        <!-- Needs meeting badge (only if no past AND no upcoming) -->
        <span v-else-if="!lastMeetingDate && !nextMeetingDate"
          class="text-xs px-2.5 py-1 rounded-full bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 shrink-0 font-medium">
          {{ $t('Reikia susitikimo') }}
        </span>
        <span v-else-if="daysSinceLast !== undefined && daysSinceLast > 60"
          class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700/50 shrink-0 font-medium">
          {{ $t('Senokas susitikimas') }}
        </span>

        <!-- Last meeting info -->
        <span v-if="!institution.active_check_in && lastMeetingDate" class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ formatDate(lastMeetingDate) }}
          <span v-if="daysSinceLast !== undefined && daysSinceLast > 30" class="text-amber-600 dark:text-amber-400 font-medium">
            ({{ daysSinceLast }} {{ $t('d.') }})
          </span>
        </span>
      </div>
    </div>

    <!-- Right: Action Buttons (optional) -->
    <div v-if="showActions" class="flex items-center gap-2 flex-shrink-0">
      <!-- Schedule Meeting button -->
      <TooltipProvider v-if="canScheduleMeeting">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="default" size="sm" class="h-8 w-8" @click="emit('schedule-meeting', institution.id)">
              <component :is="Icons.MEETING" class="h-3.5 w-3.5" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Suplanuoti naują susitikimą šiai institucijai') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <!-- Add Check-in button -->
      <TooltipProvider v-if="canAddCheckIn && !institution.active_check_in">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="sm"
              class="h-8 w-8 opacity-60 hover:opacity-100 hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-900/30 dark:hover:text-amber-400 transition-all"
              @click="emit('add-check-in', institution.id)">
              <component :is="Icons.NOTIFICATION" class="h-3.5 w-3.5" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Pranešti apie posėdžio nebuvimą') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <!-- Remove Active Check-in if exists -->
      <TooltipProvider v-if="institution.active_check_in">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="sm"
              class="h-8 w-8 opacity-60 hover:opacity-100 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition-all"
              @click="emit('remove-active-check-in', institution.id)">
              <component :is="Icons.CLOSE" class="h-3.5 w-3.5" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Pašalinti pranešimą apie posėdžio nebuvimą') }}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Link as InertiaLink } from '@inertiajs/vue3'

import { Button } from '@/Components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip'
import Icons from '@/Types/Icons/filled'
import { Globe, Info } from 'lucide-vue-next'
import { formatStaticTime } from '@/Utils/IntlTime'
import type { AtstovavimosInstitution } from '@/Pages/Admin/Dashboard/types'

const props = defineProps<{
  institution: AtstovavimosInstitution
  showActions?: boolean
  canScheduleMeeting?: boolean
  canAddCheckIn?: boolean
}>()

const emit = defineEmits<{
  'schedule-meeting': [institutionId: string]
  'add-check-in': [institutionId: string]
  'remove-active-check-in': [institutionId: string]
}>()

// Helper functions
const lastMeetingDate = computed(() => {
  if (!Array.isArray(props.institution.meetings)) return undefined
  const now = new Date()
  const past = props.institution.meetings
    .map(m => new Date(m.start_time))
    .filter(d => d <= now)
    .sort((a, b) => b.getTime() - a.getTime())
  return past[0]
})

const nextMeetingDate = computed(() => {
  if (!Array.isArray(props.institution.meetings)) return undefined
  const now = new Date()
  const upcoming = props.institution.meetings
    .map(m => new Date(m.start_time))
    .filter(d => d > now)
    .sort((a, b) => a.getTime() - b.getTime())
  return upcoming[0]
})

const daysSinceLast = computed(() => {
  if (typeof props.institution.days_since_last_meeting === 'number') {
    return props.institution.days_since_last_meeting
  }
  if (!lastMeetingDate.value) return undefined
  const diffMs = Date.now() - lastMeetingDate.value.getTime()
  return Math.floor(diffMs / (1000 * 60 * 60 * 24))
})

const statusDotClass = computed(() => {
  const hasUpcoming = (props.institution.upcoming_meetings_count || 0) > 0
  if (hasUpcoming || props.institution.active_check_in) {
    return 'bg-emerald-400'
  }
  if (daysSinceLast.value !== undefined && daysSinceLast.value > 60) {
    return 'bg-amber-400'
  }
  if (!lastMeetingDate.value) {
    return 'bg-rose-400'
  }
  return 'bg-zinc-400'
})

function formatDate(date: Date | string): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date
  return formatStaticTime(dateObj, { month: 'short', day: 'numeric' })
}
</script>
