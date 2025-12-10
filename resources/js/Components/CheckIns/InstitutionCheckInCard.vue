<template>
  <Card data-tour="institution-card" class="flex flex-col relative overflow-hidden border-zinc-200 dark:border-zinc-600 bg-gradient-to-br from-white to-zinc-50 dark:from-zinc-900 dark:to-zinc-950 shadow-sm dark:shadow-zinc-950/50" role="region" :aria-label="$t('Tavo institucijos')">
    <!-- Status indicator corner -->
    <div data-tour="institution-status" :class="statusIndicatorClasses" aria-hidden="true" />

    <CardHeader class="pb-3 relative z-10">
      <CardTitle class="flex items-center gap-2.5">
        <component :is="Icons.INSTITUTION" :class="iconClasses" aria-hidden="true" />
        <span class="font-semibold">{{ $t('Tavo institucijos') }}</span>
        <span v-if="limitedInstitutions.length < institutions.length"
          class="text-xs px-2 py-1 rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 ml-auto font-medium">
          {{ limitedInstitutions.length }}/{{ institutions.length }}
        </span>
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 space-y-4 pt-2">
      <!-- Institution List - improved spacing -->
      <div class="space-y-2">
        <InstitutionCompactCard v-for="inst in limitedInstitutions" :key="inst.id" :institution="inst"
          :show-actions="true" :can-schedule-meeting="true" :can-add-check-in="true"
          @schedule-meeting="$emit('schedule-meeting', $event)" @add-check-in="handleAddCheckIn" @remove-active-check-in="handleRemoveActiveCheckIn" />
      </div>


    </CardContent>

    <CardFooter class="border-t border-zinc-200 dark:border-zinc-600 bg-zinc-50/60 dark:bg-zinc-800/60 p-4 relative z-10">
      <div class="flex gap-3 w-full">
        <Button data-tour="all-institutions" size="sm" variant="outline" class="flex-1 font-medium" @click="$emit('show-all-modal')">
          <component :is="Icons.INSTITUTION" class="h-3.5 w-3.5 mr-2" />
          {{ $t('Visos institucijos') }}
        </Button>
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <Button data-tour="create-meeting" size="sm" variant="default" class="w-11 h-9" @click="$emit('create-meeting')">
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
import InstitutionCompactCard from '@/Components/Institutions/InstitutionCompactCard.vue'

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip'
import Icons from '@/Types/Icons/filled'
import { formatStaticTime } from '@/Utils/IntlTime'
import { 
  dashboardCardClasses, 
  dashboardCardFooterClasses,
  useDashboardCardStyles,
  urgencyPalette,
  type UrgencyLevel 
} from '@/Composables/useDashboardCardStyles'

