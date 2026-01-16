<template>
  <div class="space-y-6">
    <!-- Task Summary -->
    <Card>
      <CardHeader class="pb-3">
        <CardTitle class="flex items-center gap-2 text-base">
          <CheckSquare class="h-5 w-5 text-primary" />
          {{ $t('Užduotys') }}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="tasks && tasks.length > 0" class="space-y-3">
          <div v-for="task in displayedTasks" :key="task.id" 
            class="flex items-start gap-3 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer"
            @click="$emit('viewTask', task)"
          >
            <div class="mt-0.5">
              <component :is="getTaskIcon(task)" class="h-4 w-4" :class="getTaskIconColor(task)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ task.name }}</p>
              <p v-if="task.due_date" class="text-xs" :class="isOverdue(task) ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400'">
                {{ formatRelativeTime(new Date(task.due_date)) }}
              </p>
            </div>
          </div>
          
          <Button v-if="tasks.length > 3" variant="ghost" size="sm" class="w-full" @click="$emit('goToTasks')">
            {{ $t('Rodyti visas') }} ({{ tasks.length }})
            <ChevronRight class="h-4 w-4 ml-1" />
          </Button>
        </div>
        <div v-else class="text-center py-4">
          <CheckSquare class="h-8 w-8 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
          <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('Nėra užduočių') }}</p>
        </div>
      </CardContent>
    </Card>

    <!-- Document Status -->
    <Card>
      <CardHeader class="pb-3">
        <CardTitle class="flex items-center gap-2 text-base">
          <FileCheck class="h-5 w-5 text-primary" />
          {{ $t('Dokumentai') }}
        </CardTitle>
      </CardHeader>
      <CardContent class="space-y-2">
        <div class="flex items-center justify-between py-2">
          <div class="flex items-center gap-2">
            <ScrollText class="h-4 w-4" :class="hasProtocol ? 'text-green-500' : 'text-zinc-400'" />
            <span class="text-sm">{{ $t('Protokolas') }}</span>
          </div>
          <component :is="hasProtocol ? CheckCircle2 : XCircle" 
            class="h-4 w-4" 
            :class="hasProtocol ? 'text-green-500' : 'text-zinc-400'" 
          />
        </div>
        <div class="flex items-center justify-between py-2">
          <div class="flex items-center gap-2">
            <FileBarChart class="h-4 w-4" :class="hasReport ? 'text-green-500' : 'text-zinc-400'" />
            <span class="text-sm">{{ $t('Ataskaita') }}</span>
          </div>
          <component :is="hasReport ? CheckCircle2 : XCircle" 
            class="h-4 w-4" 
            :class="hasReport ? 'text-green-500' : 'text-zinc-400'" 
          />
        </div>
        
        <Button v-if="!hasProtocol || !hasReport" variant="outline" size="sm" class="w-full mt-3" @click="$emit('goToFiles')">
          <Upload class="h-4 w-4 mr-2" />
          {{ $t('Įkelti dokumentus') }}
        </Button>
      </CardContent>
    </Card>

    <!-- Navigation Hint -->
    <Card v-if="previousMeeting || nextMeeting" class="bg-zinc-50 dark:bg-zinc-800/50 border-dashed">
      <CardContent class="p-4 space-y-2">
        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">{{ $t('Navigacija') }}</p>
        <div class="space-y-2">
          <Link v-if="previousMeeting" :href="route('meetings.show', previousMeeting.id)"
            class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 hover:text-primary transition-colors"
          >
            <ChevronLeft class="h-4 w-4" />
            <span class="truncate">{{ formatNavDate(previousMeeting.start_time) }}</span>
          </Link>
          <Link v-if="nextMeeting" :href="route('meetings.show', nextMeeting.id)"
            class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 hover:text-primary transition-colors"
          >
            <ChevronRight class="h-4 w-4" />
            <span class="truncate">{{ formatNavDate(nextMeeting.start_time) }}</span>
          </Link>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import { 
  CheckSquare, 
  FileCheck, 
  ScrollText, 
  FileBarChart,
  CheckCircle2,
  XCircle,
  Upload,
  ChevronLeft,
  ChevronRight,
  Circle,
  CircleDot
} from 'lucide-vue-next'

import { formatRelativeTime, formatStaticTime } from '@/Utils/IntlTime'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'

interface Props {
  tasks?: App.Entities.Task[]
  hasProtocol: boolean
  hasReport: boolean
  previousMeeting?: { id: string; start_time: string } | null
  nextMeeting?: { id: string; start_time: string } | null
}

const props = defineProps<Props>()

defineEmits<{
  (e: 'goToFiles'): void
  (e: 'goToTasks'): void
  (e: 'viewTask', task: App.Entities.Task): void
}>()

const displayedTasks = computed(() => (props.tasks ?? []).slice(0, 3))

const getTaskIcon = (task: App.Entities.Task) => {
  if (task.completed_at) return CheckCircle2
  return CircleDot
}

const getTaskIconColor = (task: App.Entities.Task) => {
  if (task.completed_at) return 'text-green-500'
  if (isOverdue(task)) return 'text-red-500'
  return 'text-amber-500'
}

const isOverdue = (task: App.Entities.Task) => {
  if (!task.due_date) return false
  return new Date(task.due_date) < new Date()
}

const formatNavDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
