<template>
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="xl:col-span-2 space-y-6">
      <!-- Progress Summary Card -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <ClipboardList class="h-5 w-5 text-primary" />
            {{ $t('Posėdžio eiga') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Progress Bar -->
          <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="text-zinc-600 dark:text-zinc-400">{{ $t('Darbotvarkės užbaigimas') }}</span>
              <span class="font-medium">{{ completedItemsCount }} / {{ agendaItemsCount }}</span>
            </div>
            <Progress :model-value="progressPercentage" class="h-2" />
          </div>

          <!-- Quick Stats Grid -->
          <div class="grid grid-cols-3 gap-4 pt-2">
            <button 
              class="text-center p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
              @click="$emit('goToAgenda')"
            >
              <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ agendaItemsCount }}</div>
              <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Punktai') }}</div>
            </button>
            <button 
              class="text-center p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
              @click="$emit('goToTasks')"
            >
              <div class="text-2xl font-bold" :class="pendingTasksCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                {{ pendingTasksCount }}
              </div>
              <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Laukia užduočių') }}</div>
            </button>
            <button 
              class="text-center p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
              @click="$emit('goToFiles')"
            >
              <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ filesCount }}</div>
              <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Failai') }}</div>
            </button>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Activity -->
      <Card v-if="activities && activities.length > 0">
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <Activity class="h-5 w-5 text-primary" />
            {{ $t('Paskutinė veikla') }}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start gap-3 text-sm">
              <div class="mt-1.5 w-2 h-2 rounded-full shrink-0" :class="getActivityColor(activity)" />
              <div class="flex-1 min-w-0">
                <span class="text-zinc-700 dark:text-zinc-300">{{ activity.description }}</span>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                  {{ formatRelativeTime(new Date(activity.created_at)) }}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Description -->
      <Card v-if="meeting.description">
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <FileText class="h-5 w-5 text-primary" />
            {{ $t('Aprašymas') }}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{{ meeting.description }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Sidebar -->
    <div class="xl:sticky xl:top-6 xl:self-start space-y-6">
      <!-- Document Checklist -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <FileCheck class="h-5 w-5 text-primary" />
            {{ $t('Dokumentų būsena') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="flex items-center justify-between p-3 rounded-lg" :class="meeting.has_protocol ? 'bg-green-50 dark:bg-green-900/20' : 'bg-amber-50 dark:bg-amber-900/20'">
            <div class="flex items-center gap-2">
              <component :is="meeting.has_protocol ? CheckCircle2 : AlertCircle" 
                class="h-5 w-5" 
                :class="meeting.has_protocol ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'" 
              />
              <span class="text-sm font-medium" :class="meeting.has_protocol ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300'">
                {{ $t('Protokolas') }}
              </span>
            </div>
            <Badge :variant="meeting.has_protocol ? 'default' : 'secondary'" class="text-xs">
              {{ meeting.has_protocol ? $t('Įkeltas') : $t('Nėra') }}
            </Badge>
          </div>

          <div class="flex items-center justify-between p-3 rounded-lg" :class="meeting.has_report ? 'bg-green-50 dark:bg-green-900/20' : 'bg-amber-50 dark:bg-amber-900/20'">
            <div class="flex items-center gap-2">
              <component :is="meeting.has_report ? CheckCircle2 : AlertCircle" 
                class="h-5 w-5" 
                :class="meeting.has_report ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'" 
              />
              <span class="text-sm font-medium" :class="meeting.has_report ? 'text-green-700 dark:text-green-300' : 'text-amber-700 dark:text-amber-300'">
                {{ $t('Ataskaita') }}
              </span>
            </div>
            <Badge :variant="meeting.has_report ? 'default' : 'secondary'" class="text-xs">
              {{ meeting.has_report ? $t('Įkelta') : $t('Nėra') }}
            </Badge>
          </div>

          <Button v-if="!meeting.has_protocol || !meeting.has_report" variant="outline" size="sm" class="w-full mt-2" @click="$emit('goToFiles')">
            <Upload class="h-4 w-4 mr-2" />
            {{ $t('Įkelti dokumentus') }}
          </Button>
        </CardContent>
      </Card>

      <!-- Quick Actions -->
      <Card>
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <Zap class="h-5 w-5 text-primary" />
            {{ $t('Greiti veiksmai') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <Button variant="outline" size="sm" class="w-full justify-start" @click="$emit('goToAgenda')">
            <ListTodo class="h-4 w-4 mr-2" />
            {{ $t('Peržiūrėti darbotvarkę') }}
          </Button>
          <Button variant="outline" size="sm" class="w-full justify-start" @click="$emit('goToTasks')">
            <CheckSquare class="h-4 w-4 mr-2" />
            {{ $t('Tvarkyti užduotis') }}
            <Badge v-if="pendingTasksCount > 0" variant="secondary" class="ml-auto text-xs">
              {{ pendingTasksCount }}
            </Badge>
          </Button>
          <Button variant="outline" size="sm" class="w-full justify-start" @click="$emit('edit')">
            <Edit class="h-4 w-4 mr-2" />
            {{ $t('Redaguoti posėdį') }}
          </Button>
        </CardContent>
      </Card>

      <!-- Representatives -->
      <Card v-if="representatives && representatives.length > 0">
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <Users class="h-5 w-5 text-primary" />
            {{ $t('Studentų atstovai') }}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <div v-for="rep in representatives" :key="rep.id" class="flex items-center gap-2 px-2 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800">
              <img v-if="rep.profile_photo_path" :src="rep.profile_photo_path" :alt="rep.name" class="w-6 h-6 rounded-full object-cover" />
              <div v-else class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-medium text-primary">
                {{ rep.name?.charAt(0) }}
              </div>
              <span class="text-sm">{{ rep.name }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Meeting Navigation -->
      <Card v-if="previousMeeting || nextMeeting">
        <CardHeader class="pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <Navigation class="h-5 w-5 text-primary" />
            {{ $t('Kiti posėdžiai') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <Link
            v-if="previousMeeting"
            :href="route('meetings.show', previousMeeting.id)"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
          >
            <ChevronLeft class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300" />
            <div class="flex-1 min-w-0">
              <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Ankstesnis') }}</span>
              <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">
                {{ formatNavDate(previousMeeting.start_time) }}
              </p>
            </div>
          </Link>
          <Link
            v-if="nextMeeting"
            :href="route('meetings.show', nextMeeting.id)"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group"
          >
            <div class="flex-1 min-w-0">
              <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Kitas') }}</span>
              <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">
                {{ formatNavDate(nextMeeting.start_time) }}
              </p>
            </div>
            <ChevronRight class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300" />
          </Link>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Activity,
  ClipboardList,
  FileText,
  FileCheck,
  CheckCircle2,
  AlertCircle,
  Upload,
  Zap,
  ListTodo,
  CheckSquare,
  Edit,
  Users,
  Navigation,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'

import { formatRelativeTime, formatStaticTime } from '@/Utils/IntlTime'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Progress } from '@/Components/ui/progress'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'

interface Activity {
  id: string | number
  description?: string | null
  created_at: string
}

interface MeetingNav {
  id: string
  start_time: string
}

interface Props {
  meeting: App.Entities.Meeting
  representatives?: App.Entities.User[]
  activities?: Activity[]
  previousMeeting?: MeetingNav | null
  nextMeeting?: MeetingNav | null
}

const props = defineProps<Props>()

defineEmits<{
  (e: 'goToAgenda'): void
  (e: 'goToFiles'): void
  (e: 'goToTasks'): void
  (e: 'edit'): void
}>()

const agendaItemsCount = computed(() => props.meeting.agenda_items?.length ?? 0)

const completedItemsCount = computed(() => {
  return props.meeting.agenda_items?.filter(item =>
    item.decision === 'positive' ||
    item.decision === 'negative' ||
    item.decision === 'neutral'
  ).length ?? 0
})

const progressPercentage = computed(() => {
  if (agendaItemsCount.value === 0) return 0
  return (completedItemsCount.value / agendaItemsCount.value) * 100
})

const pendingTasksCount = computed(() => {
  return props.meeting.tasks?.filter(task => !task.completed_at).length ?? 0
})

const filesCount = computed(() => props.meeting.files?.length ?? 0)

const recentActivities = computed(() => {
  return (props.activities ?? []).slice(0, 5)
})

const getActivityColor = (activity: Activity) => {
  const description = activity.description?.toLowerCase() ?? ''
  if (description.includes('created') || description.includes('sukurt')) return 'bg-green-500'
  if (description.includes('updated') || description.includes('atnaujin')) return 'bg-blue-500'
  if (description.includes('deleted') || description.includes('pašalin')) return 'bg-red-500'
  return 'bg-zinc-400'
}

// Navigation props
const previousMeeting = computed(() => props.previousMeeting)
const nextMeeting = computed(() => props.nextMeeting)

const formatNavDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
