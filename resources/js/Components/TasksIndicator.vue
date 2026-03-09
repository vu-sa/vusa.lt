<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button 
        variant="outline" 
        size="icon" 
        class="relative rounded-full md:w-auto md:gap-2 md:px-3" 
        data-tour="tasks-indicator"
      >
        <ClipboardCheckIcon class="h-4 w-4" />
        <span class="hidden text-sm md:inline">{{ pendingTasks }}</span>
        
        <!-- Mobile badge -->
        <span 
          v-if="pendingTasks > 0" 
          :class="[
            'absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full text-[10px] font-medium md:hidden',
            hasOverdue 
              ? 'bg-red-500 text-white' 
              : 'bg-primary text-primary-foreground'
          ]"
        >
          {{ pendingTasks > 9 ? '9+' : pendingTasks }}
        </span>
        
        <span class="sr-only">{{ $t('Tasks') }}</span>
      </Button>
    </PopoverTrigger>
    
    <PopoverContent class="w-96 p-0" align="end">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
        <div class="flex items-center gap-2">
          <ClipboardCheckIcon class="h-4 w-4 text-zinc-500" />
          <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $t('Your Tasks') }}</h4>
        </div>
        <div class="flex items-center gap-2">
          <Badge v-if="hasOverdue" variant="destructive" class="text-xs">
            {{ overdueCount }} {{ $t('overdue') }}
          </Badge>
          <Badge variant="secondary" class="text-xs">
            {{ pendingTasks }} {{ $t('pending') }}
          </Badge>
        </div>
      </div>
      
      <!-- Task list -->
      <ScrollArea class="h-[340px]">
        <div v-if="isLoading" class="flex items-center justify-center p-8">
          <LoaderCircleIcon class="h-6 w-6 animate-spin text-zinc-400" />
        </div>
        
        <div v-else-if="tasks.length > 0" class="divide-y divide-zinc-100 dark:divide-zinc-800">
          <div 
            v-for="task in tasks" 
            :key="task.id" 
            :class="[
              'group relative px-4 py-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900/50',
              task.is_overdue && 'bg-red-50/30 dark:bg-red-950/5'
            ]"
          >
            <div class="flex items-start gap-3">
              <!-- Action type indicator -->
              <div class="mt-0.5 shrink-0">
                <!-- Progress ring for tasks with progress -->
                <div 
                  v-if="task.progress" 
                  class="relative h-7 w-7"
                  :title="`${task.progress.current}/${task.progress.total}`"
                >
                  <svg class="h-7 w-7 -rotate-90" viewBox="0 0 28 28">
                    <circle
                      cx="14" cy="14" r="12"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      class="text-zinc-200 dark:text-zinc-700"
                    />
                    <circle
                      cx="14" cy="14" r="12"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      :stroke-dasharray="`${task.progress.percentage * 0.754} 75.4`"
                      :class="getProgressColor(task)"
                    />
                  </svg>
                  <span class="absolute inset-0 flex items-center justify-center text-[9px] font-bold text-zinc-600 dark:text-zinc-400">
                    {{ task.progress.percentage }}%
                  </span>
                </div>
                
                <!-- Icon for other tasks -->
                <div 
                  v-else
                  :class="[
                    'flex h-7 w-7 items-center justify-center rounded-lg',
                    getIconBgClass(task)
                  ]"
                >
                  <component 
                    :is="getTaskIcon(task)" 
                    class="h-4 w-4" 
                    :class="getIconColorClass(task)"
                  />
                </div>
              </div>
              
              <!-- Task content -->
              <div class="min-w-0 flex-1 overflow-hidden">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100" :title="task.name">
                  {{ task.name }}
                </p>
                
                <!-- Meta info -->
                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs">
                  <!-- Auto-complete label -->
                  <span 
                    v-if="!task.can_be_manually_completed" 
                    :class="['font-medium', getActionTypeColor(task)]"
                  >
                    {{ getActionTypeLabel(task) }}
                  </span>
                  
                  <!-- Taskable reference -->
                  <span 
                    v-if="task.taskable?.name" 
                    class="text-zinc-500 dark:text-zinc-400"
                  >
                    {{ task.taskable.name }}
                  </span>
                  
                  <!-- Due date -->
                  <span 
                    v-if="task.due_date" 
                    :class="[
                      task.is_overdue 
                        ? 'font-medium text-red-600 dark:text-red-400' 
                        : 'text-zinc-500 dark:text-zinc-400'
                    ]"
                  >
                    {{ formatDueDate(task.due_date) }}
                  </span>
                </div>
                
                <!-- Actions -->
                <div class="mt-2 flex items-center gap-2">
                  <Button 
                    v-if="task.can_be_manually_completed" 
                    size="sm" 
                    variant="outline" 
                    class="h-7 text-xs"
                    :disabled="isUpdating === task.id"
                    @click="markTaskComplete(task)"
                  >
                    <LoaderCircleIcon v-if="isUpdating === task.id" class="mr-1.5 h-3 w-3 animate-spin" />
                    <CheckIcon v-else class="mr-1.5 h-3 w-3" />
                    {{ $t('Complete') }}
                  </Button>
                  
                  <Link 
                    v-if="task.taskable" 
                    :href="getTaskableLink(task)" 
                    class="text-xs font-medium text-primary hover:underline"
                  >
                    {{ $t('View') }} →
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Empty state -->
        <div v-else class="flex h-full flex-col items-center justify-center p-8 text-center">
          <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <CheckCircleIcon class="h-6 w-6 text-zinc-400 dark:text-zinc-500" />
          </div>
          <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
            {{ $t('All caught up!') }}
          </h3>
          <p class="mt-1 max-w-[200px] text-xs text-zinc-500 dark:text-zinc-400">
            {{ $t('You have no pending tasks. Great work!') }}
          </p>
        </div>
      </ScrollArea>
      
      <!-- Footer -->
      <div class="border-t border-zinc-200 p-2 dark:border-zinc-800">
        <Link 
          :href="route('userTasks')" 
          class="flex w-full items-center justify-center gap-1.5 rounded-md py-2 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
        >
          {{ $t('View All Tasks') }}
          <ArrowRightIcon class="h-3 w-3" />
        </Link>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { trans as $t } from "laravel-vue-i18n"
