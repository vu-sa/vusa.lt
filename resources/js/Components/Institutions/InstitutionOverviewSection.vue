<template>
  <div class="space-y-6">
    <!-- Priority Alert for overdue meetings -->
    <PriorityAlert
      v-if="isOverdue"
      v-model="showOverdueAlert"
      variant="warning"
      :title="$t('Susitikimas vėluoja')"
      :description="overdueAlertDescription"
      :action-label="$t('Suplanuoti susitikimą')"
      @action="$emit('schedule-meeting')"
    />

    <!-- Compact Stats Grid -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <button
        v-for="stat in stats"
        :key="stat.label"
        type="button"
        :class="[
          'rounded-lg border bg-card text-card-foreground shadow-sm',
          'border-border transition-colors hover:border-primary/20',
          'text-left focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2',
        ]"
        @click="stat.onClick"
      >
        <div class="flex items-start gap-3 p-4">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent">
            <component :is="stat.icon" class="h-4 w-4 text-accent-foreground" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
            <p class="text-lg font-bold leading-tight text-foreground">{{ stat.value }}</p>
            <p class="text-[11px] text-primary">{{ stat.subtitle }}</p>
          </div>
        </div>
      </button>
    </div>

    <!-- Main Content: Members (3/5) + Meetings (2/5) side-by-side on large screens -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
      <!-- Current Members - simple list -->
      <div class="lg:col-span-3">
        <Card>
          <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
              <CardTitle class="flex items-center gap-2 text-base">
                <Users class="h-5 w-5 text-primary" />
                {{ $t('Dabartiniai nariai') }}
                <span class="text-sm font-normal text-muted-foreground">
                  ({{ filledPositions }} / {{ totalPositions }})
                </span>
              </CardTitle>
              <Button
                v-if="canEditMembers"
                variant="outline"
                size="sm"
                class="gap-2"
                @click="$emit('add-member')"
              >
                <UserPlus class="h-4 w-4" />
                {{ $t('Pridėti') }}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div v-if="members.length > 0" class="space-y-1">
              <button
                v-for="member in members"
                :key="member.id"
                type="button"
                :class="[
                  'flex w-full items-center gap-3 rounded-md px-2 py-2 text-left',
                  'transition-colors hover:bg-accent/50',
                  'focus:outline-none focus:ring-2 focus:ring-primary/50',
                ]"
                @click="$emit('view-profile', member)"
              >
                <UserPopover :user="member" :size="32" />
                <span class="truncate text-sm font-medium text-foreground">{{ member.name }}</span>
              </button>
            </div>

            <!-- Empty State -->
            <div v-else class="py-8 text-center">
              <Users class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
              <p class="text-sm text-muted-foreground">{{ $t('Nėra narių') }}</p>
            </div>

            <!-- Capacity Warning -->
            <div
              v-if="showCapacityWarning"
              class="mt-3 flex items-center gap-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ $t('Viršytas narių limitas') }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Recent Meetings - simple list -->
      <div class="lg:col-span-2">
        <InstitutionMeetingsPreview
          v-if="institution.meetings && institution.meetings.length > 0"
          :meetings="recentMeetings"
          :institution
          :total-count="meetingsCount"
          :is-overdue
          @view-all="$emit('navigate-tab', 'meetings')"
          @schedule-meeting="$emit('schedule-meeting')"
          @view-meeting="(meeting) => $emit('view-meeting', meeting)"
        />

        <!-- Empty meetings state -->
        <Card v-else>
          <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-base">
              <CalendarIcon class="h-5 w-5 text-primary" />
              {{ $t('Paskutiniai susitikimai') }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="py-8 text-center">
              <CalendarIcon class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
              <p class="text-sm text-muted-foreground">{{ $t('Nėra susitikimų') }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Recent Activity -->
    <Card v-if="activities && activities.length > 0">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Activity class="h-5 w-5 text-primary" />
          {{ $t('Paskutinė veikla') }}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div
            v-for="activity in activities.slice(0, 5)"
            :key="activity.id"
            class="flex items-center gap-3 text-sm"
          >
            <div class="h-2 w-2 rounded-full bg-blue-500" />
            <span class="text-zinc-600 dark:text-zinc-400">{{ activity.description }}</span>
            <span class="ml-auto text-zinc-400 dark:text-zinc-500">
              {{ formatRelativeTime(activity.created_at) }}
            </span>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Users,
  UserPlus,
  Calendar as CalendarIcon,
  BarChart3,
  Repeat,
  Activity,
  AlertTriangle
} from 'lucide-vue-next'

import PriorityAlert from '@/Components/Alerts/PriorityAlert.vue'
import UserPopover from '@/Components/Avatars/UserPopover.vue'
import InstitutionMeetingsPreview from './InstitutionMeetingsPreview.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'

import { useInstitutionUrgency } from '@/Composables/useInstitutionUrgency'
import { formatRelativeTime } from '@/Utils/IntlTime'

interface Activity {
  id: string | number
  description?: string | null
  created_at: string
}

const props = defineProps<{
  institution: App.Entities.Institution
  activities?: Activity[]
  canEditMembers?: boolean
}>()

const emit = defineEmits<{
  'navigate-tab': [tab: string]
  'schedule-meeting': []
  'add-member': []
  'view-profile': [member: App.Entities.User]
  'edit-member': [member: App.Entities.User]
  'view-meeting': [meeting: App.Entities.Meeting]
}>()

// Use urgency composable
const {
  memberUrgency,
  meetingUrgency,
  isOverdue,
  daysSinceLastMeeting,
  memberFillRate,
  totalPositions,
  filledPositions,
  lastMeeting
} = useInstitutionUrgency(() => props.institution)

// Alert state
const showOverdueAlert = ref(true)

// Members
const members = computed(() => props.institution.current_users || [])

const showCapacityWarning = computed(() => {
  return totalPositions.value > 0 && members.value.length > totalPositions.value
})

// Computed displays
const meetingsCount = computed(() => props.institution.meetings?.length || 0)

const recentMeetings = computed(() => {
  const meetings = props.institution.meetings || []
  return [...meetings]
    .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
    .slice(0, 3)
})

const lastMeetingDisplay = computed(() => {
  if (!lastMeeting.value) return $t('Nėra')
  const date = new Date(lastMeeting.value.start_time)
  return date.toLocaleDateString('lt-LT', { month: 'short', day: 'numeric' })
})

const lastMeetingSubtitle = computed(() => {
  if (daysSinceLastMeeting.value === null) return $t('Nėra duomenų')
  if (daysSinceLastMeeting.value === 0) return $t('Šiandien')
  if (daysSinceLastMeeting.value === 1) return $t('Vakar')
  return `${$t('Prieš')} ${daysSinceLastMeeting.value} ${$t('d.')}`
})

const memberSubtitle = computed(() => {
  const rate = memberFillRate.value
  if (rate >= 100) return $t('Pilnai užpildyta')
  if (rate >= 80) return $t('Beveik pilna')
  if (rate >= 50) return $t('Dalinai užpildyta')
  return $t('Reikia daugiau narių')
})

const periodicityDisplay = computed(() => {
  const days = props.institution.meeting_periodicity_days ?? 30
  return `${days} ${$t('d.')}`
})

const periodicitySubtitle = computed(() => {
  if (daysSinceLastMeeting.value === null) return ''
  if (isOverdue.value) {
    return `${daysSinceLastMeeting.value} ${$t('d. nuo paskutinio')}`
  }
  return `${daysSinceLastMeeting.value} ${$t('d. nuo paskutinio')}`
})

const overdueAlertDescription = computed(() => {
  const periodicity = props.institution.meeting_periodicity_days ?? 30
  const overdueDays = (daysSinceLastMeeting.value ?? 0) - periodicity
  return $t('Praėjo :days d. nuo numatyto susitikimo periodiškumo. Rekomenduojame suplanuoti susitikimą.', {
    days: overdueDays
  })
})

// Stats data for the compact grid
const stats = computed(() => [
  {
    label: $t('Aktyvūs nariai'),
    value: `${filledPositions.value} / ${totalPositions.value}`,
    icon: Users,
    subtitle: memberSubtitle.value,
    onClick: () => emit('navigate-tab', 'duties'),
  },
  {
    label: $t('Paskutinis susitikimas'),
    value: lastMeetingDisplay.value,
    icon: CalendarIcon,
    subtitle: lastMeetingSubtitle.value,
    onClick: () => emit('navigate-tab', 'meetings'),
  },
  {
    label: $t('Susitikimų'),
    value: String(meetingsCount.value),
    icon: BarChart3,
    subtitle: $t('šiais metais'),
    onClick: () => emit('navigate-tab', 'meetings'),
  },
  {
    label: $t('Periodiškumas'),
    value: periodicityDisplay.value,
    icon: Repeat,
    subtitle: periodicitySubtitle.value,
    onClick: () => emit('navigate-tab', 'meetings'),
  },
])
</script>
