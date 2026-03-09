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
              :class="progressColor"
            />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-zinc-600 dark:text-zinc-400">
            {{ task.progress.percentage }}%
          </span>
        </div>

        <!-- Icon for auto-completing tasks without progress -->
        <div
          v-else-if="!canManuallyComplete"
          :class="['flex size-8 items-center justify-center rounded-lg', iconBgClass]"
        >
          <component
            :is="taskIcon"
            class="size-4"
            :class="iconColorClass"
          />
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
          <span
            v-if="!canManuallyComplete"
            :class="['font-medium', actionTypeColor]"
          >
            {{ actionTypeLabel }}
          </span>

          <!-- Taskable reference -->
          <Link
            v-if="task.taskable?.name"
            :href="taskableLink"
            class="text-zinc-500 hover:text-zinc-700 hover:underline dark:text-zinc-400 dark:hover:text-zinc-200"
          >
            {{ task.taskable.name }}
          </Link>

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
          <UsersAvatarGroup :users="(task.users as any)" :size="24" :max="4" />
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
              @click="$emit('openMeetingModal', task)"
            >
              <CalendarPlusIcon class="size-3" />
              {{ $t('Schedule') }}
            </Button>
            <Button
              size="sm"
              variant="outline"
              class="h-7 gap-1.5 text-xs text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
              @click="$emit('openCheckInDialog', task)"
            >
              <CalendarOffIcon class="size-3" />
              {{ $t('No meeting') }}
            </Button>
          </template>

          <!-- Agenda task quick actions -->
          <template v-if="isAgendaTask && !task.completed_at && isMeeting">
            <Link :href="meetingAgendaUrl">
              <Button
                size="sm"
                variant="outline"
                class="h-7 gap-1.5 text-xs"
              >
                <FilePlus2Icon v-if="isAgendaCreationTask" class="size-3" />
                <FileCheckIcon v-else class="size-3" />
                {{ isAgendaCreationTask ? $t('tasks.agenda.action_add_items') : $t('tasks.agenda.action_view_agenda') }}
              </Button>
            </Link>
          </template>

          <!-- View details button (info icon) -->
          <Button
            v-if="task.description"
            size="sm"
            variant="ghost"
            class="size-7 p-0 text-zinc-400 hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300"
            @click="$emit('openTaskDetail', task)"
          >
            <InfoIcon class="size-4" />
            <span class="sr-only">{{ $t('View Details') }}</span>
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
import { formatDistanceToNow, parseISO, isToday, isTomorrow, differenceInDays } from 'date-fns';
import { lt, enUS } from 'date-fns/locale';
import { toast } from 'vue-sonner';
import {
  CheckIcon,
  LoaderCircleIcon,
  InfoIcon,
  ShieldCheck as ShieldCheckIcon,
  Package as PackageIcon,
  PackageCheck as PackageCheckIcon,
  ClipboardCheck as ClipboardCheckIcon,
  Clock as ClockIcon,
  CalendarPlus as CalendarPlusIcon,
  CalendarOff as CalendarOffIcon,
  FilePlus2 as FilePlus2Icon,
  FileCheck as FileCheckIcon,
} from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { TaskActionType, type TaskProgress } from '@/Types/TaskTypes';

interface Task {
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

const props = defineProps<{
  task: Task;
  isLoading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'openMeetingModal', task: Task): void;
  (e: 'openCheckInDialog', task: Task): void;
  (e: 'openTaskDetail', task: Task): void;
  (e: 'update:completed', task: Task): void;
}>();

// Date locale
const dateLocale = computed(() => usePage().props.app?.locale === 'lt' ? lt : enUS);

// Computed properties
const canManuallyComplete = computed(() => props.task.can_be_manually_completed !== false);

const isPeriodicityGap = computed(() =>
  props.task.action_type === TaskActionType.PeriodicityGap || props.task.action_type === 'periodicity_gap',
);

const isAgendaTask = computed(() =>
  props.task.action_type === TaskActionType.AgendaCreation || 
  props.task.action_type === 'agenda_creation' ||
  props.task.action_type === TaskActionType.AgendaCompletion || 
  props.task.action_type === 'agenda_completion',
);

const isAgendaCreationTask = computed(() =>
  props.task.action_type === TaskActionType.AgendaCreation || props.task.action_type === 'agenda_creation',
);

const isInstitution = computed(() => props.task.taskable_type?.includes('Institution') ?? false);

const isMeeting = computed(() => props.task.taskable_type?.includes('Meeting') ?? false);

