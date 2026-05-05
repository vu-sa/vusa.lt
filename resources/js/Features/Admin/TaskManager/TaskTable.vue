<template>
  <SimpleDataTable
    :columns
    :data="tasks"
    :row-class-name
    :enable-pagination="true"
    :page-size="15"
    :empty-message="$t('No tasks found.')"
    :empty-icon="CheckIcon"
  >
    <template #empty>
      <div class="flex flex-col items-center justify-center gap-3 py-8 text-zinc-400">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
          <CheckCircleIcon class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
        </div>
        <div class="text-center">
          <p class="font-medium text-zinc-900 dark:text-zinc-100">
            {{ $t('Viskas atlikta!') }}
          </p>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('No tasks found.') }}
          </p>
        </div>
      </div>
    </template>
  </SimpleDataTable>
</template>

<script setup lang="tsx">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ref, defineAsyncComponent } from 'vue';
import {
  CheckIcon,
  CalendarIcon,
  MoreHorizontalIcon,
  TrashIcon,
  CheckCircleIcon,
  ShieldCheckIcon,
  PackageIcon,
  PackageCheckIcon,
  ClipboardCheckIcon,
  AlertCircleIcon,
  RotateCwIcon,
  CalendarPlusIcon,
  CalendarOffIcon,
  ClockIcon,
  InfoIcon,
  FilePlus2Icon,
  FileCheckIcon,
  ExternalLinkIcon,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { format, formatDistanceToNow, parseISO, differenceInDays } from 'date-fns';
import { lt, enUS } from 'date-fns/locale';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import IconsFilled from '@/Types/Icons/filled';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { TaskActionType, type TaskProgress } from '@/Types/TaskTypes';

// Lazy load modals
const NewMeetingDialog = defineAsyncComponent(() => import('@/Components/Dialogs/NewMeetingDialog.vue'));
const AddCheckInDialog = defineAsyncComponent(() => import('@/Components/Institutions/AddCheckInDialog.vue'));

// Enhanced Task interface with new backend fields
interface Task {
  id: string;
  name: string;
  description?: string | null;
  completed_at: string | null;
  due_date: string | null;
  taskable_type: string;
  taskable_id: string;
  taskable?: {
    id: string;
    name?: string;
    type?: string;
  } | null;
  users?: {
    id: string;
    name: string;
    profile_photo_path?: string;
  }[];
  // New backend computed fields
  action_type?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
  icon?: string;
  color?: string;
}

const props = defineProps<{
  tasks: Task[];
}>();

const emit = defineEmits<{
  (e: 'openMeetingModal', task: Task): void;
  (e: 'openCheckInDialog', task: Task): void;
  (e: 'openTaskDetail', task: Task): void;
}>();

// Track loading state per task
const loadingTaskId = ref<string | null>(null);

// Get locale for date formatting
const getDateLocale = () => usePage().props.app?.locale === 'lt' ? lt : enUS;

/**
 * Handle row styling based on task status
 */
const rowClassName = (row: Task) => {
  if (row.completed_at !== null) {
    return 'opacity-60 bg-zinc-50/30 dark:bg-zinc-900/20';
  }
  if (row.is_overdue) {
    return 'bg-red-50/20 dark:bg-red-950/5';
  }
  return '';
};

/**
 * Get action type icon component
 */
const getActionTypeIcon = (actionType: TaskActionType | string | null | undefined) => {
  switch (actionType) {
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
};

/**
 * Get action type color classes
 */
const getActionTypeClasses = (actionType: TaskActionType | string | null | undefined) => {
  switch (actionType) {
    case TaskActionType.Approval:
    case 'approval':
      return 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
    case TaskActionType.Pickup:
    case 'pickup':
      return 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
    case TaskActionType.Return:
    case 'return':
      return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400';
    case TaskActionType.AgendaCreation:
    case 'agenda_creation':
      return 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400';
    case TaskActionType.AgendaCompletion:
    case 'agenda_completion':
      return 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400';
    default:
      return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
  }
};

/**
 * Check if task is a periodicity gap task
 */
const isPeriodicityGapTask = (task: Task): boolean => {
  return task.action_type === TaskActionType.PeriodicityGap || task.action_type === 'periodicity_gap';
};

/**
 * Check if task is an agenda-related task
 */
const isAgendaTask = (task: Task): boolean => {
  return task.action_type === TaskActionType.AgendaCreation
    || task.action_type === 'agenda_creation'
    || task.action_type === TaskActionType.AgendaCompletion
    || task.action_type === 'agenda_completion';
};

/**
 * Check if task is an agenda creation task
 */
const isAgendaCreationTask = (task: Task): boolean => {
  return task.action_type === TaskActionType.AgendaCreation || task.action_type === 'agenda_creation';
};

/**
 * Check if task is a meeting-based task
 */
const isMeetingTask = (task: Task): boolean => {
  return task.taskable_type?.includes('Meeting') ?? false;
};

/**
 * Check if task is institution-based (for periodicity gap actions)
 */
const isInstitutionTask = (task: Task): boolean => {
  return task.taskable_type?.includes('Institution') ?? false;
};

/**
 * Get URL for navigating to meeting agenda tab with optional add action
 */
const getMeetingAgendaUrl = (task: Task, action?: 'add'): string => {
  if (!isMeetingTask(task) || !task.taskable_id) return '#';
  const baseUrl = route('meetings.show', task.taskable_id);
  const params = new URLSearchParams({ tab: 'agenda' });
  if (action) params.set('action', action);
  return `${baseUrl}?${params.toString()}`;
};

/**
 * Format due date with relative display
 */
const formatDueDate = (dateString: string | null, isOverdue?: boolean) => {
  if (!dateString) return null;

  const date = parseISO(dateString);
  const daysUntil = differenceInDays(date, new Date());

  // For dates within a week, show relative
  if (Math.abs(daysUntil) <= 7) {
    return formatDistanceToNow(date, {
      addSuffix: true,
      locale: getDateLocale(),
    });
  }

  return format(date, 'yyyy-MM-dd');
};

/**
 * Get due date badge variant and classes
 */
const getDueDateClasses = (task: Task) => {
  if (task.is_overdue) {
    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
  }

  if (task.due_date) {
    const daysUntil = differenceInDays(parseISO(task.due_date), new Date());
    if (daysUntil <= 3 && daysUntil >= 0) {
      return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    }
  }

  return '';
};

/**
 * Update task completion status
 */
const updateTaskCompletion = (task: Task) => {
  // Don't allow completing auto-completing tasks
  if (task.can_be_manually_completed === false) {
    toast.info($t('This task completes automatically'), {
      description: $t('You cannot manually complete this task'),
    });
    return;
  }

  if (loadingTaskId.value) return;
  loadingTaskId.value = task.id;

  const newCompletionState = task.completed_at === null;
  const previousState = task.completed_at;

  // Optimistic update
  task.completed_at = newCompletionState ? new Date().toISOString() : null;

  router.post(
    route('tasks.updateCompletionStatus', task.id),
    { completed: newCompletionState },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        loadingTaskId.value = null;
        if (newCompletionState) {
          toast.success($t('Task marked as completed'), { description: task.name });
        }
        else {
          toast.info($t('Task marked as incomplete'), { description: task.name });
        }
      },
      onError: () => {
        loadingTaskId.value = null;
        task.completed_at = previousState;
        toast.error($t('Failed to update task status'), {
          description: $t('Please try again'),
        });
      },
    },
  );
};

