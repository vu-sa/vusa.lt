<template>
  <Card class="flex flex-col relative overflow-hidden" role="region" :aria-label="$t('Tavo institucijos')">
    <!-- Status indicator corner -->
    <div :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2.5">
        <component :is="Icons.INSTITUTION" :class="iconClasses" aria-hidden="true" />
        <span class="font-semibold">{{ $t('Tavo institucijos') }}</span>
        <span v-if="limitedInstitutions.length < institutions.length"
          class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 ml-auto font-medium">
          {{ limitedInstitutions.length }}/{{ institutions.length }}
        </span>
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 space-y-4">
      <!-- Institution List - improved spacing -->
      <div class="space-y-2">
        <div v-for="inst in limitedInstitutions" :key="inst.id"
          class="flex items-center justify-between p-3 rounded-lg border transition-all duration-200 hover:shadow-sm hover:bg-gray-50/30"
          :class="rowShadeClass(inst)">
          
          <!-- Institution Info -->
          <div class="flex-1 min-w-0 mr-3">
            <div class="flex items-center gap-2.5 mb-1.5">
              <!-- Status dot - larger and more prominent -->
              <div class="w-3 h-3 rounded-full flex-shrink-0 ring-2 ring-white dark:ring-gray-900" :class="statusDotClass(inst)" />
              
              <!-- Institution name -->
              <span class="font-semibold text-sm text-gray-900 dark:text-gray-100 truncate">{{ inst.name }}</span>
            </div>
            
            <!-- Status row -->
            <div class="flex items-center gap-2 ml-5">
              <!-- Simple check-in badge for now -->
              <span v-if="inst.active_check_in" 
                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-full border font-medium cursor-pointer hover:shadow-sm transition-all"
                    :class="inst.active_check_in && isBlackout(inst.active_check_in?.mode) 
                      ? 'bg-emerald-100 text-emerald-800 border-emerald-200' 
                      : 'bg-sky-100 text-sky-800 border-sky-200'">
                <!-- Simple status indicator -->
                <div class="w-2 h-2 rounded-full bg-current opacity-75"></div>
                {{ isBlackout(inst.active_check_in?.mode) ? $t('Padengta') : $t('Heads-up') }}
                <span v-if="inst.active_check_in?.until_date" class="opacity-75">
                  {{ $t('iki') }} {{ formatStaticTime(new Date(inst.active_check_in.until_date), { month: 'short', day: 'numeric' }) }}
                </span>
              </span>
              
              <!-- Fallback status badges -->
              <span v-else-if="!lastMeetingDate(inst)" 
                    class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200 flex-shrink-0 font-medium">
                {{ $t('Reikia susitikimo') }}
              </span>
              <span v-else-if="daysSinceLast(inst) !== undefined && daysSinceLast(inst)! > 60" 
                    class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200 flex-shrink-0 font-medium">
                {{ $t('Senokas susitikimas') }}
              </span>
              
              <!-- Last meeting info -->
              <span v-if="!inst.active_check_in && lastMeetingDate(inst)" 
                   class="text-xs text-gray-500">
                {{ formatStaticTime(lastMeetingDate(inst)!, { month: 'short', day: 'numeric' }) }}
                <span v-if="daysSinceLast(inst) !== undefined && daysSinceLast(inst)! > 30" class="text-amber-600 font-medium">
                  ({{ daysSinceLast(inst) }} {{ $t('d.') }})
                </span>
              </span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Primary: Schedule Meeting - neutral button without text -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button variant="default" size="sm" class="h-8 w-8"
                    @click="$emit('schedule-meeting', inst.id)">
                    <component :is="Icons.MEETING" class="h-3.5 w-3.5" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Suplanuoti naują susitikimą šiai institucijai') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>

            <!-- Secondary: Add Check-in (only if no active check-in) -->
            <TooltipProvider v-if="!inst.active_check_in">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button variant="ghost" size="sm" class="h-8 w-8 opacity-60 hover:opacity-100 hover:bg-orange-50 hover:text-orange-700 transition-all"
                    @click="handleAddCheckIn(inst.id)">
                    <component :is="Icons.NOTIFICATION" class="h-3.5 w-3.5" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Pridėti pažymą institucijai') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        </div>
      </div>

      <!-- Activity Overview - simplified -->
      <div v-if="institutions.length > 0" class="space-y-3 pt-2 border-t border-gray-100">
        <!-- Progress indicator -->
        <div class="w-full h-1.5 rounded-full bg-gray-100 overflow-hidden flex">
          <div :style="{ width: `${(segCounts.green / institutions.length) * 100}%` }" 
               class="h-1.5 bg-emerald-400" />
          <div :style="{ width: `${(segCounts.blue / institutions.length) * 100}%` }" 
               class="h-1.5 bg-sky-400" />
          <div :style="{ width: `${(segCounts.red / institutions.length) * 100}%` }" 
               class="h-1.5 bg-gray-400" />
        </div>

        <!-- Critical attention callout - Smaller -->
        <div v-if="institutionsNeedingAttention.length > 0" 
             class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
          <div class="text-xs font-medium text-gray-700 mb-1 flex items-center gap-1">
            <component :is="Icons.NOTIFICATION" class="h-3 w-3" />
            {{ institutionsNeedingAttention.length }} {{ $t('reikia dėmesio') }}
          </div>
          <Button size="sm" variant="outline" class="h-6 text-xs border-gray-300 text-gray-700 hover:bg-gray-100" 
                  @click="handleAddCheckInForPriority">
            <component :is="Icons.PLUS" class="h-3 w-3 mr-1" />
            {{ $t('Pridėti pažymą') }}
          </Button>
        </div>
      </div>
    </CardContent>

    <CardFooter class="border-t bg-gray-50/40 p-4 relative z-10">
      <div class="flex gap-3 w-full">
        <Button size="sm" variant="outline" class="flex-1 font-medium" @click="$emit('show-all-modal')">
          <component :is="Icons.INSTITUTION" class="h-3.5 w-3.5 mr-2" />
          {{ $t('Visos institucijos') }}
        </Button>
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button size="sm" variant="default" class="w-11 h-9" @click="$emit('create-meeting')">
                <component :is="Icons.MEETING" class="h-3.5 w-3.5" />
              </Button>
            </TooltipTrigger>
            <TooltipContent>{{ $t('Naujas susitikimas') }}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>
    </CardFooter>

    <!-- Create Check-in Dialog -->
    <AddCheckInDialog v-if="showCreateCheckIn" :open="!!showCreateCheckIn" :institution-id="showCreateCheckIn"
      @close="showCreateCheckIn = null" @created="handleCheckInCreated" />
  </Card>
