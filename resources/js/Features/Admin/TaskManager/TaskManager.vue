<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
      <!-- A quiet count line, like a collection's "Rasta N": status colour only where something is late. -->
      <p v-if="statParts.length" class="flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground" data-slot="task-stats">
        <template v-for="(part, index) in statParts" :key="part.key">
          <span v-if="index > 0" aria-hidden="true">·</span>
          <span :class="part.class">{{ part.text }}</span>
        </template>
      </p>

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

    <ul v-if="filteredTasks.length > 0" :class="ROW_LIST_CLASS">
      <li v-for="task in filteredTasks" :key="task.id" :class="ROW_ITEM_CLASS">
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
          {{ $t('tasks.manager.all_done') }}
        </p>
        <p class="text-sm text-muted-foreground">
          {{ $t('tasks.manager.no_tasks_found') }}
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
          <AlertDialogAction :class="buttonVariants({ variant: 'destructive', voice: 'brand' })" @click="handleDelete">
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
import { CheckCircleIcon } from '@lucide/vue';
import { toast } from 'vue-sonner';

import { useTaskActionDialogs } from '@/Composables/useTaskActionDialogs';
import type { TaskDisplayData } from '@/Composables/useTaskPresentation';
import { ROW_ITEM_CLASS, ROW_LIST_CLASS } from '@/Components/Collection/rowList';
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

import TaskRow from './TaskRow.vue';
import type { TaskActionKey } from './taskActions';

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

const statParts = computed(() => {
  const stats = props.taskStats;
  if (!stats) {
    return [];
  }

  return [
    stats.overdue > 0 ? { key: 'overdue', text: `${stats.overdue} ${$t('overdue')}`, class: 'font-medium text-status-danger' } : null,
    stats.autoCompleting > 0 ? { key: 'auto', text: `${stats.autoCompleting} ${$t('tasks.manager.auto_completing_count')}`, class: undefined } : null,
    stats.completed > 0 ? { key: 'completed', text: `${stats.completed} ${$t('completed')}`, class: undefined } : null,
  ].filter((part): part is { key: string; text: string; class: string | undefined } => part !== null);
});

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
    toast.info($t('tasks.manager.completes_automatically'), {
      description: $t('tasks.manager.cannot_complete_manually'),
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
        toast.error($t('tasks.manager.update_status_failed'), {
          description: $t('tasks.manager.try_again'),
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
      toast.error($t('tasks.manager.delete_failed'), {
        description: errors.message || $t('tasks.manager.try_again'),
      });
    },
  });
};
</script>
