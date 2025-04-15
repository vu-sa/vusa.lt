<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button variant="outline" size="icon" class="rounded-full relative">
        <CheckSquare2Icon class="h-4 w-4" />
        <span v-if="pendingTasks > 0" class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">
          {{ pendingTasks > 9 ? '9+' : pendingTasks }}
        </span>
        <span class="sr-only">{{ $t('Tasks') }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-80 p-0">
      <div class="p-4 border-b">
        <div class="flex items-center justify-between">
          <h4 class="font-medium">{{ $t('Your Tasks') }}</h4>
          <Badge>{{ pendingTasks }} {{ $t('pending') }}</Badge>
        </div>
      </div>
      <ScrollArea class="h-[300px]">
        <div v-if="tasks.length > 0" class="divide-y">
          <div v-for="task in tasks" :key="task.id" class="flex items-start gap-2 p-4">
            <div :class="[
              'mt-0.5 rounded-full p-1',
              task.completed_at ? 'bg-green-500/20 text-green-600' : 'bg-amber-500/20 text-amber-600'
            ]">
              <component :is="task.completed_at ? CheckIcon : ClockIcon" class="h-3 w-3" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium">{{ task.name }}</p>
              <p v-if="task.taskable" class="text-xs text-muted-foreground">
                {{ getTaskableLabel(task) }}
              </p>
              <div v-if="task.due_date" class="text-xs text-muted-foreground">
                {{ formatDueDate(task.due_date) }}
              </div>
              <div class="mt-2 flex items-center gap-2">
                <Button 
                  v-if="!task.completed_at" 
                  size="sm" 
                  variant="outline" 
                  class="h-7 text-xs"
                  :disabled="isUpdating === task.id"
                  @click="markTaskComplete(task)"
                >
                  <LoaderCircleIcon v-if="isUpdating === task.id" class="mr-1 h-3 w-3 animate-spin" />
                  {{ $t('Mark Complete') }}
                </Button>
                <Link v-if="task.taskable" :href="getTaskableLink(task)" class="text-xs text-primary hover:underline">
                  {{ $t('View') }}
                </Link>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="flex h-full items-center justify-center p-8 text-center">
          <div>
            <CheckSquare2Icon class="mx-auto h-6 w-6 text-muted-foreground" />
            <h3 class="mt-2 text-sm font-medium">{{ $t('No tasks') }}</h3>
            <p class="mt-1 text-xs text-muted-foreground">
              {{ $t('You have completed all your tasks') }}
            </p>
          </div>
        </div>
      </ScrollArea>
      <div class="border-t p-2">
        <Link :href="route('userTasks')" class="block w-full rounded-sm p-2 text-center text-xs hover:bg-muted">
          {{ $t('View All Tasks') }}
        </Link>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { trans as $t } from "laravel-vue-i18n"
import { 
  CheckSquare2Icon, 
  CheckIcon, 
  ClockIcon,
  LoaderCircleIcon
} from 'lucide-vue-next'
import { format } from 'date-fns'

import { 
  Popover, 
  PopoverContent, 
  PopoverTrigger 
} from '@/Components/ui/popover'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'
import { toast } from 'vue-sonner'

// Define Task interface matching the backend structure
interface Task {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  taskable_type: string;
  taskable_id: string;
  taskable?: any;
  completed_at?: string | null;
}

// State
const tasks = ref<Task[]>([])
const isLoading = ref(true)
const isUpdating = ref<string | null>(null)
const maxTasksToDisplay = 5

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
  return tasks.value.filter(task => !task.completed_at).length
})

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
    const response = await fetch(route('tasks.indicator'))
    if (response.ok) {
      const data = await response.json()
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
 * Format due date
 */
function formatDueDate(date: string) {
  try {
    return format(new Date(date), 'yyyy-MM-dd')
  } catch (e) {
    return date
  }
}

/**
 * Get human-readable label for task's related object
 */
function getTaskableLabel(task: Task) {
  if (!task.taskable) return ''
  
  switch (task.taskable_type) {
    case 'App\\Models\\Meeting':
      return task.taskable.title || $t('Meeting')
    case 'App\\Models\\Goal':
      return task.taskable.title || $t('Goal')
    case 'App\\Models\\Reservation':
      return task.taskable.name || $t('Reservation')
    default:
      return task.taskable.name || task.taskable.title || ''
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
    case 'goal':
      return route('goals.show', task.taskable_id)
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
  isUpdating.value = task.id
  
  router.post(
    route('tasks.updateCompletionStatus', task.id),
    { completed: true },
    {
      preserveScroll: true,
      onSuccess: () => {
        task.completed_at = new Date().toISOString()
        toast.success($t('Task marked as completed'))
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