<template>
  <div class="space-y-4">
    <!-- Header with filters and stats -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-2">
        <TaskFilter
          v-model="currentFilter"
          :disabled
          :options="filterOptions"
        />
        <Badge v-if="visibleCount > 0" variant="secondary" class="tabular-nums">
          {{ visibleCount }}
        </Badge>
      </div>

      <!-- Stats summary (when stats provided) -->
      <div v-if="taskStats" class="flex flex-wrap items-center gap-2">
        <Badge
          v-if="taskStats.overdue > 0"
          variant="rose"
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
      :key="taskFilterKey"
      :tasks="filteredTasks"
      :loading-task-id
      :enable-pagination="!serverPaginated"
      :enable-filtering="!serverPaginated"
      @open-meeting-modal="(task) => emit('openMeetingModal', task)"
      @open-check-in-dialog="(task) => emit('openCheckInDialog', task)"
      @open-task-detail="(task) => emit('openTaskDetail', task)"
      @update:completed="handleTaskCompletion"
      @delete="confirmDelete"
    />

    <!-- Task cards (mobile) -->
    <div v-else class="space-y-3">
      <div v-if="filteredTasks.length === 0" class="flex flex-col items-center justify-center gap-3 py-8 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
          <CheckCircleIcon class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
        </div>
        <div>
          <p class="font-medium text-zinc-900 dark:text-zinc-100">
            {{ $t('Viskas atlikta!') }}
          </p>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('No tasks found.') }}
          </p>
        </div>
      </div>
      <TaskCard
        v-for="task in filteredTasks"
        :key="task.id"
        :task
        :is-loading="loadingTaskId === task.id"
        @open-meeting-modal="(t) => emit('openMeetingModal', t)"
        @open-check-in-dialog="(t) => emit('openCheckInDialog', t)"
        @open-task-detail="(t) => emit('openTaskDetail', t)"
        @update:completed="handleTaskCompletion"
        @delete="confirmDelete"
      />
    </div>

    <!-- Deleting a task is permanent, and for automatic tasks it is a super-admin escape
         hatch rather than an everyday action — always ask first. -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ $t('tasks.delete_confirm_title') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ $t('tasks.delete_confirm_description', { name: taskPendingDeletion?.name ?? '' }) }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
          <AlertDialogAction :class="buttonVariants({ variant: 'destructive' })" @click="handleDelete">
            {{ $t('forms.delete') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Create task dialog (async loaded only once opened) -->
    <CreateTaskDialog
      v-if="showCreateTaskDialog"
      :open="showCreateTaskDialog"
      :taskable
      @close="showCreateTaskDialog = false"
      @task-created="showCreateTaskDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useBreakpoints, breakpointsTailwind } from '@vueuse/core';
import { AlertCircleIcon, RotateCwIcon, CheckCircleIcon } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import TaskTable from './TaskTable.vue';
import TaskCard from './TaskCard.vue';

import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import TaskFilter from '@/Components/Tasks/TaskFilter.vue';
import { Badge } from '@/Components/ui/badge';
import { buttonVariants } from '@/Components/ui/button';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';

// Use async component for the dialog to improve initial load performance
const CreateTaskDialog = defineAsyncComponent(() => import('./CreateTaskDialog.vue'));

// Mobile detection
const breakpoints = useBreakpoints(breakpointsTailwind);
const isMobile = breakpoints.smaller('md');

// Task filtering states
enum FilterType {
  ALL = 'all',
  COMPLETED = 'completed',
  INCOMPLETE = 'incomplete',
}

interface TaskStats {
  total: number;
  completed: number;
  overdue: number;
  autoCompleting: number;
}

const props = defineProps<{
  disabled?: boolean;
  tasks?: TaskDisplayData[];
  taskStats?: TaskStats;
  taskable?: {
    id: string | number;
    type: string;
  };
  /** The page filters through the backend; the local filter only mirrors what it chose. */
  serverSideFilter?: boolean;
  /**
   * The page paginates server-side, so the table must not paginate (or search) the single
   * page it was handed — that produced a second set of page controls inside the first.
   */
  serverPaginated?: boolean;
  currentFilter?: 'all' | 'completed' | 'incomplete';
  /** Total across all pages; the local count is only the current page when paginating. */
  totalCount?: number;
}>();

const emit = defineEmits<{
  (e: 'openMeetingModal', task: TaskDisplayData): void;
  (e: 'openCheckInDialog', task: TaskDisplayData): void;
  (e: 'openTaskDetail', task: TaskDisplayData): void;
  (e: 'filterChange', status: 'all' | 'completed' | 'incomplete'): void;
}>();

// Component state
const showCreateTaskDialog = ref(false);
const taskFilterKey = ref(0);
const loadingTaskId = ref<string | null>(null);
/**
 * The dialog's own open flag is deliberately separate from the task it is about. Deriving
 * `open` from the task meant reka-ui's AlertDialogAction — which closes the dialog on click,
 * before any handler of ours runs — cleared the task first, so the confirm handler always
 * found nothing to delete.
 */
const deleteDialogOpen = ref(false);
const taskPendingDeletion = ref<TaskDisplayData | null>(null);

const filterOptions = [
  { label: $t('tasks.filters.all'), value: FilterType.ALL },
  { label: $t('tasks.filters.completed'), value: FilterType.COMPLETED },
  { label: $t('tasks.filters.incomplete'), value: FilterType.INCOMPLETE },
];

// Task filtering - default to incomplete tasks
const currentFilter = ref<FilterType>(
  props.serverSideFilter && props.currentFilter
    ? props.currentFilter as FilterType
    : FilterType.INCOMPLETE,
);

// Keep the control in step when the backend answers a filter change with new props.
watch(() => props.currentFilter, (filter) => {
  if (props.serverSideFilter && filter) {
    currentFilter.value = filter as FilterType;
  }
});

const filteredTasks = computed(() => {
  if (!props.tasks?.length) {
    return [];
  }

  // Server-side filtering: backend already filtered, just return tasks
  if (props.serverSideFilter) {
    return props.tasks;
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

const visibleCount = computed(() => props.totalCount ?? filteredTasks.value.length);

// Force re-render of TaskTable when filter changes
watch(currentFilter, (newFilter) => {
  taskFilterKey.value++;

  if (props.serverSideFilter) {
    emit('filterChange', newFilter as 'all' | 'completed' | 'incomplete');
  }
});

const handleTaskCompletion = (task: TaskDisplayData) => {
  if (task.can_be_manually_completed === false) {
    toast.info($t('This task completes automatically'), {
      description: $t('You cannot manually complete this task'),
    });

    return;
  }

  if (loadingTaskId.value) {
    return;
  }
  loadingTaskId.value = task.id;

  const newCompletionState = task.completed_at === null;

  router.post(
    route('tasks.updateCompletionStatus', task.id),
    { completed: newCompletionState },
    {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => {
        loadingTaskId.value = null;
      },
      // No success toast here: the controller flashes one and useToasts shows it globally.
      // Toasting again produced two for every action.
      onError: () => {
        toast.error($t('Failed to update task status'), {
          description: $t('Please try again'),
        });
      },
    },
  );
};

const confirmDelete = (task: TaskDisplayData) => {
  taskPendingDeletion.value = task;
  deleteDialogOpen.value = true;
};

const handleDelete = () => {
  const task = taskPendingDeletion.value;
  if (!task || loadingTaskId.value) {
    return;
  }

  deleteDialogOpen.value = false;
  loadingTaskId.value = task.id;

  router.delete(route('tasks.destroy', task.id), {
    preserveScroll: true,
    onFinish: () => {
      loadingTaskId.value = null;
    },
    onError: (errors: Record<string, string>) => {
      toast.error($t('Failed to delete task'), {
        description: errors.message || $t('Please try again'),
      });
    },
  });
};
</script>
