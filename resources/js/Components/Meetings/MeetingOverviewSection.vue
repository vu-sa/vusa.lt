<template>
  <div class="space-y-4">
    <!-- Priority Alert for missing documents -->
    <PriorityAlert
      v-if="showDocumentAlert"
      v-model="showDocumentAlertState"
      variant="warning"
      :title="$t('Trūksta dokumentų')"
      :description="documentAlertDescription"
      :action-label="$t('Įkelti dokumentus')"
      @action="$emit('go-to-files')"
    />

    <!-- Stats Row - Compact -->
    <StatsRow :columns="4">
      <StatCard
        :label="$t('Darbotvarkės punktai')"
        :value="totalAgendaItems"
        :icon="ClipboardList"
        :urgency="agendaUrgency"
        :subtitle="agendaSubtitle"
        :on-click="() => $emit('go-to-agenda')"
      />

      <StatCard
        :label="$t('Užduotys')"
        :value="pendingTasksCount"
        :icon="CheckSquare"
        :urgency="taskUrgency"
        :subtitle="taskSubtitle"
        :on-click="() => $emit('go-to-tasks')"
      />

      <StatCard
        :label="$t('Failai')"
        :value="filesCount"
        :icon="FileIcon"
        urgency="neutral"
        :subtitle="filesSubtitle"
        :on-click="() => $emit('go-to-files')"
      />

      <StatCard
        :label="$t('Protokolas')"
        :value="hasProtocol ? $t('Įkeltas') : $t('Nėra')"
        :icon="FileCheck"
        :urgency="documentUrgency"
        :subtitle="hasReport ? $t('Ataskaita įkelta') : $t('Ataskaita neįkelta')"
        :on-click="() => $emit('go-to-files')"
      />
    </StatsRow>

    <!-- Compact Progress + Actions Row -->
    <Card class="overflow-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4">
        <!-- Progress Section -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-3">
            <ClipboardList class="h-5 w-5 text-primary shrink-0" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between text-sm mb-1.5">
                <span class="text-zinc-600 dark:text-zinc-400">{{ $t('Darbotvarkės užbaigimas') }}</span>
                <span class="font-medium tabular-nums">{{ completedAgendaItems }} / {{ totalAgendaItems }}</span>
              </div>
              <div class="relative h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div
                  :class="['h-full rounded-full transition-all duration-500 ease-out', progressBarColorClass]"
                  :style="{ width: `${agendaCompletion}%` }"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="hidden sm:block w-px h-8 bg-zinc-200 dark:bg-zinc-700" />

        <!-- Quick Actions -->
        <div class="flex items-center gap-2 shrink-0">
          <Button variant="outline" size="sm" class="gap-1.5" @click="$emit('go-to-agenda')">
            <ListTodo class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('Darbotvarkė') }}</span>
          </Button>
          <Button variant="outline" size="sm" class="gap-1.5" @click="$emit('edit')">
            <Edit class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('Redaguoti') }}</span>
          </Button>
        </div>
      </div>
    </Card>

    <!-- Document Status - Compact Inline -->
    <Card v-if="!hasProtocol || !hasReport" class="overflow-hidden">
      <div class="flex flex-wrap items-center gap-3 p-4">
        <FileCheck class="h-5 w-5 text-primary shrink-0" />
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $t('Dokumentų būsena') }}</span>

        <div class="flex flex-wrap items-center gap-2 ml-auto">
          <!-- Protocol Status -->
          <div
            :class="[
              'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
              hasProtocol
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            ]"
          >
            <component :is="hasProtocol ? CheckCircle2 : AlertCircle" class="h-3.5 w-3.5" />
            {{ $t('Protokolas') }}: {{ hasProtocol ? $t('Įkeltas') : $t('Nėra') }}
          </div>

          <!-- Report Status -->
          <div
            :class="[
              'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
              hasReport
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            ]"
          >
            <component :is="hasReport ? CheckCircle2 : AlertCircle" class="h-3.5 w-3.5" />
            {{ $t('Ataskaita') }}: {{ hasReport ? $t('Įkelta') : $t('Nėra') }}
          </div>

          <Button variant="outline" size="sm" class="gap-1.5 ml-2" @click="$emit('go-to-files')">
            <Upload class="h-3.5 w-3.5" />
            {{ $t('Įkelti') }}
          </Button>
        </div>
      </div>
    </Card>

    <!-- Representatives - Compact Inline -->
    <Card v-if="representatives && representatives.length > 0" class="overflow-hidden">
      <div class="flex flex-wrap items-center gap-3 p-4">
        <Users class="h-5 w-5 text-primary shrink-0" />
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $t('Studentų atstovai') }}</span>

        <div class="flex flex-wrap items-center gap-2 ml-2">
          <div
            v-for="rep in representatives"
            :key="rep.id"
            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800"
          >
            <img
              v-if="rep.profile_photo_path"
              :src="rep.profile_photo_path"
              :alt="rep.name"
              class="w-5 h-5 rounded-full object-cover"
            />
            <div
              v-else
              class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center text-[10px] font-medium text-primary"
            >
              {{ rep.name?.charAt(0) }}
            </div>
            <span class="text-sm">{{ rep.name }}</span>
          </div>
        </div>
      </div>
    </Card>

    <!-- Meeting Navigation - Horizontal Compact -->
    <MeetingNavigationCards v-if="previousMeeting || nextMeeting" :previous-meeting :next-meeting />

    <!-- Recent Activity - Compact -->
    <Card v-if="activities && activities.length > 0" class="overflow-hidden">
      <div class="p-4">
        <div class="flex items-center gap-2 mb-3">
          <Activity class="h-5 w-5 text-primary" />
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $t('Paskutinė veikla') }}</span>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-2">
          <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center gap-2 text-sm">
            <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="getActivityColor(activity)" />
            <span class="text-zinc-600 dark:text-zinc-400">{{ activity.description }}</span>
            <span class="text-xs text-zinc-400 dark:text-zinc-500">
              {{ formatRelativeTime(new Date(activity.created_at)) }}
            </span>
          </div>
        </div>
      </div>
    </Card>

    <!-- Description - Compact -->
    <Card v-if="meeting.description" class="overflow-hidden">
      <div class="p-4">
        <div class="flex items-start gap-3">
          <FileText class="h-5 w-5 text-primary shrink-0 mt-0.5" />
          <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">
            {{ meeting.description }}
          </p>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Activity,
  ClipboardList,
  FileText,
  FileCheck,
  CheckCircle2,
  AlertCircle,
  Upload,
  ListTodo,
  CheckSquare,
  Edit,
  Users,
  File as FileIcon,
} from 'lucide-vue-next'