/**
 * Delete a task
 */
const handleDelete = (task: Task) => {
  if (loadingTaskId.value) return;
  loadingTaskId.value = task.id;

  router.delete(route('tasks.destroy', task.id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      loadingTaskId.value = null;
      toast.success($t('Task deleted successfully'), { description: task.name });
    },
    onError: (errors: any) => {
      loadingTaskId.value = null;
      toast.error($t('Failed to delete task'), {
        description: errors.message || $t('Please try again'),
      });
    },
  });
};

/**
 * Get taskable icon based on type
 */
const getTaskableIcon = (taskableType: string) => {
  // Handle both full class path and short class name
  const typeName = taskableType.includes('\\')
    ? taskableType.split('\\').pop()
    : taskableType;

  switch (typeName) {
    case 'Meeting': return IconsFilled.MEETING;
    case 'User': return IconsFilled.USER;
    case 'Reservation': return IconsFilled.RESERVATION;
    default: return IconsFilled.HOME;
  }
};

/**
 * Get model route based on taskable type
 */
const getModelRoute = (taskableType: string) => {
  const typeName = taskableType.includes('\\')
    ? taskableType.split('\\').pop()
    : taskableType;
  return (`${typeName}s`).toLowerCase();
};

/**
 * Table column definitions
 */
