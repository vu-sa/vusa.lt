<template>
  <div class="space-y-4">
    <!-- Header with filters and stats -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-2">
        <TaskFilter
          v-model="currentFilter"
          :disabled="disabled"
          :options="filterOptions" 
        />
        <Badge v-if="filteredTasks.length > 0" variant="secondary" class="tabular-nums">
          {{ filteredTasks.length }}
        </Badge>
      </div>
      
      <!-- Stats summary (when stats provided) -->
      <div v-if="taskStats" class="flex flex-wrap items-center gap-2">
        <Badge 
          v-if="taskStats.overdue > 0" 
          variant="destructive"
          class="gap-1 text-xs"
        >
          <AlertCircleIcon class="h-3 w-3" />
          {{ taskStats.overdue }} {{ $t('overdue') }}
        </Badge>
        <Badge 
          v-if="taskStats.autoCompleting > 0" 
          variant="secondary"
          class="gap-1 text-xs"
        >
          <RotateCwIcon class="h-3 w-3" />
          {{ taskStats.autoCompleting }} {{ $t('tasks.auto_completing') }}
        </Badge>
        <Badge 
          v-if="taskStats.completed > 0" 
          variant="secondary"
          class="gap-1 text-xs"
        >
          <CheckCircleIcon class="h-3 w-3" />
          {{ taskStats.completed }} {{ $t('completed') }}
        </Badge>
      </div>
    </div>
    
    <!-- Task table (desktop) -->
    <TaskTable 
      v-if="!isMobile"
      :tasks="filteredTasks" 
      :key="taskFilterKey"
      @open-meeting-modal="(task) => emit('openMeetingModal', task)"
      @open-check-in-dialog="(task) => emit('openCheckInDialog', task)"
      @open-task-detail="(task) => emit('openTaskDetail', task)"
    />

    <!-- Task cards (mobile) -->
    <div v-else class="space-y-3">
      <div v-if="filteredTasks.length === 0" class="flex flex-col items-center justify-center gap-3 py-8 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
          <CheckCircleIcon class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
        </div>
        <div>
          <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $t('Viskas atlikta!') }}</p>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('No tasks found.') }}</p>
        </div>
      </div>
      <TaskCard
        v-for="task in filteredTasks"
        :key="task.id"
        :task="task"
        :is-loading="loadingTaskId === task.id"
        @open-meeting-modal="(t) => emit('openMeetingModal', t)"
        @open-check-in-dialog="(t) => emit('openCheckInDialog', t)"
        @open-task-detail="(t) => emit('openTaskDetail', t)"
        @update:completed="handleTaskCompletion"
      />
    </div>

    <!-- Create task dialog (async loaded) -->
    <CreateTaskDialog 
      :open="false" 
      :taskable="taskable" 
      @close="showCreateTaskDialog = false"
      @task-created="handleTaskCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref, watch, defineAsyncComponent } from "vue";
import { router } from "@inertiajs/vue3";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";
import TaskFilter from "@/Components/Tasks/TaskFilter.vue";
import TaskTable from "./TaskTable.vue";
import TaskCard from "./TaskCard.vue";
import { Badge } from "@/Components/ui/badge";
import { AlertCircleIcon, RotateCwIcon, CheckCircleIcon } from "lucide-vue-next";
import { toast } from "vue-sonner";
import type { TaskProgress, TaskActionType } from "@/Types/TaskTypes";

// Use async component for the dialog to improve initial load performance
const CreateTaskDialog = defineAsyncComponent(() => 
  import('./CreateTaskDialog.vue')
);

// Mobile detection
const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smaller('md');

// Task filtering states
enum FilterType {
  ALL = "all",
  COMPLETED = "completed",
  INCOMPLETE = "incomplete"
}

// Enhanced task interface matching backend response
interface TaskWithDetails {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  action_type?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
  taskable?: {
    id: string;
    name?: string;
    type?: string;
  } | null;
  taskable_type: string;
  taskable_id: string;
  users?: Array<{
    id: string;
    name: string;
    profile_photo_path?: string;
  }>;
}

interface TaskStats {
  total: number;
  completed: number;
  overdue: number;
  autoCompleting: number;
}

const props = defineProps<{
  disabled?: boolean;
  tasks?: TaskWithDetails[];
  taskStats?: TaskStats;
  taskable?: {
    id: string | number;
    type: string;
  };
  // Server-side filtering support
  serverSideFilter?: boolean;
  currentFilter?: 'all' | 'completed' | 'incomplete';
}>();

const emit = defineEmits<{
  (e: 'openMeetingModal', task: TaskWithDetails): void;
  (e: 'openCheckInDialog', task: TaskWithDetails): void;
  (e: 'openTaskDetail', task: TaskWithDetails): void;
  (e: 'filterChange', status: 'all' | 'completed' | 'incomplete'): void;
}>();

// Component state
const showCreateTaskDialog = ref(false);
const taskFilterKey = ref(0);
const loadingTaskId = ref<string | null>(null);

// Filter options
const filterOptions = [
  { label: $t('tasks.filters.all'), value: FilterType.ALL },
  { label: $t('tasks.filters.completed'), value: FilterType.COMPLETED },
  { label: $t('tasks.filters.incomplete'), value: FilterType.INCOMPLETE }
];

// Task filtering - default to incomplete tasks
// Use props.currentFilter if server-side filtering is enabled
const currentFilter = ref<FilterType>(
  props.serverSideFilter && props.currentFilter 
    ? props.currentFilter as FilterType 
    : FilterType.INCOMPLETE
);

// When server-side filtering is enabled, use tasks directly (already filtered by backend)
// Otherwise, filter client-side
const filteredTasks = computed(() => {
  if (!props.tasks?.length) {
    return [];
  }

  // Server-side filtering: backend already filtered, just return tasks
  if (props.serverSideFilter) {
    return props.tasks;
  }

  // Client-side filtering
  switch (currentFilter.value) {
    case FilterType.COMPLETED:
      return props.tasks.filter(task => task.completed_at !== null);
    case FilterType.INCOMPLETE:
      return props.tasks.filter(task => task.completed_at === null);
    default:
      return props.tasks;
  }
});

// Force re-render of TaskTable when filter changes
watch(currentFilter, (newFilter) => {
  taskFilterKey.value++;
  
  // Emit filter change for server-side filtering
  if (props.serverSideFilter) {
    emit('filterChange', newFilter as 'all' | 'completed' | 'incomplete');
  }
});

/**
 * Handle task completion from card component
 */
const handleTaskCompletion = (task: TaskWithDetails) => {
  if (task.can_be_manually_completed === false) {
    toast.info($t("This task completes automatically"));
    return;
  }

  if (loadingTaskId.value) return;
  loadingTaskId.value = task.id;

  const newCompletionState = task.completed_at === null;

  router.post(
    route("tasks.updateCompletionStatus", task.id),
    { completed: newCompletionState },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        loadingTaskId.value = null;
        if (newCompletionState) {
          toast.success($t("Task marked as completed"), { description: task.name });
        } else {
          toast.info($t("Task marked as incomplete"), { description: task.name });
        }
      },
      onError: () => {
        loadingTaskId.value = null;
        toast.error($t("Failed to update task status"));
      },
    }
  );
};

/**
 * Handle successful task creation
 */
const handleTaskCreated = () => {
  // Additional logic after task creation can be added here
};
</script>
