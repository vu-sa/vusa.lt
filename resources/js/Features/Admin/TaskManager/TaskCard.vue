<template>
  <div
    :class="[
      'group relative overflow-hidden rounded-xl bg-white ring-1 ring-zinc-200/80 transition-all duration-200 hover:ring-zinc-300 hover:shadow-sm dark:bg-zinc-900 dark:ring-zinc-700/60 dark:hover:ring-zinc-600',
      task.completed_at && 'opacity-50'
    ]"
  >
    <div class="flex items-start gap-3 p-4">
      <!-- Status indicator (checkbox or progress) -->
      <div class="mt-0.5 shrink-0">
        <!-- Progress ring for auto-completing tasks -->
        <div
          v-if="!canManuallyComplete && task.progress"
          class="relative size-8"
          :title="`${task.progress.current}/${task.progress.total}`"
        >
          <svg class="size-8 -rotate-90" viewBox="0 0 32 32">
            <circle
              cx="16" cy="16" r="13"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
              class="text-zinc-200 dark:text-zinc-700"
            />
            <circle
              cx="16" cy="16" r="13"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
              stroke-linecap="round"
              :stroke-dasharray="`${task.progress.percentage * 0.817} 81.7`"
              :class="progressStrokeClass"
            />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-zinc-600 dark:text-zinc-400">
            {{ task.progress.percentage }}%
          </span>
        </div>

        <!-- Icon for auto-completing tasks without progress -->
        <div
          v-else-if="!canManuallyComplete"
          :class="['flex size-8 items-center justify-center rounded-lg', actionBackgroundClass]"
        >
          <component :is="actionIcon" class="size-4" :class="actionTextClass" />
        </div>

        <!-- Checkbox for manual tasks -->
        <Checkbox
          v-else
          :model-value="task.completed_at !== null"
          :disabled="isLoading"
          class="mt-0.5"
          @update:model-value="handleComplete"
        />
      </div>

      <!-- Task content -->
      <div class="min-w-0 flex-1">
        <!-- Task name -->
        <p
          :class="[
            'text-sm font-semibold leading-snug',
            task.completed_at ? 'line-through text-zinc-400 dark:text-zinc-500' : 'text-zinc-900 dark:text-zinc-100'
          ]"
          :title="task.name"
        >
          {{ task.name }}
        </p>

        <!-- Meta info row -->
        <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
          <!-- Auto-complete label -->
          <span v-if="!canManuallyComplete && actionLabel" :class="['font-medium', actionTextClass]">
            {{ actionLabel }}
          </span>

          <!-- Taskable reference -->
          <Link
            v-if="taskableUrl"
            :href="taskableUrl"
            class="text-zinc-500 hover:text-zinc-700 hover:underline dark:text-zinc-400 dark:hover:text-zinc-200"
          >
            {{ task.taskable?.name }}
          </Link>
          <span
            v-else-if="isOrphaned"
            class="text-zinc-400 dark:text-zinc-500"
            :title="$t('tasks.orphaned_description')"
          >
            {{ $t('tasks.orphaned') }}
          </span>

          <!-- Due date -->
          <span
            v-if="task.due_date"
            :class="[
              'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5',
              task.is_overdue
                ? 'bg-rose-100/80 font-medium text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                : 'text-zinc-500 dark:text-zinc-400'
            ]"
          >
            {{ formattedDueDate }}
          </span>
        </div>

        <!-- Assigned users -->
        <div v-if="task.users?.length" class="mt-2">
          <UsersAvatarGroup :users="task.users" :size="24" :max="4" />
        </div>

        <!-- Actions row -->
        <div class="mt-3 flex flex-wrap items-center gap-2">
          <!-- Complete button for manual tasks -->
          <Button
            v-if="canManuallyComplete && !task.completed_at"
            size="sm"
            variant="outline"
            class="h-7 gap-1.5 text-xs"
            :disabled="isLoading"
            @click="handleComplete"
          >
            <LoaderCircleIcon v-if="isLoading" class="size-3 animate-spin" />
            <CheckIcon v-else class="size-3" />
            {{ $t('Complete') }}
          </Button>

          <!-- Periodicity gap quick actions -->
          <template v-if="isPeriodicityGap && !task.completed_at && isInstitution">
            <Button
              size="sm"
              variant="outline"
              class="h-7 gap-1.5 text-xs"
              @click="emit('openMeetingModal', task)"
            >
              <CalendarPlusIcon class="size-3" />
              {{ $t('Schedule') }}
            </Button>
            <Button
              size="sm"
              variant="outline"
              class="h-7 gap-1.5 text-xs text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
              @click="emit('openCheckInDialog', task)"
            >
              <CalendarOffIcon class="size-3" />
              {{ $t('No meeting') }}
            </Button>
          </template>

          <!-- Agenda task quick actions -->
          <Link v-if="meetingAgendaUrl && !task.completed_at" :href="meetingAgendaUrl">
            <Button size="sm" variant="outline" class="h-7 gap-1.5 text-xs">
              <FilePlus2Icon v-if="isAgendaCreation" class="size-3" />
              <FileCheckIcon v-else class="size-3" />
              {{ isAgendaCreation ? $t('tasks.agenda.action_add_items') : $t('tasks.agenda.action_view_agenda') }}
            </Button>
          </Link>

          <!-- View details button (info icon) -->
          <Button
            v-if="task.description"
            size="sm"
            variant="ghost"
            class="size-7 p-0 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
            @click="emit('openTaskDetail', task)"
          >
            <InfoIcon class="size-4" />
            <span class="sr-only">{{ $t('View Details') }}</span>
          </Button>

          <!-- Delete: the mobile counterpart of the table's actions menu -->
          <Button
            v-if="canDelete"
            size="sm"
            variant="ghost"
            class="size-7 p-0 text-zinc-400 hover:text-destructive dark:text-zinc-500"
            :disabled="isLoading"
            @click="emit('delete', task)"
          >
            <TrashIcon class="size-4" />
            <span class="sr-only">{{ canManuallyComplete ? $t('Delete') : $t('tasks.delete_automatic') }}</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import {
  CalendarOffIcon,
  CalendarPlusIcon,
  CheckIcon,
  FileCheckIcon,
  FilePlus2Icon,
  InfoIcon,
  LoaderCircleIcon,
  TrashIcon,
} from 'lucide-vue-next';