const columns = [
  // Status column - checkbox or progress indicator
  {
    id: 'status',
    header: '',
    cell: ({ row }) => {
      const task = row.original;
      const isLoading = loadingTaskId.value === task.id;
      const canComplete = task.users?.find(
        user => user.id === usePage().props.auth?.user?.id,
      );
      const canManuallyComplete = task.can_be_manually_completed !== false;

      // Loading state
      if (isLoading) {
        return (
          <div class="flex justify-center">
            <RotateCwIcon class="h-4 w-4 animate-spin text-zinc-400" />
          </div>
        );
      }

      // Progress indicator for auto-completing tasks
      if (!canManuallyComplete && task.progress) {
        return (
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <div class="relative flex h-6 w-6 items-center justify-center">
                  <svg class="h-6 w-6 -rotate-90" viewBox="0 0 24 24">
                    <circle
                      cx="12"
                      cy="12"
                      r="10"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      class="text-zinc-200 dark:text-zinc-700"
                    />
                    <circle
                      cx="12"
                      cy="12"
                      r="10"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-dasharray={`${(task.progress.percentage / 100) * 62.8} 62.8`}
                      class={task.action_type === 'return' || task.action_type === TaskActionType.Return
                        ? 'text-emerald-500'
                        : task.action_type === 'pickup' || task.action_type === TaskActionType.Pickup
                          ? 'text-amber-500'
                          : 'text-blue-500'}
                    />
                  </svg>
                  <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold text-zinc-600 dark:text-zinc-400">
                    {task.progress.percentage}
                  </span>
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>
                  {task.progress.current}
                  /
                  {task.progress.total}
                  {' '}
                  {$t('completed')}
                </p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        );
      }

      // Icon for auto-completing tasks without progress
      if (!canManuallyComplete) {
        const ActionIcon = getActionTypeIcon(task.action_type);
        return (
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <div class={`flex h-6 w-6 items-center justify-center rounded-md ${getActionTypeClasses(task.action_type)}`}>
                  <ActionIcon class="h-3.5 w-3.5" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>{$t('This task completes automatically')}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        );
      }

      // Standard checkbox for manual tasks
      return (
        <div class="flex justify-center">
          <Checkbox
            modelValue={task.completed_at !== null}
            disabled={!canComplete || isLoading}
            onUpdate:modelValue={() => updateTaskCompletion(task)}
            class="transition-all duration-200 hover:scale-110"
          />
        </div>
      );
    },
    size: 48,
  },
  // Task name with action type indicator
  {
    accessorKey: 'name',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => {
      const task = row.original;
      const isCompleted = task.completed_at !== null;
      const hasDescription = !!task.description;

      // If task has description, wrap in HoverCard for preview
      if (hasDescription) {
        return (
          <div class="min-w-0 flex-1">
            <HoverCard openDelay={300}>
              <HoverCardTrigger asChild>
                <button
                  type="button"
                  class={`group/name flex max-w-full items-center gap-1.5 text-left ${isCompleted ? 'line-through text-zinc-500' : 'text-zinc-900 dark:text-zinc-100'}`}
                  onClick={() => emit('openTaskDetail', task)}
                >
                  <span class="truncate text-sm font-medium group-hover/name:underline" title={task.name}>
                    {task.name}
                  </span>
                </button>
              </HoverCardTrigger>
              <HoverCardContent class="w-80" side="top" align="start">
                <div class="space-y-2">
                  <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{task.name}</p>
                  <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">{task.description}</p>
                </div>
              </HoverCardContent>
            </HoverCard>
          </div>
        );
      }

      // No description - just show name with tooltip for overflow
      return (
        <div class="min-w-0 flex-1">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <button
                  type="button"
                  class={`group/name flex max-w-full items-center gap-1.5 text-left ${isCompleted ? 'line-through text-zinc-500' : 'text-zinc-900 dark:text-zinc-100'}`}
                  onClick={() => emit('openTaskDetail', task)}
                >
                  <span class="truncate text-sm font-medium group-hover/name:underline">
                    {task.name}
                  </span>
                </button>
              </TooltipTrigger>
              <TooltipContent side="top" align="start">
                <p>{task.name}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      );
    },
    size: 280,
  },
  // Subject (taskable) with icon
  {
    accessorKey: 'subject',
    header: () => $t('forms.fields.subject'),
    cell: ({ row }) => {
      const task = row.original;
      if (!task.taskable) return <span class="text-zinc-400">—</span>;

      const modelRoute = getModelRoute(task.taskable_type);
      const Icon = getTaskableIcon(task.taskable_type);
      const displayName = task.taskable.name || task.taskable_type;

      return (
        <Link href={route(`${modelRoute}.show`, task.taskable_id)}>
          <Badge variant="outline" class="inline-flex max-w-[160px] items-center gap-1.5 font-normal hover:bg-zinc-100 dark:hover:bg-zinc-800">
            <Icon class="h-3 w-3 shrink-0" />
            <span class="truncate">{displayName}</span>
          </Badge>
        </Link>
      );
    },
    size: 180,
  },
  // Responsible users
  {
    accessorKey: 'users',
    header: () => $t('forms.fields.responsible_people'),
    cell: ({ row }) => (
      <UsersAvatarGroup size={28} users={row.original.users || []} max={3} />
    ),
    size: 120,
  },
  // Due date with status badge
  {
    accessorKey: 'due_date',
    header: () => $t('forms.fields.due_date'),
    cell: ({ row }) => {
      const task = row.original;
      if (!task.due_date) return <span class="text-zinc-400">—</span>;

      const dueDateClasses = getDueDateClasses(task);
      const formattedDate = formatDueDate(task.due_date, task.is_overdue);

      return (
        <div class="flex items-center gap-2">
          {task.is_overdue && <AlertCircleIcon class="h-3.5 w-3.5 shrink-0 text-red-500" />}
          <Badge
            variant={task.is_overdue ? 'destructive' : 'secondary'}
            class={`text-xs font-medium ${dueDateClasses}`}
          >
            {formattedDate}
          </Badge>
        </div>
      );
    },
    size: 140,
  },
  // Quick actions for periodicity gap tasks and agenda tasks
  {
    id: 'quick_actions',
    header: '',
    cell: ({ row }) => {
      const task = row.original;
      const isCompleted = task.completed_at !== null;

      // Show quick actions for uncompleted periodicity gap tasks (institution-based)
      if (isPeriodicityGapTask(task) && !isCompleted && isInstitutionTask(task)) {
        return (
          <div class="flex items-center gap-1">
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-zinc-600 hover:text-emerald-700 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-emerald-400 dark:hover:bg-zinc-800"
                    onClick={() => emit('openMeetingModal', task)}
                  >
                    <CalendarPlusIcon class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{$t('tasks.periodicity_gap.action_schedule_meeting')}</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-zinc-600 hover:text-amber-700 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:text-amber-400 dark:hover:bg-zinc-800"
                    onClick={() => emit('openCheckInDialog', task)}
                  >
                    <CalendarOffIcon class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{$t('tasks.periodicity_gap.action_report_no_meeting')}</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        );
      }

      // Show quick actions for uncompleted agenda tasks (meeting-based)
      if (isAgendaTask(task) && !isCompleted && isMeetingTask(task)) {
        const isCreation = isAgendaCreationTask(task);
        const agendaUrl = getMeetingAgendaUrl(task, isCreation ? 'add' : undefined);

        return (
          <div class="flex items-center gap-1">
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <Link href={agendaUrl}>
                    <Button
                      variant="ghost"
                      size="icon"
                      class={`h-7 w-7 text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 ${
                        isCreation
                          ? 'hover:text-violet-700 dark:hover:text-violet-400'
                          : 'hover:text-green-700 dark:hover:text-green-400'
                      }`}
                    >
                      {isCreation
                        ? <FilePlus2Icon class="h-4 w-4" />
                        : <FileCheckIcon class="h-4 w-4" />}
                    </Button>
                  </Link>
                </TooltipTrigger>
                <TooltipContent>
                  <p>{isCreation ? $t('tasks.agenda.action_add_items') : $t('tasks.agenda.action_view_agenda')}</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        );
      }

      return null;
    },
    size: 80,
  },
  // Actions dropdown
  {
    id: 'actions',
    cell: ({ row }) => {
      const task = row.original;
      const canManuallyComplete = task.can_be_manually_completed !== false;
      const isCompleted = task.completed_at !== null;
      const hasDescription = !!task.description;

      return (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 data-[state=open]:opacity-100">
              <MoreHorizontalIcon class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => emit('openTaskDetail', task)}>
              <InfoIcon class="mr-2 h-4 w-4" />
              <span>{$t('View Details')}</span>
            </DropdownMenuItem>
            {(canManuallyComplete || hasDescription) && <DropdownMenuSeparator />}
            {canManuallyComplete && !isCompleted && (
              <DropdownMenuItem onClick={() => updateTaskCompletion(task)}>
                <CheckIcon class="mr-2 h-4 w-4" />
                <span>{$t('Mark Complete')}</span>
              </DropdownMenuItem>
            )}
            <DropdownMenuItem onClick={() => handleDelete(task)} class="text-destructive focus:text-destructive">
              <TrashIcon class="mr-2 h-4 w-4" />
              <span>{$t('Delete')}</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      );
    },
    size: 50,
  },
];
</script>