// URL for navigating to meeting agenda
const meetingAgendaUrl = computed(() => {
  if (!isMeeting.value || !props.task.taskable_id) return '#';
  const baseUrl = route('meetings.show', props.task.taskable_id);
  const params = new URLSearchParams({ tab: 'agenda' });
  if (isAgendaCreationTask.value) params.set('action', 'add');
  return `${baseUrl}?${params.toString()}`;
});

// Format due date
const formattedDueDate = computed(() => {
  if (!props.task.due_date) return '';
  try {
    const date = parseISO(props.task.due_date);
    if (isToday(date)) return $t('Today');
    if (isTomorrow(date)) return $t('Tomorrow');
    const daysUntil = differenceInDays(date, new Date());
    if (Math.abs(daysUntil) <= 7) {
      return formatDistanceToNow(date, { addSuffix: true, locale: dateLocale.value });
    }
    return props.task.due_date.split('T')[0];
  }
  catch {
    return props.task.due_date;
  }
});

// Task icon based on action type
const taskIcon = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return ShieldCheckIcon;
    case TaskActionType.Pickup:
    case 'pickup':
      return PackageIcon;
    case TaskActionType.Return:
    case 'return':
      return PackageCheckIcon;
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return ClockIcon;
    case TaskActionType.AgendaCreation:
    case 'agenda_creation':
      return FilePlus2Icon;
    case TaskActionType.AgendaCompletion:
    case 'agenda_completion':
      return FileCheckIcon;
    default:
      return ClipboardCheckIcon;
  }
});

// Icon background class
const iconBgClass = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'bg-blue-100 dark:bg-blue-900/40';
    case TaskActionType.Pickup:
    case 'pickup':
      return 'bg-amber-100 dark:bg-amber-900/40';
    case TaskActionType.Return:
    case 'return':
      return 'bg-emerald-100 dark:bg-emerald-900/40';
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return 'bg-orange-100 dark:bg-orange-900/40';
    case TaskActionType.AgendaCreation:
    case 'agenda_creation':
      return 'bg-violet-100 dark:bg-violet-900/40';
    case TaskActionType.AgendaCompletion:
    case 'agenda_completion':
      return 'bg-emerald-100 dark:bg-emerald-900/40';
    default:
      return 'bg-zinc-100 dark:bg-zinc-800';
  }
});

// Icon color class
const iconColorClass = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'text-blue-600 dark:text-blue-400';
    case TaskActionType.Pickup:
    case 'pickup':
      return 'text-amber-600 dark:text-amber-400';
    case TaskActionType.Return:
    case 'return':
      return 'text-emerald-600 dark:text-emerald-400';
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return 'text-orange-600 dark:text-orange-400';
    case TaskActionType.AgendaCreation:
    case 'agenda_creation':
      return 'text-violet-600 dark:text-violet-400';
    case TaskActionType.AgendaCompletion:
    case 'agenda_completion':
      return 'text-green-600 dark:text-green-400';
    default:
      return 'text-zinc-600 dark:text-zinc-400';
  }
});

// Progress ring color
const progressColor = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'text-blue-500 dark:text-blue-400';
    case TaskActionType.Pickup:
    case 'pickup':
      return 'text-amber-500 dark:text-amber-400';
    case TaskActionType.Return:
    case 'return':
      return 'text-emerald-500 dark:text-emerald-400';
    default:
      return 'text-primary';
  }
});

// Action type color
const actionTypeColor = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'text-blue-600 dark:text-blue-400';
    case TaskActionType.Pickup:
    case 'pickup':
      return 'text-amber-600 dark:text-amber-400';
    case TaskActionType.Return:
    case 'return':
      return 'text-emerald-600 dark:text-emerald-400';
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return 'text-orange-600 dark:text-orange-400';
    default:
      return 'text-zinc-600 dark:text-zinc-400';
  }
});

// Action type label
const actionTypeLabel = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return $t('Auto: approval');
    case TaskActionType.Pickup:
    case 'pickup':
      return $t('Auto: pickup');
    case TaskActionType.Return:
    case 'return':
      return $t('Auto: return');
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return $t('Auto: periodicity');
    default:
      return '';
  }
});

// Taskable link
const taskableLink = computed(() => {
  if (!props.task.taskable) return '#';
  const type = props.task.taskable_type.split('\\').pop()?.toLowerCase();
  switch (type) {
    case 'meeting':
      return route('meetings.show', props.task.taskable_id);
    case 'reservation':
      return route('reservations.show', props.task.taskable_id);
    case 'institution':
      return route('institutions.show', props.task.taskable_id);
    default:
      return '#';
  }
});

// Handle completion
function handleComplete() {
  if (!canManuallyComplete.value) {
    toast.info($t('This task completes automatically'));
    return;
  }
  emit('update:completed', props.task);
}
</script>
