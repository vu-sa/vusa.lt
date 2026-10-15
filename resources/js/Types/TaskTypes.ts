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
  PeriodicityGap = 'periodicity_gap',
  AgendaCreation = 'agenda_creation',
  AgendaCompletion = 'agenda_completion',
}

/**
 * Task progress information for multi-item tasks (e.g., return multiple resources).
 */
export interface TaskProgress {
  current: number;
  total: number;
  percentage: number;
}