import { formatDistanceToNow, parseISO, isToday, isTomorrow } from 'date-fns'
import { lt, enUS } from 'date-fns/locale'
import { 
  ClipboardCheck as ClipboardCheckIcon,
  CheckCircle as CheckCircleIcon,
  CheckIcon, 
  LoaderCircleIcon,
  ArrowRight as ArrowRightIcon,
  ShieldCheck as ShieldCheckIcon,
  Package as PackageIcon,
  PackageCheck as PackageCheckIcon,
} from 'lucide-vue-next'

import { 
  Popover, 
  PopoverContent, 
  PopoverTrigger 
} from '@/Components/ui/popover'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { toast } from 'vue-sonner'
import { TaskActionType, type TaskProgress } from '@/Types/TaskTypes'

// Task interface matching the API response
interface Task {
  id: string
  name: string
  description?: string | null
  due_date?: string | null
  is_overdue: boolean
  action_type?: TaskActionType | string | null
  progress?: TaskProgress | null
  can_be_manually_completed: boolean
  taskable_type: string
  taskable_id: string
  taskable?: {
    id: string
    name?: string
  } | null
}

// State
const tasks = ref<Task[]>([])
const isLoading = ref(true)
const isUpdating = ref<string | null>(null)
const maxTasksToDisplay = 5

// Locale for date formatting
const dateLocale = computed(() => usePage().props.app.locale === 'lt' ? lt : enUS)

// Get pending tasks count from Inertia shared props if available
const page = usePage()
const userTasksCount = computed(() => {
  return page.props.auth?.user?.tasks_count || 0
})

// Compute number of pending tasks
const pendingTasks = computed(() => {
  if (userTasksCount.value > 0) {
    return userTasksCount.value
  }
  return tasks.value.length
})

// Check if any tasks are overdue
const hasOverdue = computed(() => tasks.value.some(t => t.is_overdue))
const overdueCount = computed(() => tasks.value.filter(t => t.is_overdue).length)

// Fetch user tasks when component mounts
onMounted(() => {
  fetchUserTasks()
})

/**
 * Fetch current user's tasks
 */
