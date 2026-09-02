<template>
  <SimpleDataTable
    :columns
    :data="tasks"
    :row-class-name
    :enable-pagination
    :enable-filtering
    :page-size
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
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  AlertCircleIcon,
  CalendarOffIcon,
  CalendarPlusIcon,
  CheckCircleIcon,
  CheckIcon,
  FileCheckIcon,
  FilePlus2Icon,
  InfoIcon,
  LinkIcon,
  MoreHorizontalIcon,
  RotateCwIcon,
  TrashIcon,
} from 'lucide-vue-next';

import { useDateLocale } from '@/Composables/useDateLocale';
import {
  formatTaskDueDate,
  getDueDateUrgencyClasses,
  getMeetingAgendaUrl,
  getTaskActionBadgeClasses,
  getTaskActionIcon,
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
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/Components/ui/hover-card';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { HomeIconFilled, MeetingIconFilled, ReservationIconFilled, UserIconFilled } from '@/Components/icons';

const props = withDefaults(defineProps<{
  tasks: TaskDisplayData[];
  /** The task currently being written to, so its row can show a spinner instead of a control. */
  loadingTaskId?: string | null;
  /** Off when the page around the table already paginates server-side. */
  enablePagination?: boolean;
  enableFiltering?: boolean;
  pageSize?: number;
}>(), {
  loadingTaskId: null,
  enablePagination: true,
  enableFiltering: true,
  pageSize: 15,
});

const emit = defineEmits<{
  (e: 'openMeetingModal', task: TaskDisplayData): void;
  (e: 'openCheckInDialog', task: TaskDisplayData): void;
  (e: 'openTaskDetail', task: TaskDisplayData): void;
  (e: 'update:completed', task: TaskDisplayData): void;
  (e: 'delete', task: TaskDisplayData): void;
}>();

const dateLocale = useDateLocale();

const currentUserId = () => usePage().props.auth?.user?.id;

// An automatic task is the system's to close; only a super admin may force one away when it
// has become unclosable. The backend enforces the same rule.
const isSuperAdmin = () => usePage().props.auth?.user?.isSuperAdmin ?? false;

/**
 * `can_delete` is authoritative when the endpoint sends it. The Show pages hand raw task models
 * to the table, so fall back to the rule the controller applies for those.
 */
const canDeleteTask = (task: TaskDisplayData): boolean => {
  if (task.can_delete !== undefined) {
    return task.can_delete;
  }

  return task.can_be_manually_completed !== false || isSuperAdmin();
};

/**
 * `group` is what makes the row-hover reveal of the actions menu work — TableRow adds no
 * grouping class of its own, so without it the trigger stayed at its base opacity forever.
 */
const rowClassName = (row: TaskDisplayData) => {
  if (row.completed_at) {
    return 'group opacity-60 bg-zinc-50/30 dark:bg-zinc-900/20';
  }
  if (row.is_overdue) {
    return 'group bg-rose-50/20 dark:bg-rose-950/5';
  }

  return 'group';
};

const getTaskableIcon = (taskableType: string) => {
  switch (taskableType) {
    case 'meeting': return MeetingIconFilled;
    case 'user': return UserIconFilled;
    case 'reservation': return ReservationIconFilled;
    default: return HomeIconFilled;
  }
};

const columns = [
  // Status column - checkbox or progress indicator
  {
    id: 'status',
    header: '',
    cell: ({ row }) => {
      const task: TaskDisplayData = row.original;
      const isLoading = props.loadingTaskId === task.id;
      const isAssignedToCurrentUser = task.users?.some(user => user.id === currentUserId());
      const canManuallyComplete = task.can_be_manually_completed !== false;

      if (isLoading) {
        return (
          <div class="flex justify-center">
            <RotateCwIcon class="h-4 w-4 animate-spin text-zinc-400" />
          </div>
        );
      }

      // Progress ring for auto-completing tasks that track item counts
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
                      class={getTaskProgressStrokeClass(task.action_type)}
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

      if (!canManuallyComplete) {
        const ActionIcon = getTaskActionIcon(task.action_type);

        return (
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <div class={`flex h-6 w-6 items-center justify-center rounded-md ${getTaskActionBadgeClasses(task.action_type)}`}>
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

      return (
        <div class="flex justify-center">
          <Checkbox
            modelValue={task.completed_at !== null}
            disabled={!isAssignedToCurrentUser || isLoading}
            onUpdate:modelValue={() => emit('update:completed', task)}
            class="transition-all duration-200 hover:scale-110"
          />
        </div>
      );
    },
    size: 48,
  },
  // Task name, with a description preview when there is one
  {
    accessorKey: 'name',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => {
      const task: TaskDisplayData = row.original;
      const nameClasses = `group/name flex max-w-full items-center gap-1.5 text-left ${
        task.completed_at ? 'line-through text-zinc-500' : 'text-zinc-900 dark:text-zinc-100'
      }`;

      const trigger = (
        <button
          type="button"
          class={nameClasses}
          onClick={() => emit('openTaskDetail', task)}
        >
          <span class="truncate text-sm font-medium group-hover/name:underline" title={task.name}>
            {task.name}
          </span>
        </button>
      );

      if (task.description) {
        return (
          <div class="min-w-0 flex-1">
            <HoverCard openDelay={300}>
              <HoverCardTrigger asChild>{trigger}</HoverCardTrigger>
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

      return (
        <div class="min-w-0 flex-1">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>{trigger}</TooltipTrigger>
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
  // Subject (taskable)
  {
    accessorKey: 'subject',
    header: () => $t('forms.fields.subject'),
    cell: ({ row }) => {
      const task: TaskDisplayData = row.original;

      // A task outlives its subject when the subject is hard-deleted; say so instead of
      // rendering a dash, so the row can be recognised as one worth clearing away.
      if (isOrphanedTask(task)) {
        return (
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <Badge variant="outline" class="inline-flex max-w-[160px] items-center gap-1.5 font-normal text-zinc-500 dark:text-zinc-400">
                  <LinkIcon class="h-3 w-3 shrink-0" />
                  <span class="truncate">{$t('tasks.orphaned')}</span>
                </Badge>
              </TooltipTrigger>
              <TooltipContent>
                <p>{$t('tasks.orphaned_description')}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        );
      }

      const Icon = getTaskableIcon(task.taskable_type);
      const displayName = task.taskable?.name || task.taskable_type;
      const url = getTaskableUrl(task);

      const badge = (
        <Badge variant="outline" class="inline-flex max-w-[160px] items-center gap-1.5 font-normal hover:bg-zinc-100 dark:hover:bg-zinc-800">
          <Icon class="h-3 w-3 shrink-0" />
          <span class="truncate">{displayName}</span>
        </Badge>
      );

      return url ? <Link href={url}>{badge}</Link> : badge;
    },
    size: 180,
  },
  {
    accessorKey: 'users',
    header: () => $t('forms.fields.responsible_people'),
    cell: ({ row }) => (
      <UsersAvatarGroup size={28} users={row.original.users || []} max={3} />
    ),
    size: 120,
  },
  {
    accessorKey: 'due_date',
    header: () => $t('forms.fields.due_date'),
    cell: ({ row }) => {
      const task: TaskDisplayData = row.original;
      if (!task.due_date) {
        return <span class="text-zinc-400">—</span>;
      }

      return (
        <div class="flex items-center gap-2">
          {task.is_overdue && <AlertCircleIcon class="h-3.5 w-3.5 shrink-0 text-rose-500" />}
          <Badge
            variant={task.is_overdue ? 'rose' : 'secondary'}
            class={`text-xs font-medium ${getDueDateUrgencyClasses(task)}`}
          >
            {formatTaskDueDate(task.due_date, dateLocale.value)}
          </Badge>
        </div>
      );
    },
    size: 140,
  },
  // Quick actions for periodicity gap and agenda tasks
  {
    id: 'quick_actions',
    header: '',
    cell: ({ row }) => {
      const task: TaskDisplayData = row.original;
      if (task.completed_at) {
        return null;
      }

      if (isPeriodicityGapTask(task) && isInstitutionTask(task)) {
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

      const agendaUrl = isAgendaTask(task) && isMeetingTask(task) ? getMeetingAgendaUrl(task) : null;

      if (agendaUrl) {
        const isCreation = isAgendaCreationTask(task);

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
      const task: TaskDisplayData = row.original;
      const canManuallyComplete = task.can_be_manually_completed !== false;
      const canComplete = canManuallyComplete && !task.completed_at;
      const canDelete = canDeleteTask(task);

      return (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              aria-label={$t('tables.open_menu')}
              class="h-8 w-8 p-0 opacity-60 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 data-[state=open]:opacity-100"
            >
              <MoreHorizontalIcon class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => emit('openTaskDetail', task)}>
              <InfoIcon class="mr-2 h-4 w-4" />
              <span>{$t('View Details')}</span>
            </DropdownMenuItem>
            {(canComplete || canDelete) && <DropdownMenuSeparator />}
            {canComplete && (
              <DropdownMenuItem onClick={() => emit('update:completed', task)}>
                <CheckIcon class="mr-2 h-4 w-4" />
                <span>{$t('Mark Complete')}</span>
              </DropdownMenuItem>
            )}
            {canDelete && (
              <DropdownMenuItem onClick={() => emit('delete', task)} class="text-destructive focus:text-destructive">
                <TrashIcon class="mr-2 h-4 w-4" />
                <span>{canManuallyComplete ? $t('Delete') : $t('tasks.delete_automatic')}</span>
              </DropdownMenuItem>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      );
    },
    size: 50,
  },
];
</script>
