<template>
  <Card>
    <CardHeader class="pb-3">
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2 text-base">
          <CalendarIcon class="h-5 w-5 text-primary" />
          {{ $t('Paskutiniai susitikimai') }}
        </CardTitle>
        <div class="flex items-center gap-2">
          <Badge v-if="totalCount > meetings.length" variant="secondary" class="text-xs">
            {{ totalCount }} {{ $t('iš viso') }}
          </Badge>
          <Button v-if="isOverdue" variant="default" size="sm" class="gap-1.5" @click="$emit('schedule-meeting')">
            <Plus class="h-3.5 w-3.5" />
            {{ $t('Naujas') }}
          </Button>
        </div>
      </div>
    </CardHeader>
    <CardContent class="space-y-3">
      <!-- Meeting cards -->
      <button
        v-for="meeting in meetings"
        :key="meeting.id"
        type="button"
        :class="[
          'w-full flex items-center gap-4 p-3 rounded-lg text-left',
          'bg-zinc-50 dark:bg-zinc-800/50',
          'hover:bg-zinc-100 dark:hover:bg-zinc-800',
          'transition-colors duration-200',
          'focus:outline-none focus:ring-2 focus:ring-primary/50 focus:ring-offset-2 dark:focus:ring-offset-zinc-900',
        ]"
        @click="$emit('view-meeting', meeting)"
      >
        <!-- Date badge -->
        <div
          :class="[
            'flex flex-col items-center justify-center',
            'w-14 h-14 rounded-lg shrink-0',
            'bg-white dark:bg-zinc-900',
            'border',
            getMeetingStatusBorderClass(meeting),
          ]"
        >
          <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">
            {{ formatMonth(meeting.start_time) }}
          </span>
          <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
            {{ formatDay(meeting.start_time) }}
          </span>
        </div>

        <!-- Meeting info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h4 class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
              {{ getMeetingTitle(meeting) }}
            </h4>
            <Badge
              v-if="isFutureMeeting(meeting)"
              variant="outline"
              class="text-xs shrink-0 text-blue-600 border-blue-300 dark:text-blue-400 dark:border-blue-700"
            >
              {{ $t('Būsimas') }}
            </Badge>
          </div>
          <div class="flex items-center gap-3 mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1">
              <Clock class="h-3.5 w-3.5" />
              {{ formatTime(meeting.start_time) }}
            </span>
            <span v-if="meeting.agenda_items?.length" class="flex items-center gap-1">
              <ListTodo class="h-3.5 w-3.5" />
              {{ meeting.agenda_items.length }} {{ $t('punktai') }}
            </span>
          </div>
        </div>

        <!-- Status indicator -->
        <div class="shrink-0">
          <component
            :is="getMeetingStatusIcon(meeting)"
            :class="['h-5 w-5', getMeetingStatusIconClass(meeting)]"
          />
        </div>
      </button>

      <!-- View all button -->
      <Button
        variant="outline"
        class="w-full"
        @click="$emit('view-all')"
      >
        {{ $t('Peržiūrėti visus susitikimus') }}
        <ChevronRight class="h-4 w-4 ml-2" />
      </Button>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n'
import {
  Calendar as CalendarIcon,
  Clock,
  ListTodo,
  Plus,
  ChevronRight,
  CheckCircle2,
  AlertCircle,
  Circle
} from 'lucide-vue-next'

import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { formatStaticTime } from '@/Utils/IntlTime'

const props = defineProps<{
  meetings: App.Entities.Meeting[]
  institution: App.Entities.Institution
  totalCount: number
  isOverdue?: boolean
}>()

const emit = defineEmits<{
  'view-all': []
  'schedule-meeting': []
  'view-meeting': [meeting: App.Entities.Meeting]
}>()

// Date formatting helpers
const formatMonth = (dateString: string) => {
  return formatStaticTime(new Date(dateString), { month: 'short' })
}

const formatDay = (dateString: string) => {
  return formatStaticTime(new Date(dateString), { day: 'numeric' })
}

const formatTime = (dateString: string) => {
  return formatStaticTime(new Date(dateString), { hour: '2-digit', minute: '2-digit' })
}

// Meeting status helpers
const isFutureMeeting = (meeting: App.Entities.Meeting) => {
  return new Date(meeting.start_time) > new Date()
}

const getMeetingTitle = (meeting: App.Entities.Meeting) => {
  if (meeting.title) return meeting.title
  return formatStaticTime(new Date(meeting.start_time), {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }) + ' ' + $t('posėdis')
}

const getMeetingStatusIcon = (meeting: App.Entities.Meeting) => {
  if (isFutureMeeting(meeting)) return Circle

  const hasProtocol = meeting.has_protocol
  const hasReport = meeting.has_report

  if (hasProtocol && hasReport) return CheckCircle2
  if (hasProtocol || hasReport) return AlertCircle
  return AlertCircle
}

const getMeetingStatusIconClass = (meeting: App.Entities.Meeting) => {
  if (isFutureMeeting(meeting)) return 'text-blue-500 dark:text-blue-400'

  const hasProtocol = meeting.has_protocol
  const hasReport = meeting.has_report

  if (hasProtocol && hasReport) return 'text-emerald-500 dark:text-emerald-400'
  if (hasProtocol || hasReport) return 'text-amber-500 dark:text-amber-400'
  return 'text-zinc-400 dark:text-zinc-500'
}

const getMeetingStatusBorderClass = (meeting: App.Entities.Meeting) => {
  if (isFutureMeeting(meeting)) return 'border-blue-200 dark:border-blue-800'

  const hasProtocol = meeting.has_protocol
  const hasReport = meeting.has_report

  if (hasProtocol && hasReport) return 'border-emerald-200 dark:border-emerald-800'
  if (hasProtocol || hasReport) return 'border-amber-200 dark:border-amber-800'
  return 'border-zinc-200 dark:border-zinc-700'
}
</script>