async function fetchUserTasks() {
  isLoading.value = true
  try {
    const response = await fetch(route('api.v1.admin.tasks.indicator'))
    if (response.ok) {
      const json = await response.json()
      const data = json.success ? json.data : json
      tasks.value = data.slice(0, maxTasksToDisplay)
    } else {
      console.error('Failed to fetch tasks')
    }
  } catch (error) {
    console.error('Error fetching tasks:', error)
  } finally {
    isLoading.value = false
  }
}

/**
 * Format due date in a human-readable way
 */
function formatDueDate(date: string) {
  try {
    const parsed = parseISO(date)
    if (isToday(parsed)) return $t('Today')
    if (isTomorrow(parsed)) return $t('Tomorrow')
    return formatDistanceToNow(parsed, { addSuffix: true, locale: dateLocale.value })
  } catch {
    return date
  }
}

/**
 * Get icon component based on action type
 */
function getTaskIcon(task: Task) {
  switch (task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return ShieldCheckIcon
    case TaskActionType.Pickup:
    case 'pickup':
      return PackageIcon
    case TaskActionType.Return:
    case 'return':
      return PackageCheckIcon
    default:
      return ClipboardCheckIcon
  }
}

/**
 * Get icon background class based on action type
 */
function getIconBgClass(task: Task) {
  switch (task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'bg-blue-100 dark:bg-blue-900/30'
    case TaskActionType.Pickup:
    case 'pickup':
      return 'bg-amber-100 dark:bg-amber-900/30'
    case TaskActionType.Return:
    case 'return':
      return 'bg-emerald-100 dark:bg-emerald-900/30'
    default:
      return 'bg-zinc-100 dark:bg-zinc-800'
  }
}

/**
 * Get icon color class based on action type
 */
function getIconColorClass(task: Task) {
  switch (task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'text-blue-600 dark:text-blue-400'
    case TaskActionType.Pickup:
    case 'pickup':
      return 'text-amber-600 dark:text-amber-400'
    case TaskActionType.Return:
    case 'return':
      return 'text-emerald-600 dark:text-emerald-400'
    default:
      return 'text-zinc-600 dark:text-zinc-400'
  }
}

/**
 * Get progress circle color based on action type
 */
function getProgressColor(task: Task) {
  switch (task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'text-blue-500 dark:text-blue-400'
    case TaskActionType.Pickup:
    case 'pickup':
      return 'text-amber-500 dark:text-amber-400'
    case TaskActionType.Return:
    case 'return':
      return 'text-emerald-500 dark:text-emerald-400'
    default:
      return 'text-primary'
  }
}

/**
 * Get action type label color
 */
function getActionTypeColor(task: Task) {
  switch (task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'text-blue-600 dark:text-blue-400'
    case TaskActionType.Pickup:
    case 'pickup':
      return 'text-amber-600 dark:text-amber-400'
    case TaskActionType.Return:
    case 'return':
      return 'text-emerald-600 dark:text-emerald-400'
    default:
      return 'text-zinc-600 dark:text-zinc-400'
  }
}

/**
 * Get human-readable action type label
 */
function getActionTypeLabel(task: Task) {
  switch (task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return $t('Auto: approval')
    case TaskActionType.Pickup:
    case 'pickup':
      return $t('Auto: pickup')
    case TaskActionType.Return:
    case 'return':
      return $t('Auto: return')
    default:
      return ''
  }
}

/**
 * Generate link to the task's related object
 */
function getTaskableLink(task: Task) {
  if (!task.taskable) return '#'
  
  const type = task.taskable_type.split('\\').pop()?.toLowerCase()
  
  switch (type) {
    case 'meeting':
      return route('meetings.show', task.taskable_id)
    case 'reservation':
      return route('reservations.show', task.taskable_id)
    default:
      return '#'
  }
}

/**
 * Mark task as complete
 */
function markTaskComplete(task: Task) {
  if (!task.can_be_manually_completed) {
    toast.info($t('This task completes automatically'))
    return
  }
  
  isUpdating.value = task.id
  
  router.post(
    route('tasks.updateCompletionStatus', task.id),
    { completed: true },
    {
      preserveScroll: true,
      onSuccess: () => {
        // Remove from local list
        tasks.value = tasks.value.filter(t => t.id !== task.id)
        toast.success($t('Task completed'))
        isUpdating.value = null
      },
      onError: () => {
        toast.error($t('Failed to update task'))
        isUpdating.value = null
      }
    }
  )
}
</script>
