import type { ActionType } from '@/Types/enums';

/** A task row as `MeetingPanelPresenter::taskRows()` sends it (TaskResource without listing-only abilities). */
export interface TaskRow {
  id: string;
  name: string;
  description: string | null;
  due_date: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  created_at: string;
  updated_at: string;
  action_type: ActionType | null;
  metadata: Record<string, unknown> | null;
  progress: { current: number; total: number; percentage: number } | null;
  is_overdue: boolean;
  can_be_manually_completed: boolean;
  can_update: boolean;
  icon: string;
  color: string;
  taskable: { id: number | string; name: string | null; type: string } | null;
  taskable_type: string;
  taskable_id: string;
  users: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}
