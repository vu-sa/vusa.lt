import { trans as $t } from 'laravel-vue-i18n';
import {
  ShieldCheck,
  Package,
  PackageCheck,
  ClipboardCheck,
  Clock,
  FilePlus2,
  FileCheck,
  type LucideIcon,
} from 'lucide-vue-next';
import { differenceInDays, format, formatDistanceToNow, isToday, isTomorrow, parseISO } from 'date-fns';
import type { Locale } from 'date-fns';

import { TaskActionType, type TaskProgress } from '@/Types/TaskTypes';

/**
 * Shared presentation rules for a task. TaskTable (desktop) and TaskCard (mobile) render the
 * same task in two shapes, so every action-type switch lives here rather than in both.
 */

/** The shape both TaskTable and TaskCard receive from the task endpoints. */
export interface TaskDisplayData {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  action_type?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
  can_delete?: boolean;
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

/** Aggregate counts the task pages show above the list. */
export interface TaskStats {
  total: number;
  completed: number;
  overdue: number;
  autoCompleting: number;
}

type ActionType = TaskActionType | string | null | undefined;

/** Colour family each action type is drawn in. */
type ActionPalette = 'blue' | 'amber' | 'emerald' | 'orange' | 'violet' | 'green' | 'zinc';

interface ActionTypePresentation {
  icon: LucideIcon;
  palette: ActionPalette;
  /** Empty for types that carry no "Auto: …" label. */
  labelKey: string;
}

const ACTION_TYPE_PRESENTATION: Record<TaskActionType, ActionTypePresentation> = {
  [TaskActionType.Approval]: { icon: ShieldCheck, palette: 'blue', labelKey: 'Auto: approval' },
  [TaskActionType.Pickup]: { icon: Package, palette: 'amber', labelKey: 'Auto: pickup' },
  [TaskActionType.Return]: { icon: PackageCheck, palette: 'emerald', labelKey: 'Auto: return' },
  [TaskActionType.PeriodicityGap]: { icon: Clock, palette: 'orange', labelKey: 'Auto: periodicity' },
  [TaskActionType.AgendaCreation]: { icon: FilePlus2, palette: 'violet', labelKey: '' },
  [TaskActionType.AgendaCompletion]: { icon: FileCheck, palette: 'green', labelKey: '' },
  [TaskActionType.Manual]: { icon: ClipboardCheck, palette: 'zinc', labelKey: '' },
};

const FALLBACK_PRESENTATION: ActionTypePresentation = {
  icon: ClipboardCheck,
  palette: 'zinc',
  labelKey: '',
};

const PALETTE_CLASSES: Record<ActionPalette, { badge: string; background: string; text: string; stroke: string }> = {
  blue: {
    badge: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    background: 'bg-blue-100 dark:bg-blue-900/40',
    text: 'text-blue-600 dark:text-blue-400',
    stroke: 'text-blue-500 dark:text-blue-400',
  },
  amber: {
    badge: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    background: 'bg-amber-100 dark:bg-amber-900/40',
    text: 'text-amber-600 dark:text-amber-400',
    stroke: 'text-amber-500 dark:text-amber-400',
  },
  emerald: {
    badge: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    background: 'bg-emerald-100 dark:bg-emerald-900/40',
    text: 'text-emerald-600 dark:text-emerald-400',
    stroke: 'text-emerald-500 dark:text-emerald-400',
  },
  orange: {
    badge: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    background: 'bg-orange-100 dark:bg-orange-900/40',
    text: 'text-orange-600 dark:text-orange-400',
    stroke: 'text-orange-500 dark:text-orange-400',
  },
  violet: {
    badge: 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
    background: 'bg-violet-100 dark:bg-violet-900/40',
    text: 'text-violet-600 dark:text-violet-400',
    stroke: 'text-violet-500 dark:text-violet-400',
  },
  green: {
    badge: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    background: 'bg-green-100 dark:bg-green-900/40',
    text: 'text-green-600 dark:text-green-400',
    stroke: 'text-green-500 dark:text-green-400',
  },
  zinc: {
    badge: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    background: 'bg-zinc-100 dark:bg-zinc-800',
    text: 'text-zinc-600 dark:text-zinc-400',
    stroke: 'text-primary',
  },
};

function presentationFor(actionType: ActionType): ActionTypePresentation {
  if (!actionType) {
    return FALLBACK_PRESENTATION;
  }

  return ACTION_TYPE_PRESENTATION[actionType as TaskActionType] ?? FALLBACK_PRESENTATION;
}

export function getTaskActionIcon(actionType: ActionType): LucideIcon {
  return presentationFor(actionType).icon;
}

/** Icon + background in one class string, for the square action badge. */
export function getTaskActionBadgeClasses(actionType: ActionType): string {
  return PALETTE_CLASSES[presentationFor(actionType).palette].badge;
}

export function getTaskActionBackgroundClass(actionType: ActionType): string {
  return PALETTE_CLASSES[presentationFor(actionType).palette].background;
}

export function getTaskActionTextClass(actionType: ActionType): string {
  return PALETTE_CLASSES[presentationFor(actionType).palette].text;
}

export function getTaskProgressStrokeClass(actionType: ActionType): string {
  return PALETTE_CLASSES[presentationFor(actionType).palette].stroke;
}

/** Empty string for action types that are not labelled as automatic in the UI. */
export function getTaskActionLabel(actionType: ActionType): string {
  const { labelKey } = presentationFor(actionType);

  return labelKey ? $t(labelKey) : '';
}

export function isPeriodicityGapTask(task: Pick<TaskDisplayData, 'action_type'>): boolean {
  return task.action_type === TaskActionType.PeriodicityGap;
}

export function isAgendaCreationTask(task: Pick<TaskDisplayData, 'action_type'>): boolean {
  return task.action_type === TaskActionType.AgendaCreation;
}

export function isAgendaTask(task: Pick<TaskDisplayData, 'action_type'>): boolean {
  return isAgendaCreationTask(task) || task.action_type === TaskActionType.AgendaCompletion;
}

export function isInstitutionTask(task: Pick<TaskDisplayData, 'taskable_type'>): boolean {
  return task.taskable_type === 'institution';
}

export function isMeetingTask(task: Pick<TaskDisplayData, 'taskable_type'>): boolean {
  return task.taskable_type === 'meeting';
}

/**
 * A task whose subject was deleted underneath it. Automatic tasks in this state can no longer
 * complete themselves, which is why only a super admin can clear them away.
 */
export function isOrphanedTask(task: Pick<TaskDisplayData, 'taskable'>): boolean {
  return !task.taskable;
}

/** Link to the task's subject, or null when there is nothing left to link to. */
export function getTaskableUrl(task: Pick<TaskDisplayData, 'taskable' | 'taskable_type' | 'taskable_id'>): string | null {
  if (!task.taskable) {
    return null;
  }

  switch (task.taskable_type) {
    case 'meeting':
      return route('meetings.show', task.taskable_id);
    case 'reservation':
      return route('reservations.show', task.taskable_id);
    case 'institution':
      return route('institutions.show', task.taskable_id);
    case 'user':
      return route('users.show', task.taskable_id);
    default:
      return null;
  }
}

/** Meeting agenda tab, optionally opening the "add agenda item" flow. */
export function getMeetingAgendaUrl(task: Pick<TaskDisplayData, 'taskable_type' | 'taskable_id' | 'action_type'>): string | null {
  if (!isMeetingTask(task) || !task.taskable_id) {
    return null;
  }

  const params = new URLSearchParams({ tab: 'agenda' });
  if (isAgendaCreationTask(task)) {
    params.set('action', 'add');
  }

  return `${route('meetings.show', task.taskable_id)}?${params.toString()}`;
}

/**
 * Relative wording ("in 3 days", "Today") inside a week either way, an absolute date beyond it —
 * a fortnight-old due date reads as noise when phrased relatively.
 */
export function formatTaskDueDate(dueDate: string | null | undefined, locale: Locale): string {
  if (!dueDate) {
    return '';
  }

  try {
    const date = parseISO(dueDate);

    if (isToday(date)) {
      return $t('Today');
    }
    if (isTomorrow(date)) {
      return $t('Tomorrow');
    }
    if (Math.abs(differenceInDays(date, new Date())) <= 7) {
      return formatDistanceToNow(date, { addSuffix: true, locale });
    }

    return format(date, 'yyyy-MM-dd');
  }
  catch {
    return dueDate;
  }
}

/** Amber warning for a due date landing within three days; nothing once it is already overdue. */
export function getDueDateUrgencyClasses(task: Pick<TaskDisplayData, 'due_date' | 'is_overdue'>): string {
  if (!task.due_date || task.is_overdue) {
    return '';
  }

  const daysUntil = differenceInDays(parseISO(task.due_date), new Date());

  return daysUntil >= 0 && daysUntil <= 3
    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
    : '';
}
