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

    <!-- Stats Row - Clickable cards navigating to tabs -->
    <StatsRow :columns="4">
      <StatCard
        :label="$t('Aktyvūs nariai')"
        :value="`${filledPositions} / ${totalPositions}`"
        :icon="Users"
        :urgency="memberUrgency"
        :subtitle="memberSubtitle"
        :on-click="() => $emit('navigate-tab', 'duties')"
      />

      <StatCard
        :label="$t('Paskutinis susitikimas')"
        :value="lastMeetingDisplay"
        :icon="CalendarIcon"
        :urgency="meetingUrgency"
        :subtitle="lastMeetingSubtitle"
        :on-click="() => $emit('navigate-tab', 'meetings')"
      />

      <StatCard
        :label="$t('Susitikimų')"
        :value="meetingsCount"
        :icon="BarChart3"
        urgency="neutral"
        :subtitle="$t('šiais metais')"
        :on-click="() => $emit('navigate-tab', 'meetings')"
      />

      <StatCard
        :label="$t('Periodiškumas')"
        :value="periodicityDisplay"
        :icon="Repeat"
        :urgency="meetingUrgency"
        :subtitle="periodicitySubtitle"
        :on-click="() => $emit('navigate-tab', 'meetings')"
      />
    </StatsRow>

    <!-- Main Content - Full Width (no sidebar) -->
    <div class="space-y-6">
      <!-- Current Members Grid -->
      <MemberGrid
        :title="$t('Dabartiniai nariai')"
        :subtitle="`${filledPositions} ${$t('aktyvūs nariai')}`"
        :members="institution.current_users || []"
        :institution
        :max-positions="totalPositions"
        :show-contact="true"
        :show-actions="true"
        :can-edit="canEditMembers"
        :can-add-member="canEditMembers"
        @add-member="$emit('add-member')"
        @view-profile="(member) => $emit('view-profile', member)"
        @edit-member="(member) => $emit('edit-member', member)"
      />

      <!-- Meetings Preview (last 3) -->
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
              <div class="w-2 h-2 rounded-full bg-blue-500" />
              <span class="text-zinc-600 dark:text-zinc-400">{{ activity.description }}</span>
              <span class="text-zinc-400 dark:text-zinc-500 ml-auto">
                {{ formatRelativeTime(activity.created_at) }}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Users,
  Calendar as CalendarIcon,
  BarChart3,
  Repeat,
  Activity
} from 'lucide-vue-next'

import StatCard from '@/Components/Cards/StatCard.vue'
import StatsRow from '@/Components/Cards/StatsRow.vue'
import PriorityAlert from '@/Components/Alerts/PriorityAlert.vue'
import MemberGrid from '@/Components/Members/MemberGrid.vue'
import InstitutionMeetingsPreview from './InstitutionMeetingsPreview.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'

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
  return `${$t('Kas')} ${days} ${$t('d.')}`
})

const periodicitySubtitle = computed(() => {
  if (daysSinceLastMeeting.value === null) return ''
  if (isOverdue.value) {
    return `${daysSinceLastMeeting.value} ${$t('d. nuo paskutinio')} (${$t('vėluoja')})`
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
</script>