const props = defineProps<{
  institutions: unknown[]
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
// Coverage means: has an upcoming meeting OR an active check-in
const institutionsWithCheckInsOrMeetings = computed(() => {
  return props.institutions.filter(inst =>
    inst.active_check_in ||
    (Array.isArray(inst.meetings) && inst.meetings.some((meeting: any) => new Date(meeting.start_time) > new Date()))
  )
})

const institutionsNeedingAttention = computed(() => {
  return props.institutions.filter(inst => {
    const hasCoverageCheckIn = !!inst.active_check_in
    const hasUpcomingMeeting = Array.isArray(inst.meetings) && inst.meetings.some((meeting: any) => new Date(meeting.start_time) > new Date())
    return !hasCoverageCheckIn && !hasUpcomingMeeting
  })
})

const coveredInstitutionsCount = computed(() => institutionsWithCheckInsOrMeetings.value.length)

// Calculate total upcoming items: upcoming meetings + active check-ins
const upcomingMeetingsCount = computed(() => {
  return props.institutions.reduce((total, inst) => {
    const meetingCount = inst.upcoming_meetings_count || 0
    const checkIn = inst.active_check_in ? 1 : 0
    return total + meetingCount + checkIn
  }, 0)
})

// Sort institutions by priority - attention needed institutions first
const sortedInstitutions = computed(() => {
  return [...props.institutions].sort((a, b) => {
    const aHasCoverage = !!a.active_check_in
    const bHasCoverage = !!b.active_check_in
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
      const aDays = Math.ceil((new Date(a.active_check_in.end_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
      const bDays = Math.ceil((new Date(b.active_check_in.end_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
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
const urgencyLevel = computed((): UrgencyLevel => {
  const needingAttentionCount = institutionsNeedingAttention.value.length
  const totalCount = props.institutions.length

  if (needingAttentionCount === 0) return 'success'
  if (needingAttentionCount / totalCount > 0.5) return 'danger'
  return 'warning'
})

// Use the composable for consistent styling
const { 
  statusIndicatorClasses, 
  iconClasses,
  borderClasses: summaryBorderClasses,
  textClasses: summaryTextClasses,
  subtextClasses: summarySubtextClasses,
  backgroundClasses: progressBackgroundClasses,
  foregroundClasses: progressBarClasses
} = useDashboardCardStyles(urgencyLevel)

// Additional insight text classes (slightly different shade)
const insightTextClasses = computed(() => {
  const classes: Record<UrgencyLevel, string> = {
    success: 'text-emerald-500 dark:text-emerald-500/70',
    warning: 'text-amber-500 dark:text-amber-500/70',
    danger: 'text-zinc-500 dark:text-zinc-400',
    neutral: 'text-zinc-500 dark:text-zinc-400'
  }
  return classes[urgencyLevel.value]
})

// Meetings-specific styling (prioritize meetings over check-ins)
// Color logic uses only meetings, but a blackout check-in suppresses danger when no meetings exist.
const upcomingMeetingsOnlyCount = computed(() => {
  return props.institutions.reduce((total, inst) => total + (inst.upcoming_meetings_count || 0), 0)
})

const hasAnyCheckIn = computed(() => {
  return props.institutions.some(inst => inst.active_check_in)
})

const meetingUrgencyLevel = computed((): UrgencyLevel => {
  if (upcomingMeetingsOnlyCount.value === 0) {
    return hasAnyCheckIn.value ? 'success' : 'neutral'
  }
  if (upcomingMeetingsOnlyCount.value >= 3) return 'success'
  return 'warning'
})

// Helper: check if institution has active check-in
const hasCheckIn = (inst: any): boolean => !!inst.active_check_in

// Use palette for meeting-specific styling
const meetingSummaryBorderClasses = computed(() => urgencyPalette.border[meetingUrgencyLevel.value])
const meetingSummaryTextClasses = computed(() => urgencyPalette.text[meetingUrgencyLevel.value])
const meetingSummarySubtextClasses = computed(() => urgencyPalette.subtext[meetingUrgencyLevel.value])

// Action handlers with loading states
// Row shading and segmented counts
const hasUpcomingMeeting = (inst: any) => {
  if (typeof inst.upcoming_meetings_count === 'number') return inst.upcoming_meetings_count > 0
  if (!Array.isArray(inst.meetings)) return false
  const now = new Date()
  return inst.meetings.some((m: any) => new Date(m.start_time) > now)
}

// Action handlers with loading states
const setLoading = (institutionId: string, loading: boolean) => {
  actionLoading[institutionId] = loading
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
  // Refresh data to update UI with new check-in
  router.reload({ only: ['user', 'accessibleInstitutions'] })
}

const handleRemoveActiveCheckIn = (institutionId: string) => {
  setLoading(institutionId, true)
  router.delete(route('institutions.check-ins.destroyActive', institutionId), {
    onFinish: () => setLoading(institutionId, false),
    onSuccess: () => {
      // Refresh data to update UI after check-in deletion
      router.reload({ only: ['user', 'accessibleInstitutions'] })
    }
  })
}
</script>
