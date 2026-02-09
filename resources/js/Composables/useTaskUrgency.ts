/**
 * Task Urgency Composable
 *
 * Provides utilities for sorting and filtering tasks by urgency.
 * Urgent tasks are prioritized as: overdue first, then by due date (soonest first).
 */

import { computed, type ComputedRef, type Ref, toValue, type MaybeRefOrGetter } from 'vue';
import { parseISO, isAfter, isBefore, addDays } from 'date-fns';

export interface TaskWithUrgencyInfo {
  id: string;
  name: string;
  due_date?: string | null;
  is_overdue?: boolean;
  completed_at?: string | null;
  [key: string]: unknown;
}

export interface UseTaskUrgencyOptions {
  /** Maximum number of tasks to return */
  limit?: number;
  /** Only include incomplete tasks (completed_at is null) */
  incompleteOnly?: boolean;
  /** Number of days to consider "due soon" */
  dueSoonDays?: number;
}

/**
 * Sort tasks by urgency: overdue first, then by due date (soonest first)
 */
export function sortTasksByUrgency<T extends TaskWithUrgencyInfo>(tasks: T[]): T[] {
  return [...tasks].sort((a, b) => {
    // Overdue tasks come first
    if (a.is_overdue && !b.is_overdue) return -1;
    if (!a.is_overdue && b.is_overdue) return 1;

    // Then sort by due date (soonest first)
    if (a.due_date && b.due_date) {
      const dateA = parseISO(a.due_date);
      const dateB = parseISO(b.due_date);
      if (isBefore(dateA, dateB)) return -1;
      if (isAfter(dateA, dateB)) return 1;
    }

    // Tasks with due dates come before those without
    if (a.due_date && !b.due_date) return -1;
    if (!a.due_date && b.due_date) return 1;

    return 0;
  });
}

/**
 * Get most urgent tasks (overdue + upcoming)
 */
export function getMostUrgentTasks<T extends TaskWithUrgencyInfo>(
  tasks: T[],
  options: UseTaskUrgencyOptions = {},
): T[] {
  const { limit = 5, incompleteOnly = true } = options;

  let filtered = tasks;

  // Filter to incomplete only if requested
  if (incompleteOnly) {
    filtered = filtered.filter(task => task.completed_at === null || task.completed_at === undefined);
  }

  // Sort by urgency
  const sorted = sortTasksByUrgency(filtered);

  // Return limited results
  return sorted.slice(0, limit);
}

/**
 * Calculate task statistics
 */
export function calculateTaskStats<T extends TaskWithUrgencyInfo>(
  tasks: T[],
  dueSoonDays = 3,
): { total: number; overdue: number; dueSoon: number } {
  const now = new Date();
  const dueSoonThreshold = addDays(now, dueSoonDays);

  const incompleteTasks = tasks.filter(
    task => task.completed_at === null || task.completed_at === undefined,
  );

  const overdue = incompleteTasks.filter(task => task.is_overdue).length;

  const dueSoon = incompleteTasks.filter((task) => {
    if (task.is_overdue || !task.due_date) return false;
    const dueDate = parseISO(task.due_date);
    return isBefore(dueDate, dueSoonThreshold);
  }).length;

  return {
    total: incompleteTasks.length,
    overdue,
    dueSoon,
  };
}

/**
 * Reactive composable for task urgency
 */
export function useTaskUrgency<T extends TaskWithUrgencyInfo>(
  tasks: MaybeRefOrGetter<T[]>,
  options: UseTaskUrgencyOptions = {},
) {
  const { limit = 5, incompleteOnly = true, dueSoonDays = 3 } = options;

  const urgentTasks: ComputedRef<T[]> = computed(() => {
    const taskList = toValue(tasks);
    return getMostUrgentTasks(taskList, { limit, incompleteOnly });
  });

  const stats = computed(() => {
    const taskList = toValue(tasks);
    const incompleteTasks = incompleteOnly
      ? taskList.filter(t => t.completed_at === null || t.completed_at === undefined)
      : taskList;
    return calculateTaskStats(incompleteTasks, dueSoonDays);
  });

  return {
    urgentTasks,
    stats,
  };
}
