<template>
  <div
    data-tour="institution-item"
    class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white/60 dark:bg-zinc-800/50 transition-all duration-200 hover:shadow-sm hover:bg-white/80 dark:hover:bg-zinc-700/50 hover:border-zinc-300 dark:hover:border-zinc-600">
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
        <!-- Muted indicator -->
        <TooltipProvider v-if="isMuted">
          <Tooltip>
            <TooltipTrigger as-child>
              <BellOff class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500 shrink-0" />
            </TooltipTrigger>
            <TooltipContent>{{ $t('visak.notifications_muted') }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <!-- Duty-based badge -->
        <span v-if="isDutyBased && showSubscriptionActions"
          class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-medium shrink-0">
          {{ $t('visak.duty_based') }}
        </span>
      </div>

      <!-- Status row -->
      <div class="flex items-center gap-2 ml-5">
        <!-- Active check-in badge -->
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <span v-if="institution.active_check_in"
                class="inline-flex items-center gap-1 sm:gap-1.5 text-xs px-1.5 sm:px-2.5 py-1 sm:py-1.5 rounded-full border font-medium bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-700/50">
                <CalendarCheck class="h-3 w-3 sm:hidden shrink-0" />
                <div class="hidden sm:block w-2 h-2 rounded-full bg-current opacity-75" />
                <span class="hidden sm:inline">{{ $t('Pranešta apie nebuvimą') }}</span>
                <span v-if="institution.active_check_in?.end_date" class="hidden sm:inline opacity-75">
                  {{ $t('iki') }} {{ formatDate(institution.active_check_in.end_date) }}
                </span>
              </span>
              <!-- Upcoming meeting badge -->
              <span v-else-if="nextMeetingDate"
                class="inline-flex items-center gap-1 sm:gap-1.5 text-xs px-1.5 sm:px-2.5 py-1 sm:py-1.5 rounded-full border font-medium bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-700/50">
                <CalendarClock class="h-3 w-3 sm:hidden shrink-0" />
                <div class="hidden sm:block w-2 h-2 rounded-full bg-current opacity-75" />
                <span class="hidden sm:inline">{{ $t('Suplanuotas susitikimas') }}</span>
                <span class="opacity-75">{{ formatDate(nextMeetingDate) }}</span>
              </span>
              <!-- Needs meeting badge -->
              <span v-else-if="!lastMeetingDate && !nextMeetingDate"
                class="inline-flex items-center gap-1 text-xs px-1.5 sm:px-2.5 py-1 rounded-full bg-zinc-100 text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700 shrink-0 font-medium">
                <CalendarX class="h-3 w-3 sm:hidden shrink-0" />
                <span class="hidden sm:inline">{{ $t('Reikia susitikimo') }}</span>
              </span>
              <!-- Overdue badge -->
              <span v-else-if="isOverdue"
                class="inline-flex items-center gap-1 text-xs px-1.5 sm:px-2.5 py-1 rounded-full bg-orange-100 text-orange-700 border border-orange-200 dark:bg-orange-900/40 dark:text-orange-300 dark:border-orange-700/50 shrink-0 font-medium">
                <AlertTriangle class="h-3 w-3 sm:hidden shrink-0" />
                <span class="hidden sm:inline">{{ $t('Senokas susitikimas') }}</span>
              </span>
              <!-- Approaching badge -->
              <span v-else-if="isApproaching"
                class="inline-flex items-center gap-1 text-xs px-1.5 sm:px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-700/50 shrink-0 font-medium">
                <Clock class="h-3 w-3 sm:hidden shrink-0" />
                <span class="hidden sm:inline">{{ $t('Artėja susitikimo laikas') }}</span>
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              <template v-if="institution.active_check_in">
                {{ $t('Pranešta apie nebuvimą') }}
                <span v-if="institution.active_check_in.end_date"> {{ $t('iki') }} {{ formatDate(institution.active_check_in.end_date) }}</span>
                <p v-if="institution.active_check_in.note" class="mt-1 text-xs opacity-80">{{ institution.active_check_in.note }}</p>
              </template>
              <template v-else-if="nextMeetingDate">{{ $t('Suplanuotas susitikimas') }} {{ formatDate(nextMeetingDate) }}</template>
              <template v-else-if="!lastMeetingDate && !nextMeetingDate">{{ $t('Reikia susitikimo') }}</template>
              <template v-else-if="isOverdue">{{ $t('Senokas susitikimas') }} ({{ daysSinceLast }} {{ $t('d.') }})</template>
              <template v-else-if="isApproaching">{{ $t('Artėja susitikimo laikas') }}</template>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <!-- Last meeting info - only show when no upcoming meeting -->
        <span v-if="!institution.active_check_in && !nextMeetingDate && lastMeetingDate" class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ formatDate(lastMeetingDate) }}
          <span v-if="isOverdue" class="text-orange-600 dark:text-orange-400 font-medium">
            ({{ daysSinceLast }} {{ $t('d.') }})
          </span>
          <span v-else-if="isApproaching" class="text-amber-600 dark:text-amber-400 font-medium">
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
              <CalendarOff class="h-3.5 w-3.5" />
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

      <!-- Subscription Actions (only for non-duty-based institutions) -->
      <template v-if="canShowSubscriptionActions">
        <!-- Divider -->
        <div class="w-px h-6 bg-zinc-200 dark:bg-zinc-700 mx-1" />

        <!-- Follow/Unfollow button -->
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button variant="ghost" size="sm"
                class="h-8 w-8 opacity-60 hover:opacity-100 transition-all"
                :class="isFollowed ? 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                :disabled="followLoading"
                @click="emit('toggle-follow', institution.id)">
                <Loader2 v-if="followLoading" class="h-3.5 w-3.5 animate-spin" />
                <Eye v-else-if="isFollowed" class="h-3.5 w-3.5" />
                <EyeOff v-else class="h-3.5 w-3.5" />
              </Button>
            </TooltipTrigger>
            <TooltipContent>
              {{ isFollowed ? $t('visak.unfollow') : $t('visak.follow') }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <!-- Mute/Unmute button (only show if followed) -->
        <TooltipProvider v-if="isFollowed">
          <Tooltip>
            <TooltipTrigger as-child>
              <Button variant="ghost" size="sm"
                class="h-8 w-8 opacity-60 hover:opacity-100 transition-all"
                :class="isMuted ? 'text-zinc-500 dark:text-zinc-400' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'"
                :disabled="muteLoading"
                @click="emit('toggle-mute', institution.id)">
                <Loader2 v-if="muteLoading" class="h-3.5 w-3.5 animate-spin" />
                <BellOff v-else-if="isMuted" class="h-3.5 w-3.5" />
                <Bell v-else class="h-3.5 w-3.5" />
              </Button>
            </TooltipTrigger>
            <TooltipContent>
              {{ isMuted ? $t('visak.unmute') : $t('visak.mute') }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </template>
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
import { Globe, Info, Eye, EyeOff, Bell, BellOff, Loader2, CalendarOff, CalendarCheck, CalendarClock, CalendarX, AlertTriangle, Clock } from 'lucide-vue-next'
import { formatStaticTime } from '@/Utils/IntlTime'
import type { AtstovavimosInstitution } from '@/Pages/Admin/Dashboard/types'

const props = defineProps<{
  institution: AtstovavimosInstitution
  showActions?: boolean
  showSubscriptionActions?: boolean
  canScheduleMeeting?: boolean
  canAddCheckIn?: boolean
  followLoading?: boolean
  muteLoading?: boolean
}>()

const emit = defineEmits<{
  'schedule-meeting': [institutionId: string]
  'add-check-in': [institutionId: string]
  'remove-active-check-in': [institutionId: string]
  'toggle-follow': [institutionId: string]
  'toggle-mute': [institutionId: string]
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

// Get periodicity from institution (accessor handles inheritance from types, defaults to 30)
const periodicity = computed(() => {
  return (props.institution as any).meeting_periodicity_days ?? 30
})

// Calculate thresholds based on periodicity
const APPROACHING_THRESHOLD = 0.8 // 80% of periodicity
const isApproaching = computed(() => {
  if (daysSinceLast.value === undefined) return false
  return daysSinceLast.value >= (periodicity.value * APPROACHING_THRESHOLD) && daysSinceLast.value <= periodicity.value
})

const isOverdue = computed(() => {
  if (daysSinceLast.value === undefined) return false
  return daysSinceLast.value > periodicity.value
})

const statusDotClass = computed(() => {
  const hasUpcoming = (props.institution.upcoming_meetings_count || 0) > 0
  if (hasUpcoming || props.institution.active_check_in) {
    return 'bg-emerald-400'
  }
  if (isOverdue.value) {
    return 'bg-orange-400'
  }
  if (isApproaching.value) {
    return 'bg-amber-400'
  }
  if (!lastMeetingDate.value) {
    return 'bg-zinc-400'
  }
  return 'bg-zinc-400'
})

// Subscription status helpers
const subscription = computed(() => props.institution.subscription)
const isDutyBased = computed(() => subscription.value?.is_duty_based ?? false)
const isFollowed = computed(() => subscription.value?.is_followed ?? false)
const isMuted = computed(() => subscription.value?.is_muted ?? false)

// Only show subscription actions for non-duty-based institutions (or when explicitly enabled)
const canShowSubscriptionActions = computed(() => {
  return props.showSubscriptionActions && !isDutyBased.value
})

function formatDate(date: Date | string): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date
  return formatStaticTime(dateObj, { month: 'short', day: 'numeric' })
}
</script>