</template>

<script setup lang="ts">
import { computed, ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { NNumberAnimation } from 'naive-ui'

import AddCheckInDialog from './AddCheckInDialog.vue'

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip'
import Icons from '@/Types/Icons/filled'
import { formatStaticTime } from '@/Utils/IntlTime'
import type { AtstovavimosInstitution } from '@/Pages/Admin/Dashboard/types'

const props = defineProps<{
  institutions: AtstovavimosInstitution[]
  isAdmin?: boolean
  maxDisplayCount?: number
  currentUserId?: string
}>()

const emit = defineEmits<{
  'show-all-modal': []
  'refresh': []
  'schedule-meeting': [institutionId: string]
  'show-institution-details': [institutionId: string]
  'create-meeting': []
}>()

const showAllInstitutions = ref(false)
const showCreateCheckIn = ref<string | null>(null)
const actionLoading = reactive<Record<string, boolean>>({})

// Computed values for institution analysis
// Coverage means: has an upcoming meeting OR an active blackout check-in (heads-up does not count as coverage)
const institutionsWithCheckInsOrMeetings = computed(() => {
  return props.institutions.filter(inst =>
    (inst.active_check_in && isBlackout(inst.active_check_in.mode)) ||
    (Array.isArray(inst.meetings) && inst.meetings.some((meeting: any) => new Date(meeting.start_time) > new Date()))
  )
})

const institutionsNeedingAttention = computed(() => {
  return props.institutions.filter(inst => {
    const hasCoverageCheckIn = !!(inst.active_check_in && isBlackout(inst.active_check_in.mode))
    const hasUpcomingMeeting = Array.isArray(inst.meetings) && inst.meetings.some((meeting: any) => new Date(meeting.start_time) > new Date())
    return !hasCoverageCheckIn && !hasUpcomingMeeting
  })
})

const coveredInstitutionsCount = computed(() => institutionsWithCheckInsOrMeetings.value.length)

// Calculate total upcoming items: upcoming meetings + active blackout check-ins (heads-up does not reduce urgency)
const upcomingMeetingsCount = computed(() => {
  return props.institutions.reduce((total, inst) => {
    const meetingCount = inst.upcoming_meetings_count || 0
    const blackoutCheckIn = inst.active_check_in && isBlackout(inst.active_check_in.mode) ? 1 : 0
    return total + meetingCount + blackoutCheckIn
  }, 0)
})

// Sort institutions by priority - attention needed institutions first
const sortedInstitutions = computed(() => {
  return [...props.institutions].sort((a, b) => {
    const aHasCoverage = !!(a.active_check_in && isBlackout(a.active_check_in.mode))
    const bHasCoverage = !!(b.active_check_in && isBlackout(b.active_check_in.mode))
    const aHasUpcoming = !!a.upcoming_meetings_count && a.upcoming_meetings_count > 0
    const bHasUpcoming = !!b.upcoming_meetings_count && b.upcoming_meetings_count > 0

    // Priority 1: Institutions needing attention (no coverage check-in AND no upcoming meetings)
    const aNeedsAttention = !aHasCoverage && !aHasUpcoming
    const bNeedsAttention = !bHasCoverage && !bHasUpcoming

    if (aNeedsAttention !== bNeedsAttention) {
      return aNeedsAttention ? -1 : 1
    }

    // Priority 2: No upcoming meetings (but has coverage check-in)
    const aNoMeetings = !aHasUpcoming
    const bNoMeetings = !bHasUpcoming

    if (aNoMeetings !== bNoMeetings) {
      return aNoMeetings ? -1 : 1
    }

    // Priority 3: By days since last meeting (older meetings first)
    if (a.days_since_last_meeting && b.days_since_last_meeting) {
      return b.days_since_last_meeting - a.days_since_last_meeting
    }

    // Priority 4: Check-ins expiring soon
    if (a.active_check_in && b.active_check_in) {
      const aDays = Math.ceil((new Date(a.active_check_in.until_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
      const bDays = Math.ceil((new Date(b.active_check_in.until_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
      return aDays - bDays
    }

    // Priority 5: Alphabetical
    return a.name.localeCompare(b.name)
  })
})

const limitedInstitutions = computed(() => {
  return sortedInstitutions.value.slice(0, props.maxDisplayCount)
})

// Determine overall urgency level for theming
const urgencyLevel = computed(() => {
  const needingAttentionCount = institutionsNeedingAttention.value.length
  const totalCount = props.institutions.length

  if (needingAttentionCount === 0) return 'success'
  if (needingAttentionCount / totalCount > 0.5) return 'danger'
  return 'warning'
})

// Dynamic CSS classes based on urgency - only for specific elements

const statusIndicatorClasses = computed(() => {
  const base = 'absolute top-0 right-0 w-16 h-16 -mr-8 -mt-8 rotate-45'
  const urgencyClasses = {
    success: 'bg-green-200 dark:bg-green-800',
    warning: 'bg-amber-200 dark:bg-amber-800',
    danger: 'bg-gray-200 dark:bg-gray-800'
  }
  return `${base} ${urgencyClasses[urgencyLevel.value]}`
})

const iconClasses = computed(() => {
  const urgencyClasses = {
    success: 'h-5 w-5 text-green-600 dark:text-green-400',
    warning: 'h-5 w-5 text-amber-600 dark:text-amber-400',
    danger: 'h-5 w-5 text-gray-600 dark:text-gray-400'
  }
  return urgencyClasses[urgencyLevel.value]
})

const summaryBorderClasses = computed(() => {
  const urgencyClasses = {
    success: 'border-green-200 dark:border-green-700',
    warning: 'border-amber-200 dark:border-amber-700',
    danger: 'border-gray-200 dark:border-gray-700'
  }
  return urgencyClasses[urgencyLevel.value]
})

const summaryTextClasses = computed(() => {
  const urgencyClasses = {
    success: 'text-green-700 dark:text-green-300',
    warning: 'text-amber-700 dark:text-amber-300',
    danger: 'text-gray-700 dark:text-gray-300'
  }
  return urgencyClasses[urgencyLevel.value]
})

const summarySubtextClasses = computed(() => {
  const urgencyClasses = {
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-amber-600 dark:text-amber-400',
    danger: 'text-gray-600 dark:text-gray-400'
  }
  return urgencyClasses[urgencyLevel.value]
})

const progressBackgroundClasses = computed(() => {
  const urgencyClasses = {
    success: 'bg-green-200 dark:bg-green-700',
    warning: 'bg-amber-200 dark:bg-amber-700',
    danger: 'bg-gray-200 dark:bg-gray-700'
  }
  return urgencyClasses[urgencyLevel.value]
})

const progressBarClasses = computed(() => {
  const urgencyClasses = {
    success: 'bg-green-600 dark:bg-green-400',
    warning: 'bg-amber-600 dark:bg-amber-400',
    danger: 'bg-gray-600 dark:bg-gray-400'
  }
  return urgencyClasses[urgencyLevel.value]
})

const insightTextClasses = computed(() => {
  const urgencyClasses = {
    success: 'text-green-500 dark:text-green-400',
    warning: 'text-amber-500 dark:text-amber-400',
    danger: 'text-gray-500 dark:text-gray-400'
  }
  return urgencyClasses[urgencyLevel.value]
})

// Meetings-specific styling (prioritize meetings over check-ins)
// Color logic uses only meetings, but a blackout check-in suppresses danger when no meetings exist.
const upcomingMeetingsOnlyCount = computed(() => {
  return props.institutions.reduce((total, inst) => total + (inst.upcoming_meetings_count || 0), 0)
})

const hasAnyBlackoutCheckIn = computed(() => {
  return props.institutions.some(inst => inst.active_check_in && isBlackout(inst.active_check_in.mode))
})

const meetingUrgencyLevel = computed(() => {
  if (upcomingMeetingsOnlyCount.value === 0) {
    return hasAnyBlackoutCheckIn.value ? 'success' : 'neutral'
  }
  if (upcomingMeetingsOnlyCount.value >= 3) return 'success'
  return 'warning'
})

// Helpers: map API modes to UI categories (normalize backend variants)
type CheckInMode = 'heads_up' | 'no_meetings' | undefined
const normalizeMode = (mode: unknown): CheckInMode => {
  if (mode == null) return undefined
  const s = String(mode).toLowerCase().trim().replace(/[\s-]+/g, '_')
  if (s === 'heads_up' || s === 'headsup') return 'heads_up'
  if (s === 'no_meetings' || s === 'no_meeting' || s === 'nomeetings') return 'no_meetings'
  if (s === 'blackout' || s === 'no_availability' || s === 'unavailable') return 'no_meetings'
  return undefined
}
const isHeadsUp = (mode?: unknown) => normalizeMode(mode) === 'heads_up'
const isBlackout = (mode?: unknown) => normalizeMode(mode) === 'no_meetings'

// Helpers: last meeting date and days since
const lastMeetingDate = (inst: AtstovavimosInstitution): Date | undefined => {
  if (!Array.isArray(inst.meetings)) return undefined
  const now = new Date()
  const past = inst.meetings
    .map(m => new Date(m.start_time))
    .filter(d => d <= now)
    .sort((a, b) => b.getTime() - a.getTime())
  return past[0]
}

const daysSinceLast = (inst: AtstovavimosInstitution): number | undefined => {
  if (typeof inst.days_since_last_meeting === 'number') return inst.days_since_last_meeting
  const last = lastMeetingDate(inst)
  if (!last) return undefined
  const diffMs = Date.now() - last.getTime()
  return Math.floor(diffMs / (1000 * 60 * 60 * 24))
}

const statusDotClass = (inst: AtstovavimosInstitution) => {
  const hasUpcoming = (inst.upcoming_meetings_count || 0) > 0
  if (hasUpcoming || (inst.active_check_in && isBlackout(inst.active_check_in.mode))) {
    return 'bg-emerald-400'
  }
  if (inst.active_check_in && isHeadsUp(inst.active_check_in.mode)) {
    return 'bg-sky-400'
  }
  // Check if old meeting (60+ days)
  if (daysSinceLast(inst) !== undefined && daysSinceLast(inst)! > 60) {
    return 'bg-amber-400'
  }
  // No meetings at all
  if (!lastMeetingDate(inst)) {
    return 'bg-rose-400'
  }
  // Recent meeting, no upcoming
  return 'bg-slate-400'
}

const meetingSummaryBorderClasses = computed(() => {
  const urgencyClasses: Record<string, string> = {
    success: 'border-green-200 dark:border-green-700',
    warning: 'border-amber-200 dark:border-amber-700',
    danger: 'border-gray-200 dark:border-gray-700',
    neutral: 'border-gray-200 dark:border-gray-700',
  }
  return urgencyClasses[meetingUrgencyLevel.value]
})

const meetingSummaryTextClasses = computed(() => {
  const urgencyClasses: Record<string, string> = {
    success: 'text-green-700 dark:text-green-300',
    warning: 'text-amber-700 dark:text-amber-300',
    danger: 'text-gray-700 dark:text-gray-300',
    neutral: 'text-gray-700 dark:text-gray-300',
  }
  return urgencyClasses[meetingUrgencyLevel.value]
})

const meetingSummarySubtextClasses = computed(() => {
  const urgencyClasses: Record<string, string> = {
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-amber-600 dark:text-amber-400',
    danger: 'text-gray-600 dark:text-gray-400',
    neutral: 'text-gray-600 dark:text-gray-400',
  }
  return urgencyClasses[meetingUrgencyLevel.value]
})

// Action handlers with loading states
// Row shading and segmented counts
const hasUpcomingMeeting = (inst: AtstovavimosInstitution) => {
  if (typeof inst.upcoming_meetings_count === 'number') return inst.upcoming_meetings_count > 0
  if (!Array.isArray(inst.meetings)) return false
  const now = new Date()
  return inst.meetings.some(m => new Date(m.start_time) > now)
}

const rowShadeClass = (inst: AtstovavimosInstitution) => {
  const hasUpcoming = hasUpcomingMeeting(inst)
  const blackout = !!(inst.active_check_in && isBlackout(inst.active_check_in.mode))
  const headsUp = !!(inst.active_check_in && isHeadsUp(inst.active_check_in.mode))
  if (hasUpcoming || blackout) return 'border-emerald-200 dark:border-emerald-700'
  if (headsUp) return 'border-sky-200 dark:border-sky-700'
  return 'border-gray-300 dark:border-gray-600'
}

const segCounts = computed(() => {
  let green = 0, blue = 0, red = 0
  for (const inst of props.institutions) {
    const hasUpcoming = hasUpcomingMeeting(inst)
    const blackout = !!(inst.active_check_in && isBlackout(inst.active_check_in.mode))
    const headsUp = !!(inst.active_check_in && isHeadsUp(inst.active_check_in.mode))
    if (hasUpcoming || blackout) green++
    else if (headsUp) blue++
    else red++
  }
  return { green, blue, red }
})

// Action handlers with loading states
const setLoading = (institutionId: string, loading: boolean) => {
  actionLoading[institutionId] = loading
}

const handleConfirm = async (checkInId: string) => {
  const institution = props.institutions.find(inst => inst.active_check_in?.id === checkInId)
  if (institution) setLoading(institution.id, true)

  router.post(route('check-ins.confirm', checkInId), {}, {
    preserveScroll: true,
    only: ['user'],
    onFinish: () => {
      if (institution) setLoading(institution.id, false)
    }
  })
}

const handleWithdraw = async (checkInId: string) => {
  const institution = props.institutions.find(inst => inst.active_check_in?.id === checkInId)
  if (institution) setLoading(institution.id, true)

  router.post(route('check-ins.withdraw', checkInId), {}, {
    preserveScroll: true,
    only: ['user'],
    onFinish: () => {
      if (institution) setLoading(institution.id, false)
    }
  })
}

const handleDispute = async (checkInId: string) => {
  const reason = window.prompt($t('Įveskite priežastį') as unknown as string)
  if (reason === null) return

  const institution = props.institutions.find(inst => inst.active_check_in?.id === checkInId)
  if (institution) setLoading(institution.id, true)

  router.post(route('check-ins.dispute', checkInId), { reason }, {
    preserveScroll: true,
    only: ['user'],
    onFinish: () => {
      if (institution) setLoading(institution.id, false)
    }
  })
}

const handleResolve = async ({ id, resolution }: { id: string, resolution: 'keep' | 'withdraw' }) => {
  const institution = props.institutions.find(inst => inst.active_check_in?.id === id)
  if (institution) setLoading(institution.id, true)

  router.post(route('check-ins.resolve', id), { resolution }, {
    preserveScroll: true,
    only: ['user'],
    onFinish: () => {
      if (institution) setLoading(institution.id, false)
    }
  })
}

const handleSuppress = async (checkInId: string) => {
  const reason = window.prompt($t('Įveskite priežastį') as unknown as string)
  if (!reason) return

  const institution = props.institutions.find(inst => inst.active_check_in?.id === checkInId)
  if (institution) setLoading(institution.id, true)

  router.post(route('check-ins.suppress', checkInId), { reason }, {
    preserveScroll: true,
    only: ['user'],
    onFinish: () => {
      if (institution) setLoading(institution.id, false)
    }
  })
}

const handleUnsuppress = async (checkInId: string) => {
  const institution = props.institutions.find(inst => inst.active_check_in?.id === checkInId)
  if (institution) setLoading(institution.id, true)

  router.post(route('check-ins.unsuppress', checkInId), {}, {
    preserveScroll: true,
    only: ['user'],
    onFinish: () => {
      if (institution) setLoading(institution.id, false)
    }
  })
}

const handleAddCheckIn = (institutionId: string) => {
  showCreateCheckIn.value = institutionId
}

const handleAddCheckInForPriority = () => {
  const priorityInstitution = institutionsNeedingAttention.value[0]
  if (priorityInstitution) {
    showCreateCheckIn.value = priorityInstitution.id
  }
}

const handleCheckInCreated = () => {
  showCreateCheckIn.value = null
  // Refresh data using only parameter to avoid full page reload
  router.reload({ only: ['user'] })
}
</script>