import { formatRelativeTime } from '@/Utils/IntlTime'
import StatCard from '@/Components/Cards/StatCard.vue'
import StatsRow from '@/Components/Cards/StatsRow.vue'
import PriorityAlert from '@/Components/Alerts/PriorityAlert.vue'
import MeetingNavigationCards from './MeetingNavigationCards.vue'
import { Card } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'

import { useMeetingUrgency } from '@/Composables/useMeetingUrgency'

interface Activity {
  id: string | number
  description?: string | null
  created_at: string
}

interface MeetingNav {
  id: string
  start_time: string
}

const props = defineProps<{
  meeting: App.Entities.Meeting
  representatives?: App.Entities.User[]
  activities?: Activity[]
  previousMeeting?: MeetingNav | null
  nextMeeting?: MeetingNav | null
}>()

defineEmits<{
  'go-to-agenda': []
  'go-to-files': []
  'go-to-tasks': []
  edit: []
}>()

// Use urgency composable
const {
  agendaUrgency,
  documentUrgency,
  taskUrgency,
  isPastMeeting,
  agendaCompletion,
  completedAgendaItems,
  totalAgendaItems,
  pendingTasksCount,
  totalTasksCount,
  hasProtocol,
  hasReport,
  filesCount,
} = useMeetingUrgency(() => props.meeting)

// Alert state
const showDocumentAlertState = ref(true)

// Computed displays
const showDocumentAlert = computed(() => {
  return isPastMeeting.value && (!hasProtocol.value || !hasReport.value) && showDocumentAlertState.value
})

const documentAlertDescription = computed(() => {
  if (!hasProtocol.value && !hasReport.value) {
    return $t('Posėdis įvyko, bet protokolas ir ataskaita dar neįkelti.')
  }
  if (!hasProtocol.value) {
    return $t('Posėdis įvyko, bet protokolas dar neįkeltas.')
  }
  return $t('Posėdis įvyko, bet ataskaita dar neįkelta.')
})

const agendaSubtitle = computed(() => {
  if (totalAgendaItems.value === 0) return $t('Nėra punktų')
  const rate = agendaCompletion.value
  if (rate >= 100) return $t('Visi punktai aptarti')
  return `${completedAgendaItems.value} ${$t('aptarta')}`
})

const taskSubtitle = computed(() => {
  if (totalTasksCount.value === 0) return $t('Nėra užduočių')
  if (pendingTasksCount.value === 0) return $t('Visos atliktos')
  return `${$t('iš')} ${totalTasksCount.value}`
})

const filesSubtitle = computed(() => {
  if (filesCount.value === 0) return $t('Nėra failų')
  return $t('įkelta')
})

const progressBarColorClass = computed(() => {
  const completion = agendaCompletion.value
  if (completion >= 100) return 'bg-emerald-500 dark:bg-emerald-400'
  if (completion >= 50) return 'bg-amber-500 dark:bg-amber-400'
  return 'bg-zinc-400 dark:bg-zinc-500'
})

const recentActivities = computed(() => {
  return (props.activities ?? []).slice(0, 5)
})

const getActivityColor = (activity: Activity) => {
  const description = activity.description?.toLowerCase() ?? ''
  if (description.includes('created') || description.includes('sukurt')) return 'bg-emerald-500'
  if (description.includes('updated') || description.includes('atnaujin')) return 'bg-blue-500'
  if (description.includes('deleted') || description.includes('pašalin')) return 'bg-red-500'
  return 'bg-zinc-400'
}
</script>