import { useDateLocale } from '@/Composables/useDateLocale';
import {
  formatTaskDueDate,
  getMeetingAgendaUrl,
  getTaskActionBackgroundClass,
  getTaskActionIcon,
  getTaskActionLabel,
  getTaskActionTextClass,
  getTaskProgressStrokeClass,
  getTaskableUrl,
  isAgendaCreationTask,
  isAgendaTask,
  isInstitutionTask,
  isMeetingTask,
  isOrphanedTask,
  isPeriodicityGapTask,
  type TaskDisplayData,
} from '@/Composables/useTaskPresentation';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';

const props = defineProps<{
  task: TaskDisplayData;
  isLoading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'openMeetingModal', task: TaskDisplayData): void;
  (e: 'openCheckInDialog', task: TaskDisplayData): void;
  (e: 'openTaskDetail', task: TaskDisplayData): void;
  (e: 'update:completed', task: TaskDisplayData): void;
  (e: 'delete', task: TaskDisplayData): void;
}>();

const dateLocale = useDateLocale();

const canManuallyComplete = computed(() => props.task.can_be_manually_completed !== false);
const isPeriodicityGap = computed(() => isPeriodicityGapTask(props.task));
const isAgendaCreation = computed(() => isAgendaCreationTask(props.task));
const isInstitution = computed(() => isInstitutionTask(props.task));
const isOrphaned = computed(() => isOrphanedTask(props.task));

const meetingAgendaUrl = computed(() =>
  isAgendaTask(props.task) && isMeetingTask(props.task) ? getMeetingAgendaUrl(props.task) : null,
);
const taskableUrl = computed(() => (props.task.taskable?.name ? getTaskableUrl(props.task) : null));
const formattedDueDate = computed(() => formatTaskDueDate(props.task.due_date, dateLocale.value));

const actionIcon = computed(() => getTaskActionIcon(props.task.action_type));
const actionLabel = computed(() => getTaskActionLabel(props.task.action_type));
const actionTextClass = computed(() => getTaskActionTextClass(props.task.action_type));
const actionBackgroundClass = computed(() => getTaskActionBackgroundClass(props.task.action_type));
const progressStrokeClass = computed(() => getTaskProgressStrokeClass(props.task.action_type));

// Mirrors TaskTable: the endpoint's `can_delete` wins, with the controller's rule as fallback
// for the Show pages that hand over raw task models.
const canDelete = computed(() => {
  if (props.task.can_delete !== undefined) {
    return props.task.can_delete;
  }

  return canManuallyComplete.value || (usePage().props.auth?.user?.isSuperAdmin ?? false);
});

function handleComplete() {
  if (!canManuallyComplete.value) {
    toast.info($t('This task completes automatically'));

    return;
  }

  emit('update:completed', props.task);
}
</script>
