/**
 * Task System Type Definitions
 *
 * Centralized types for the task management system including:
 * - Action types (manual, approval, pickup, return)
 * - Progress tracking for multi-item tasks
 * - Task display properties
 */

/**
 * Action types that determine how a task can be completed.
 * Must match App\Tasks\Enums\ActionType on the backend.
 */
export enum TaskActionType {
  Manual = 'manual',
  Approval = 'approval',
  Pickup = 'pickup',
  Return = 'return',
}

/**
 * Task progress information for multi-item tasks (e.g., return multiple resources).
 */
export interface TaskProgress {
  current: number
  total: number
  percentage: number
}

/**
 * Metadata stored in task's JSON metadata column.
 */
export interface TaskMetadata {
  items_total?: number
  items_completed?: number
  [key: string]: unknown
}

/**
 * Extended task interface with frontend-specific computed properties.
 * Extends the base Task from models.d.ts with action type and progress info.
 */
export interface TaskWithDetails extends Omit<models.Task, 'taskable'> {
  // Action type (from backend enum)
  action_type?: TaskActionType | null

  // Metadata JSON for progress tracking
  metadata?: TaskMetadata | null

  // Computed progress (from backend getProgress() or calculated frontend)
  progress?: TaskProgress | null

  // Icon name based on action type
  icon?: string

  // Color scheme based on action type
  color?: string

  // Whether task can be manually completed
  can_be_manually_completed?: boolean

  // Whether task is overdue
  is_overdue?: boolean

  // The related model (properly typed, not recursive Task)
  taskable?: {
    id: string
    name?: string
    title?: string
    [key: string]: unknown
  } | null
}

/**
 * Statistics about a user's tasks.
 */
export interface TaskStats {
  total: number
  overdue: number
  dueSoon: number
}

/**
 * Task data for the indicator popover (simplified for header display).
 */
export interface TaskIndicatorItem {
  id: string
  name: string
  due_date: string | null
  is_overdue: boolean
  action_type: TaskActionType | null
  can_be_manually_completed: boolean
  progress: TaskProgress | null
  taskable_type: string
  taskable_id: string
  taskable?: {
    id: string
    name?: string
    title?: string
  } | null
}

/**
 * Configuration for task action type display.
 */
export interface TaskActionTypeConfig {
  type: TaskActionType
  label: string
  icon: string
  color: string
  canBeManuallyCompleted: boolean
}

/**
 * Get configuration for a task action type.
 */
export function getActionTypeConfig(actionType: TaskActionType | null | undefined): TaskActionTypeConfig {
  switch (actionType) {
    case TaskActionType.Approval:
      return {
        type: TaskActionType.Approval,
        label: 'Approval',
        icon: 'shield-check',
        color: 'blue',
        canBeManuallyCompleted: false,
      }
    case TaskActionType.Pickup:
      return {
        type: TaskActionType.Pickup,
        label: 'Pickup',
        icon: 'package',
        color: 'amber',
        canBeManuallyCompleted: false,
      }
    case TaskActionType.Return:
      return {
        type: TaskActionType.Return,
        label: 'Return',
        icon: 'package-check',
        color: 'emerald',
        canBeManuallyCompleted: false,
      }
    case TaskActionType.Manual:
    default:
      return {
        type: TaskActionType.Manual,
        label: 'Manual',
        icon: 'clipboard-check',
        color: 'zinc',
        canBeManuallyCompleted: true,
      }
  }
}

/**
 * Check if a task can be manually completed based on its action type.
 */
export function canTaskBeManuallyCompleted(task: { action_type?: TaskActionType | null; can_be_manually_completed?: boolean }): boolean {
  // Use backend-computed value if available
  if (task.can_be_manually_completed !== undefined) {
    return task.can_be_manually_completed
  }

  // Fallback to frontend calculation
  return getActionTypeConfig(task.action_type).canBeManuallyCompleted
}

/**
 * Check if a task is an auto-completing task.
 */
export function isAutoCompletingTask(task: { action_type?: TaskActionType | null }): boolean {
  return !canTaskBeManuallyCompleted(task)
}
