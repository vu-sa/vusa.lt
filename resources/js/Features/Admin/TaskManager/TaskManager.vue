<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
      <template v-if="taskStats">
        <Badge v-if="taskStats.overdue > 0" variant="rose" class="gap-1 text-xs">
          <AlertCircleIcon class="h-3 w-3" />
          {{ taskStats.overdue }} {{ $t('overdue') }}
        </Badge>
        <Badge v-if="taskStats.autoCompleting > 0" variant="secondary" class="gap-1 text-xs">
          <RotateCwIcon class="h-3 w-3" />
          {{ taskStats.autoCompleting }} {{ $t('tasks.auto_completing') }}
        </Badge>
        <Badge v-if="taskStats.completed > 0" variant="secondary" class="gap-1 text-xs">
          <CheckCircleIcon class="h-3 w-3" />
          {{ taskStats.completed }} {{ $t('completed') }}
        </Badge>
      </template>

      <!-- Open tasks by default; completed ones only on request. -->
      <button
        type="button"
        :disabled
        :aria-pressed="showAll"
        :class="['ml-auto', controlVariants({ size: 'sm', active: showAll })]"
        @click="showAll = !showAll"
      >
        {{ $t('tasks.filters.show_all') }}
      </button>
    </div>

    <ul v-if="filteredTasks.length > 0" class="divide-y divide-border border-y border-border">
      <li v-for="task in filteredTasks" :key="task.id">
        <TaskRow
          :task
          :loading="loadingTaskId === task.id"
          @open="emit('openTaskDetail', task)"
          @action="key => runAction(task, key)"
        />
      </li>
    </ul>

    <div v-else class="flex flex-col items-center justify-center gap-3 py-8 text-center">
      <div class="flex h-12 w-12 items-center justify-center border border-border bg-muted">
        <CheckCircleIcon class="h-6 w-6 text-muted-foreground" />
      </div>
      <div>
        <p class="font-medium text-foreground">
          {{ $t('Viskas atlikta!') }}
        </p>
        <p class="text-sm text-muted-foreground">
          {{ $t('No tasks found.') }}
        </p>
      </div>
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
import { computed, defineAsyncComponent, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { AlertCircleIcon, RotateCwIcon, CheckCircleIcon } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import type { TaskActionKey } from './taskActions';
import TaskRow from './TaskRow.vue';

import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { Badge } from '@/Components/ui/badge';
import { controlVariants } from '@/Components/ui/control';
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
}>();

const emit = defineEmits<{
  openTaskDetail: [task: TaskDisplayData];
}>();

const { openReportWindow } = useTaskActionDialogs();

// Component state
const showCreateTaskDialog = ref(false);
const loadingTaskId = ref<string | null>(null);
/**
 * The dialog's own open flag is deliberately separate from the task it is about. Deriving
 * `open` from the task meant reka-ui's AlertDialogAction — which closes the dialog on click,
 * before any handler of ours runs — cleared the task first, so the confirm handler always
 * found nothing to delete.
 */
const deleteDialogOpen = ref(false);
const taskPendingDeletion = ref<TaskDisplayData | null>(null);

const showAll = ref(false);

const filteredTasks = computed(() => {
  const tasks = props.tasks ?? [];

  return showAll.value ? tasks : tasks.filter(task => !task.completed_at);
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

const runAction = (task: TaskDisplayData, key: TaskActionKey) => {
  switch (key) {
    case 'report':
      openReportWindow(task);
      break;
    case 'complete':
      handleTaskCompletion(task);
      break;
    case 'delete':
      confirmDelete(task);
      break;
  }
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
