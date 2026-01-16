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
          class="gap-1 bg-blue-100 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
        >
          <RotateCwIcon class="h-3 w-3" />
          {{ taskStats.autoCompleting }} {{ $t('tasks.auto_completing') }}
        </Badge>
        <Badge 
          v-if="taskStats.completed > 0" 
          class="gap-1 bg-emerald-100 text-xs text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
        >
          <CheckCircleIcon class="h-3 w-3" />
          {{ taskStats.completed }} {{ $t('completed') }}
        </Badge>
      </div>
    </div>
    
    <!-- Task table -->
    <TaskTable :tasks="filteredTasks" :key="taskFilterKey" />

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
import TaskFilter from "@/Components/Tasks/TaskFilter.vue";
import TaskTable from "./TaskTable.vue";
import { Badge } from "@/Components/ui/badge";
import { AlertCircleIcon, RotateCwIcon, CheckCircleIcon } from "lucide-vue-next";
import type { TaskProgress, TaskActionType } from "@/Types/TaskTypes";

// Use async component for the dialog to improve initial load performance
const CreateTaskDialog = defineAsyncComponent(() => 
  import('./CreateTaskDialog.vue')
);

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
}>();

// Component state
const showCreateTaskDialog = ref(false);
const taskFilterKey = ref(0);

// Filter options
const filterOptions = [
  { label: $t('tasks.filters.all'), value: FilterType.ALL },
  { label: $t('tasks.filters.completed'), value: FilterType.COMPLETED },
  { label: $t('tasks.filters.incomplete'), value: FilterType.INCOMPLETE }
];

// Task filtering - default to incomplete tasks
const currentFilter = ref(FilterType.INCOMPLETE);
const filteredTasks = computed(() => {
  if (!props.tasks?.length) {
    return [];
  }

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
watch(currentFilter, () => {
  taskFilterKey.value++;
});

/**
 * Handle successful task creation
 */
const handleTaskCreated = () => {
  // Additional logic after task creation can be added here
};
</script>
